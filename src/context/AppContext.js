import React, { createContext, useState, useEffect } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { AppState, Alert } from 'react-native';
import { scheduleAllNotifications, sendLocalPushNotification, requestNotificationPermissions } from '../services/NotificationService';
import SubscriptionModal from '../components/SubscriptionModal';
import {
  loginUser,
  registerUser,
  getProducts,
  addProduct as addProductAPI,
  updateProduct as updateProductAPI,
  deleteProduct as deleteProductAPI,
  getCustomers,
  addCustomer as addCustomerAPI,
  updateCustomer as updateCustomerAPI,
  deleteCustomer as deleteCustomerAPI,
  getBills,
  addBill as addBillAPI,
  updateBill as updateBillAPI,
  deleteBill as deleteBillAPI,
  updateDueStatus as updateDueStatusAPI,
  addDuePayment as addDuePaymentAPI,
  getDuePayments as getDuePaymentsAPI,
  getExpenses,
  addExpense as addExpenseAPI,
  updateExpense as updateExpenseAPI,
  deleteExpense as deleteExpenseAPI,
  createBackup,
  restoreBackup,
  getBackupList,
  syncDataToServer,
  getOwnerNotifications,
  checkSubscriptionLimit,
  updateUserProfile,
  API_URL
} from '../services/api';

export const AppContext = createContext();

