import AsyncStorage from '@react-native-async-storage/async-storage';

// API URL - Change based on environment
// LOCAL TEST: 'http://10.181.83.18/binest/backend/api'
// PRODUCTION: 'https://tensemock.in/api'
export const API_URL = 'https://tensemock.in/api';

// Helper function to get auth token
const getAuthToken = async () => {
  return await AsyncStorage.getItem('authToken');
};

// Generic API caller to reduce redundancy
const apiCall = async (endpoint, options = {}) => {
  try {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 10000);
    
    const response = await fetch(`${API_URL}${endpoint}`, {
      ...options,
      signal: controller.signal,
      headers: {
        'Content-Type': 'application/json',
        ...(options.headers || {}),
      },
    });
    clearTimeout(timeoutId);
    return await response.json();
  } catch (error) {
    // silent
    return { success: false, error: error.message || 'Network request failed' };
  }
};

// Auth APIs
export const registerUser = async (userData) => {
  return apiCall('/auth.php?action=register', {
    method: 'POST',
    body: JSON.stringify(userData)
  });
};

export const loginUser = async (credentials) => {
  return apiCall('/auth.php?action=login', {
    method: 'POST',
    body: JSON.stringify(credentials)
  });
};

// OTP & Password APIs
export const sendOTP = async (email, purpose = 'registration') => {
  return apiCall('/otp.php?action=send', {
    method: 'POST',
    body: JSON.stringify({ email, purpose })
  });
};

export const resendOTP = async (email, purpose = 'registration') => {
  return apiCall('/otp.php?action=resend', {
    method: 'POST',
    body: JSON.stringify({ email, purpose })
  });
};

export const verifyAndRegister = async (userData, otp) => {
  return apiCall('/auth.php?action=verifyAndRegister', {
    method: 'POST',
    body: JSON.stringify({ ...userData, otp })
  });
};

export const resetPassword = async (email, otp, newPassword) => {
  return apiCall('/auth.php?action=resetPassword', {
    method: 'POST',
    body: JSON.stringify({ email, otp, newPassword })
  });
};

// Product APIs
export const getProducts = async (userId) => {
  return apiCall(`/products.php?action=get&userId=${userId}`);
};

export const addProduct = async (productData) => {
  const userId = await AsyncStorage.getItem('userId');
  return apiCall(`/products.php?action=add&userId=${userId}`, {
    method: 'POST',
    body: JSON.stringify(productData)
  });
};

export const updateProduct = async (productId, productData) => {
  const userId = await AsyncStorage.getItem('userId');
  return apiCall(`/products.php?action=update&userId=${userId}`, {
    method: 'POST',
    body: JSON.stringify({ ...productData, id: productId })
  });
};

export const deleteProduct = async (productId) => {
  const userId = await AsyncStorage.getItem('userId');
  return apiCall(`/products.php?action=delete&userId=${userId}&id=${productId}`);
};

// Customer APIs
export const getCustomers = async (userId) => {
  return apiCall(`/customers.php?action=get&userId=${userId}`);
};

export const addCustomer = async (customerData) => {
  const userId = await AsyncStorage.getItem('userId');
  return apiCall(`/customers.php?action=add&userId=${userId}`, {
    method: 'POST',
    body: JSON.stringify(customerData)
  });
};

export const updateCustomer = async (customerId, customerData) => {
  const userId = await AsyncStorage.getItem('userId');
  return apiCall(`/customers.php?action=update&userId=${userId}`, {
    method: 'POST',
    body: JSON.stringify({ ...customerData, id: customerId })
  });
};

export const deleteCustomer = async (customerId) => {
  const userId = await AsyncStorage.getItem('userId');
  return apiCall(`/customers.php?action=delete&userId=${userId}&id=${customerId}`);
};

// Bills APIs
export const getBills = async (userId) => {
  return apiCall(`/bills.php?action=get&userId=${userId}`);
};

export const addBill = async (billData) => {
  const userId = await AsyncStorage.getItem('userId');
  return apiCall(`/bills.php?action=add&userId=${userId}`, {
    method: 'POST',
    body: JSON.stringify(billData)
  });
};

export const updateBill = async (billId, billData) => {
  const userId = await AsyncStorage.getItem('userId');
  return apiCall(`/bills.php?action=update&userId=${userId}`, {
    method: 'POST',
    body: JSON.stringify({ ...billData, id: billId })
  });
};

export const deleteBill = async (billId) => {
  const userId = await AsyncStorage.getItem('userId');
  return apiCall(`/bills.php?action=delete&userId=${userId}&id=${billId}`);
};

export const updateDueStatus = async (billId, dueStatus) => {
  const userId = await AsyncStorage.getItem('userId');
  return apiCall(`/bills.php?action=update_due_status&userId=${userId}`, {
    method: 'POST',
    body: JSON.stringify({ billId, dueStatus })
  });
};

export const addDuePayment = async (billId, amount, note = '') => {
  const userId = await AsyncStorage.getItem('userId');
  return apiCall(`/bills.php?action=add_payment&userId=${userId}`, {
    method: 'POST',
    body: JSON.stringify({ billId, amount, note, paymentDate: new Date().toISOString().split('T')[0] })
  });
};

export const getDuePayments = async (billId) => {
  const userId = await AsyncStorage.getItem('userId');
  return apiCall(`/bills.php?action=get_payments&userId=${userId}&billId=${billId}`);
};

// Expenses APIs
export const getExpenses = async (userId) => {
  return apiCall(`/expenses.php?action=get&userId=${userId}`);
};

export const addExpense = async (expenseData) => {
  const userId = await AsyncStorage.getItem('userId');
  return apiCall(`/expenses.php?action=add&userId=${userId}`, {
    method: 'POST',
    body: JSON.stringify(expenseData)
  });
};

export const updateExpense = async (expenseId, expenseData) => {
  const userId = await AsyncStorage.getItem('userId');
  return apiCall(`/expenses.php?action=update&userId=${userId}`, {
    method: 'POST',
    body: JSON.stringify({ ...expenseData, id: expenseId })
  });
};

export const deleteExpense = async (expenseId) => {
  const userId = await AsyncStorage.getItem('userId');
  return apiCall(`/expenses.php?action=delete&userId=${userId}&id=${expenseId}`);
};

// Backup & Restore APIs
export const createBackup = async (userId) => {
  return apiCall(`/backup.php?action=create&userId=${userId}`, {
    method: 'POST'
  });
};

export const restoreBackup = async (userId, backupId) => {
  return apiCall(`/backup.php?action=restore&userId=${userId}`, {
    method: 'POST',
    body: JSON.stringify({ backupId })
  });
};

export const getBackupList = async (userId) => {
  return apiCall(`/backup.php?action=list&userId=${userId}`);
};

export const checkSubscriptionLimit = async (userId) => {
  return apiCall(`/check_limit.php?userId=${userId}`);
};

export const getOwnerNotifications = async (userId) => {
  return apiCall(`/notifications.php?userId=${userId}`);
};

export const syncDataToServer = async (userId, localData) => {
  return apiCall(`/backup.php?action=sync&userId=${userId}`, {
    method: 'POST',
    body: JSON.stringify({ localData })
  });
};

// Hooks (optional, for backward compatibility if any screen uses them)
export const useApi = () => {
  return {
    getProducts,
    loginUser,
    registerUser,
    // Add others if needed
  };
};

export const getAppSettings = async () => {
  return await apiCall('/get_app_settings.php');
};

export const updateUserProfile = async (profileData) => {
  return await apiCall('/update_profile.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(profileData),
  });
};

export const useSmartApi = () => {
  return {
    apiCall: async (key, endpoint, options) => apiCall(endpoint, options),
    postCall: async (key, endpoint, body, options) => apiCall(endpoint, { method: 'POST', body: JSON.stringify(body), ...options })
  };
};