export const AppProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [language, setLanguage] = useState('en');
  const [products, setProducts] = useState([]);
  const [categories, setCategories] = useState([]);
  const [customers, setCustomers] = useState([]);
  const [sales, setSales] = useState([]);
  const [expenses, setExpenses] = useState([]);
  const [bills, setBills] = useState([]);
  const [isLimited, setIsLimited] = useState(false);
  const [showSubModal, setShowSubModal] = useState(false);

  useEffect(() => {
    loadData();
  }, []);

  useEffect(() => {
    if (!user || !user.id) return;

    // Request notification permission on Android
    requestNotificationPermissions();

    // Immediate check when user is loaded
    checkUserStatus(user.id);
    sendHeartbeat(user);
    refreshLimit(user.id);

    // Check user status when app comes to foreground
    const subscription = AppState.addEventListener('change', handleAppStateChange);

    // Check user status + limit every 30 seconds
    const interval = setInterval(() => {
      checkUserStatus(user.id);
      refreshLimit(user.id);
    }, 30000);

    // Heartbeat every 60 seconds for live monitoring
    const heartbeatInterval = setInterval(() => {
      sendHeartbeat(user);
    }, 60000);

    // Poll for owner notifications every 30 seconds
    const checkOwnerNotifs = async () => {
      try {
        const res = await getOwnerNotifications(user.id);
        if (res.success && res.notifications && res.notifications.length > 0) {
          const lastSeenId = await AsyncStorage.getItem('lastSeenNotifId');
          const lastId = parseInt(lastSeenId) || 0;
          const newNotifs = res.notifications.filter(n => parseInt(n.id) > lastId);
          if (newNotifs.length > 0) {
            await AsyncStorage.setItem('lastSeenNotifId', String(newNotifs[0].id));
            for (const n of newNotifs) {
              const typeEmoji = { info: 'ℹ️', success: '✅', warning: '⚠️', urgent: '🚨' };
              await sendLocalPushNotification(
                `${typeEmoji[n.type] || 'ℹ️'} ${n.title}`,
                n.message,
                { type: 'owner_notification', id: n.id }
              );
            }
          }
        }
      } catch (e) {}
    };
    checkOwnerNotifs();
    const notifInterval = setInterval(checkOwnerNotifs, 30000);

    return () => {
      subscription?.remove();
      clearInterval(interval);
      clearInterval(notifInterval);
      clearInterval(heartbeatInterval);
    };
  }, [user?.id]);

  const refreshLimit = async (uid) => {
    try {
      const res = await checkSubscriptionLimit(uid);
      if (res.success) setIsLimited(!!res.limited);
    } catch (e) {}
  };

  const guardLimit = () => {
    if (isLimited) {
      setShowSubModal(true);
      return true;
    }
    return false;
  };

  const sendHeartbeat = async (u) => {
    try {
      if (!u || !u.id) return;
      await fetch(`${API_URL}/heartbeat.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          userId: u.id,
          userName: u.name || '',
          businessName: u.business_name || '',
          deviceInfo: `${require('react-native').Platform.OS} ${require('react-native').Platform.Version}`,
          screen: 'Active',
        }),
      });
    } catch (e) {}
  };

  const loadData = async () => {
    try {
      // Load user and language from local storage
      const [userData, languageData, userId] = await Promise.all([
        AsyncStorage.getItem('user'),
        AsyncStorage.getItem('selectedLanguage'),
        AsyncStorage.getItem('userId')
      ]);

      if (userData) setUser(JSON.parse(userData));
      if (languageData) setLanguage(languageData);

      // Load data from cloud if user is logged in
      if (userId) {
        await loadCloudData(userId);
      } else {
        // Load from local storage for offline mode
        await loadLocalData();
      }
    } catch (error) {
      // silent
    }
  };

  const loadCloudData = async (userId) => {
    try {
      // Load data from database only - NO local storage
      const [productsResponse, customersResponse] = await Promise.all([
        getProducts(userId),
        getCustomers(userId)
      ]);

      if (productsResponse.success) {
        setProducts(productsResponse.products || []);
      }

      if (customersResponse.success) {
        setCustomers(customersResponse.customers || []);
      }

      // Load bills and expenses from database
      const [billsResponse, expensesResponse] = await Promise.all([
        getBills(userId),
        getExpenses(userId)
      ]);

      if (billsResponse.success) {
        setBills(billsResponse.bills || []);
        // Create sales from bills for reports
        const salesData = (billsResponse.bills || []).map(bill => ({
          id: bill.id,
          customerName: bill.customer_name || bill.customerName,
          amount: bill.grand_total || bill.grandTotal || bill.total,
          date: bill.date,
          items: Array.isArray(bill.items) ? bill.items.length : 0,
          payment_mode: bill.payment_mode || bill.paymentMode,
          due_status: bill.due_status,
          due_date: bill.due_date,
          paid_amount: bill.paid_amount
        }));
        setSales(salesData);
      }

      if (expensesResponse.success) {
        setExpenses(expensesResponse.expenses || []);
      }

      // Data loading complete

      // Schedule notifications once per day (not on every app open)
      try {
        const lastScheduled = await AsyncStorage.getItem('lastNotifSchedule');
        const todayStr = new Date().toDateString();
        if (lastScheduled !== todayStr) {
          const loadedBills = billsResponse?.bills || [];
          const loadedExpenses = expensesResponse?.expenses || [];
          await scheduleAllNotifications(loadedBills, loadedExpenses);
          await AsyncStorage.setItem('lastNotifSchedule', todayStr);
        }
      } catch (notifError) {
      }
    } catch (error) {
    }
  };

  const loadLocalData = async () => {
    // NO LOCAL STORAGE - Database only
  };

  const login = async (credentials) => {
    try {
      const response = await loginUser(credentials);
      if (response.success) {
        const userData = response.user;
        await AsyncStorage.setItem('user', JSON.stringify(userData));
        await AsyncStorage.setItem('userId', userData.id.toString());
        setUser(userData);
        await loadCloudData(userData.id);
        return { success: true };
      } else {
        return { success: false, error: response.error };
      }
    } catch (error) {
      // silent
      return { success: false, error: error.message };
    }
  };

  const register = async (userData) => {
    try {
      const response = await registerUser(userData);
      if (response.success) {
        const newUserData = response.user;
        await AsyncStorage.setItem('user', JSON.stringify(newUserData));
        await AsyncStorage.setItem('userId', newUserData.id.toString());
        setUser(newUserData);
        await loadCloudData(newUserData.id);
        return { success: true };
      } else {
        return { success: false, error: response.error };
      }
    } catch (error) {
      // silent
      return { success: false, error: error.message };
    }
  };

  const logout = async () => {
    try {
      await AsyncStorage.removeItem('user');
      await AsyncStorage.removeItem('userId');
      await AsyncStorage.removeItem('userToken');
      setUser(null);
      // Clear all data
      setProducts([]);
      setCustomers([]);
      setSales([]);
      setExpenses([]);
      setBills([]);
    } catch (error) {
      // silent
    }
  };

  const saveUser = async (userData) => {
    try {
      if (userData?.id) {
        const res = await updateUserProfile({
          userId: userData.id,
          businessName: userData.businessName || userData.business_name || '',
          mobile: userData.mobile || '',
          email: userData.email || '',
          address: userData.address || '',
        });
        if (res?.success && res.user) {
          const merged = { ...userData, ...res.user, businessName: res.user.business_name || userData.businessName };
          await AsyncStorage.setItem('user', JSON.stringify(merged));
          setUser(merged);
          return { success: true };
        }
      }
      await AsyncStorage.setItem('user', JSON.stringify(userData));
      setUser(userData);
      return { success: true };
    } catch (error) {
      await AsyncStorage.setItem('user', JSON.stringify(userData));
      setUser(userData);
      return { success: false };
    }
  };

  const saveLanguage = async (lang) => {
    try {
      await AsyncStorage.setItem('selectedLanguage', lang);
      setLanguage(lang);
    } catch (error) {
      // silent
    }
  };

  const checkUserStatus = async (userId) => {
    try {
      const response = await fetch(`${API_URL}/check_user_status.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ userId })
      });

      const data = await response.json();

      if (data.blocked === true) {

        // Show alert and logout immediately
        Alert.alert(
          'Account Blocked',
          'Your account has been blocked by the administrator.',
          [{ text: 'OK' }],
          { cancelable: false }
        );

        // Logout immediately (don't wait for button press)
        await logout();
      }
    } catch (error) {
      // Silent fail - network might be unavailable
    }
  };

  const handleAppStateChange = (nextAppState) => {
    if (nextAppState === 'active' && user && user.id) {
      // App came to foreground, check user status
      checkUserStatus(user.id);
    }
  };

  const addProduct = async (product) => {
    if (guardLimit()) return null;
    try {
      const userId = await AsyncStorage.getItem('userId');

      if (userId) {
        // Save to database only
        const response = await addProductAPI(product);
        if (response.success) {
          // Reload products from database
          const productsResponse = await getProducts(userId);
          if (productsResponse.success) {
            setProducts(productsResponse.products || []);
          }
        }
      }

    } catch (error) {
      // silent
    }
  };

  const updateProduct = async (productId, updatedProduct) => {
    try {
      const userId = await AsyncStorage.getItem('userId');

      if (userId) {
        // Update in database only
        const response = await updateProductAPI(productId, updatedProduct);
        if (response.success) {
          // Reload products from database
          const productsResponse = await getProducts(userId);
          if (productsResponse.success) {
            setProducts(productsResponse.products || []);
          }
        } else {
        }
      } else {
      }
    } catch (error) {
    }
  };

  const deleteProduct = async (productId) => {
    try {
      const userId = await AsyncStorage.getItem('userId');

      if (userId) {
        // Delete from database only
        const response = await deleteProductAPI(productId);
        if (response.success) {
          // Reload products from database
          const productsResponse = await getProducts(userId);
          if (productsResponse.success) {
            setProducts(productsResponse.products || []);
          }
        }
      }
    } catch (error) {
      // silent
    }
  };

  const addCategory = async (category) => {
    try {
      const newCategories = [...categories, { ...category, id: Date.now().toString() }];
      await AsyncStorage.setItem('categories', JSON.stringify(newCategories));
      setCategories(newCategories);
    } catch (error) {
      // silent
    }
  };

  const addCustomer = async (customer) => {
    if (guardLimit()) return null;
    try {
      const userId = await AsyncStorage.getItem('userId');

      if (userId) {
        // Save to database only
        const response = await addCustomerAPI(customer);
        if (response.success) {
          // Reload customers from database
          const customersResponse = await getCustomers(userId);
          if (customersResponse.success) {
            setCustomers(customersResponse.customers || []);
          }
        }
      }
    } catch (error) {
      // silent
    }
  };

  const updateCustomer = async (customerId, updatedCustomer) => {
    try {
      const userId = await AsyncStorage.getItem('userId');

      if (userId) {
        const response = await updateCustomerAPI(customerId, updatedCustomer);
        if (response.success) {
          const customersResponse = await getCustomers(userId);
          if (customersResponse.success) {
            setCustomers(customersResponse.customers || []);
          }
        }
      }
    } catch (error) {
      // silent
    }
  };

  const deleteCustomer = async (customerId) => {
    try {
      const userId = await AsyncStorage.getItem('userId');

      if (userId) {
        const response = await deleteCustomerAPI(customerId);
        if (response.success) {
          const customersResponse = await getCustomers(userId);
          if (customersResponse.success) {
            setCustomers(customersResponse.customers || []);
          }
        }
      }
    } catch (error) {
      // silent
    }
  };

  const addSale = async (sale) => {
    try {
      const newSales = [...sales, { ...sale, id: Date.now().toString(), date: new Date().toISOString() }];
      await AsyncStorage.setItem('sales', JSON.stringify(newSales));
      setSales(newSales);
    } catch (error) {
      // silent
    }
  };

  const addExpense = async (expense) => {
    if (guardLimit()) return null;
    try {
      const userId = await AsyncStorage.getItem('userId');

      if (userId) {
        // Save to database only
        const response = await addExpenseAPI(expense);
        if (response.success) {
          // Reload expenses from database
          const expensesResponse = await getExpenses(userId);
          if (expensesResponse.success) {
            setExpenses(expensesResponse.expenses || []);
          }
        }
      }
    } catch (error) {
    }
  };

  const updateExpense = async (expenseId, updatedExpense) => {
    try {
      const userId = await AsyncStorage.getItem('userId');

      if (userId) {
        const response = await updateExpenseAPI(expenseId, updatedExpense);
        if (response.success) {
          const expensesResponse = await getExpenses(userId);
          if (expensesResponse.success) {
            setExpenses(expensesResponse.expenses || []);
          }
        }
      }
    } catch (error) {
      // silent
    }
  };

  const deleteExpense = async (expenseId) => {
    try {
      const userId = await AsyncStorage.getItem('userId');

      if (userId) {
        const response = await deleteExpenseAPI(expenseId);
        if (response.success) {
          const expensesResponse = await getExpenses(userId);
          if (expensesResponse.success) {
            setExpenses(expensesResponse.expenses || []);
          }
        }
      }
    } catch (error) {
      // silent
    }
  };

  const addBill = async (bill) => {
    if (guardLimit()) return { limit_reached: true };
    try {
      const userId = await AsyncStorage.getItem('userId');
      const safeBills = bills || [];

      if (userId) {
        // Save to cloud database
        const now = new Date();
        const day = String(now.getDate()).padStart(2, '0');
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const year = now.getFullYear();
        const datePrefix = `${day}${month}${year}`;
        const sequentialNumber = String(safeBills.length + 1).padStart(3, '0');
        const invoiceNumber = `${datePrefix}${sequentialNumber}`;

        // Format date as Y-m-d for MySQL
        const mysqlDate = `${year}-${month}-${day}`;

        const billData = {
          invoiceNumber: invoiceNumber,
          customerId: bill.customerId || null,
          customerName: bill.customerName || '',
          customerMobile: bill.customerMobile || '',
          customerEmail: bill.customerEmail || '',
          customerAddress: bill.customerAddress || '',
          items: bill.items || [],
          subtotal: bill.subtotal || 0,
          discount: bill.discount || 0,
          tax: bill.tax || 0,
          total: bill.total || 0,
          grandTotal: bill.grandTotal || bill.total || 0,
          paymentMode: bill.paymentMode || 'Cash',
          due_status: bill.due_status || ((bill.paymentMode === 'Due') ? 'unpaid' : 'paid'),
          due_date: bill.due_date || null,
          date: mysqlDate
        };

        const response = await addBillAPI(billData);

        if (response && response.success) {
          // Create the saved bill object with DB ID
          const savedBill = {
            ...billData,
            id: response.billId,
            invoiceNumber: invoiceNumber
          };

          // Update product stock in database
          for (const item of bill.items) {
            if (item.productId && !item.isManual) {
              const product = products.find(p => p.id === item.productId);
              if (product) {
                const newStock = product.stock - item.quantity;
                await updateProduct(item.productId, {
                  stock: newStock
                });
              } else {
              }
            } else {
            }
          }

          // Reload ALL data from database to ensure sync
          await loadCloudData(userId);

          return savedBill;
        } else {
          if (response?.limit_reached) {
            return { limit_reached: true };
          }
          throw new Error(response?.error || 'Failed to create bill');
        }
      }
    } catch (error) {
      Alert.alert('Error', `Failed to create bill: ${error.message}`);
      return null;
    }
  };

  const updateBill = async (billId, updatedBill) => {
    try {
      const userId = await AsyncStorage.getItem('userId');

      if (userId) {
        const response = await updateBillAPI(billId, updatedBill);
        if (response.success) {
          const billsResponse = await getBills(userId);
          if (billsResponse.success) {
            setBills(billsResponse.bills);
            await AsyncStorage.setItem('bills', JSON.stringify(billsResponse.bills));
          }
          // Return the updated bill from API response or build it
          const returnBill = response.bill || { ...updatedBill, id: billId };
          return returnBill;
        }
        return null;
      } else {
        const merged = { ...updatedBill, id: billId };
        const newBills = bills.map(b => String(b.id) === String(billId) ? { ...b, ...updatedBill } : b);
        await AsyncStorage.setItem('bills', JSON.stringify(newBills));
        setBills(newBills);
        return merged;
      }
    } catch (error) {
      // silent
      return null;
    }
  };

  const deleteBill = async (billId) => {
    try {
      const userId = await AsyncStorage.getItem('userId');
      const billToDelete = bills.find(b => b.id === billId);
      if (!billToDelete) return;

      // Restore product stock
      for (const item of billToDelete.items) {
        if (!item.isManual) {
          const product = products.find(p => p.id === item.productId);
          if (product) {
            await updateProduct(item.productId, {
              stock: product.stock + item.quantity
            });
          }
        }
      }

      if (userId) {
        const response = await deleteBillAPI(billId);
        if (response.success) {
          const billsResponse = await getBills(userId);
          if (billsResponse.success) {
            setBills(billsResponse.bills);
            await AsyncStorage.setItem('bills', JSON.stringify(billsResponse.bills));
          }
        }
        return true;
      } else {
        const newBills = bills.filter(b => b.id !== billId);
        await AsyncStorage.setItem('bills', JSON.stringify(newBills));
        setBills(newBills);

        const newSales = sales.filter(s => s.id !== billId);
        await AsyncStorage.setItem('sales', JSON.stringify(newSales));
        setSales(newSales);
        return true;
      }
    } catch (error) {
      // silent
      const newBills = bills.filter(b => b.id !== billId);
      await AsyncStorage.setItem('bills', JSON.stringify(newBills));
      setBills(newBills);
      return false;
    }
  };

  const clearAllData = async () => {
    try {
      await AsyncStorage.clear();
      setProducts([]);
      setCategories([]);
      setCustomers([]);
      setSales([]);
      setExpenses([]);
      setBills([]);
      // cleared
    } catch (error) {
      // silent
    }
  };

  const addDuePayment = async (billId, amount, note = '') => {
    try {
      const response = await addDuePaymentAPI(billId, amount, note);
      if (response.success) {
        const updatedBills = bills.map(b => 
          (String(b.id) === String(billId))
            ? { ...b, paid_amount: response.paid_amount, due_status: response.due_status, due_paid_date: response.due_status === 'paid' ? new Date().toISOString().split('T')[0] : b.due_paid_date }
            : b
        );
        setBills(updatedBills);
        await AsyncStorage.setItem('bills', JSON.stringify(updatedBills));

        // Re-schedule due reminders after payment
        try { await scheduleAllNotifications(updatedBills, expenses); } catch (e) { /* silent */ }
        return response;
      }
      return response;
    } catch (error) {
      // silent
      return { success: false, error: error.message };
    }
  };

  const updateDueStatus = async (billId, dueStatus) => {
    try {
      const response = await updateDueStatusAPI(billId, dueStatus);
      if (response.success) {
        const updatedBills = bills.map(b => 
          (b.id === billId || String(b.id) === String(billId))
            ? { ...b, due_status: dueStatus, due_paid_date: dueStatus === 'paid' ? new Date().toISOString().split('T')[0] : null }
            : b
        );
        setBills(updatedBills);
        await AsyncStorage.setItem('bills', JSON.stringify(updatedBills));

        // Update sales too
        const updatedSales = sales.map(s =>
          (s.id === billId || String(s.id) === String(billId))
            ? { ...s, due_status: dueStatus }
            : s
        );
        setSales(updatedSales);
        return true;
      }
      return false;
    } catch (error) {
      // silent
      return false;
    }
  };


  return (
    <AppContext.Provider
      value={{
        user,
        language,
        products,
        categories,
        customers,
        sales,
        expenses,
        bills,
        login,
        register,
        logout,
        saveUser,
        saveLanguage,
        addProduct,
        updateProduct,
        deleteProduct,
        addCustomer,
        updateCustomer,
        deleteCustomer,
        addBill,
        updateBill,
        deleteBill,
        addExpense,
        updateExpense,
        deleteExpense,
        clearAllData,
        updateDueStatus,
        addDuePayment,
        isLimited,
        showSubModal,
        setShowSubModal
      }}
    >
      {children}
      <SubscriptionModal visible={showSubModal} onClose={() => setShowSubModal(false)} />
    </AppContext.Provider>
  );
};
