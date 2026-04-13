import React, { useState, useContext } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, TextInput, Alert, Platform, Modal } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { AppContext } from '../../context/AppContext';
import useTranslation from '../../i18n/useTranslation';
import * as Print from 'expo-print';
import * as Sharing from 'expo-sharing';

const BillingScreen = ({ navigation, route }) => {
  const { customers, products, addBill, updateBill, user } = useContext(AppContext);
  const { t } = useTranslation();
  const editBill = route?.params?.editBill || null;
  const isEditing = !!editBill;

  // Parse items from editBill
  const getEditItems = () => {
    if (!editBill) return [];
    let parsed = editBill.items;
    if (typeof parsed === 'string') {
      try { parsed = JSON.parse(parsed); } catch { parsed = []; }
    }
    return Array.isArray(parsed) ? parsed : [];
  };

  const getEditCustomer = () => {
    if (!editBill) return null;
    return {
      id: editBill.customer_id || editBill.customerId,
      name: editBill.customer_name || editBill.customerName || '',
      mobile: editBill.customer_mobile || editBill.customerMobile || '',
      email: editBill.customer_email || editBill.customerEmail || '',
      address: editBill.customer_address || editBill.customerAddress || '',
    };
  };

  const [selectedCustomer, setSelectedCustomer] = useState(getEditCustomer());
  const [items, setItems] = useState(getEditItems());
  const [discount, setDiscount] = useState(editBill ? String(editBill.discount || '0') : '0');
  const [tax, setTax] = useState(editBill ? String(editBill.tax || '0') : '0');
  const [showCustomerList, setShowCustomerList] = useState(false);
  const [showProductList, setShowProductList] = useState(false);
  const [productSearch, setProductSearch] = useState('');
  const [showManualEntry, setShowManualEntry] = useState(false);
  const [manualItemName, setManualItemName] = useState('');
  const [manualItemPrice, setManualItemPrice] = useState('');
  const [manualItemQty, setManualItemQty] = useState('1');
  const [manualItemUnit, setManualItemUnit] = useState('Nos');
  const [paymentMethod, setPaymentMethod] = useState(editBill ? (editBill.paymentMode || editBill.payment_mode || 'Cash') : 'Cash');
  const [dueDate, setDueDate] = useState(editBill?.due_date || '');
  const [showDatePicker, setShowDatePicker] = useState(false);
  const [tempDate, setTempDate] = useState({ day: '', month: '', year: '' });

  const unitTypes = ['Nos', 'Kg', 'Ltr', 'Pcs', 'Box', 'Mtr', 'Gm', 'Ml'];

  const paymentMethods = ['Cash', 'Online', 'UPI', 'Card', 'Due'];

  const addItem = (product) => {
    const existingItem = items.find(item => item.productId === product.id);
    if (existingItem) {
      setItems(items.map(item =>
        item.productId === product.id
          ? { ...item, quantity: item.quantity + 1 }
          : item
      ));
    } else {
      setItems([...items, {
        productId: product.id,
        name: product.name,
        price: product.price,
        quantity: 1,
        unit: product.unit || 'Nos',
        stock: product.stock
      }]);
    }
    setShowProductList(false);
    setProductSearch('');
  };

  const addManualItem = () => {
    if (!manualItemName || !manualItemPrice) {
      Alert.alert('Error', 'Please enter item name and price');
      return;
    }

    const newItem = {
      productId: 'manual_' + Date.now(),
      name: manualItemName,
      price: parseFloat(manualItemPrice),
      quantity: parseInt(manualItemQty) || 1,
      unit: manualItemUnit,
      stock: 999,
      isManual: true
    };

    setItems([...items, newItem]);
    setShowManualEntry(false);
    setManualItemName('');
    setManualItemPrice('');
    setManualItemQty('1');
    setManualItemUnit('Nos');
  };

  const filteredProducts = products.filter(p =>
    p.stock > 0 && p.name.toLowerCase().includes(productSearch.toLowerCase())
  );

  const updateQuantity = (productId, change) => {
    setItems(items.map(item => {
      if (item.productId === productId) {
        const newQuantity = item.quantity + change;
        if (newQuantity <= 0) {
          return null;
        }
        if (newQuantity > item.stock) {
          Alert.alert('Error', 'Not enough stock available');
          return item;
        }
        return { ...item, quantity: newQuantity };
      }
      return item;
    }).filter(Boolean));
  };

  const setQuantityDirectly = (productId, value) => {
    const qty = parseInt(value) || 0;
    if (qty <= 0) {
      setItems(items.filter(item => item.productId !== productId));
      return;
    }

    setItems(items.map(item => {
      if (item.productId === productId) {
        if (qty > item.stock && !item.isManual) {
          Alert.alert('Error', 'Not enough stock available');
          return item;
        }
        return { ...item, quantity: qty };
      }
      return item;
    }));
  };

  const calculateTotal = () => {
    const subtotal = items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const discountAmount = parseFloat(discount) || 0;
    const taxAmount = parseFloat(tax) || 0;
    const total = subtotal - discountAmount + taxAmount;
    return { subtotal, total };
  };

  const handleSave = async () => {
    if (!selectedCustomer) {
      Alert.alert('Error', 'Please select a customer');
      return;
    }
    if (items.length === 0) {
      Alert.alert('Error', 'Please add at least one item');
      return;
    }

    const { subtotal, total } = calculateTotal();
    const bill = {
      customerId: selectedCustomer.id,
      customerName: selectedCustomer.name,
      customerMobile: selectedCustomer.mobile || '',
      customerEmail: selectedCustomer.email || '',
      customerAddress: selectedCustomer.address || '',
      items,
      subtotal,
      discount: parseFloat(discount) || 0,
      tax: parseFloat(tax) || 0,
      total,
      grandTotal: total,
      paymentMode: paymentMethod,
      due_status: paymentMethod === 'Due' ? 'unpaid' : 'paid',
      due_date: paymentMethod === 'Due' ? dueDate : null,
    };

    let savedBill;
    if (isEditing) {
      const result = await updateBill(editBill.id, bill);
      if (result) {
        // Merge so InvoiceScreen gets both camelCase and snake_case fields
        savedBill = {
          ...editBill,
          ...result,
          ...bill,
          id: editBill.id,
          invoiceNumber: editBill.invoiceNumber || editBill.invoice_number || result.invoice_number,
          invoice_number: result.invoice_number || editBill.invoice_number || editBill.invoiceNumber,
          paymentMode: paymentMethod,
          payment_mode: paymentMethod,
          date: result.date || editBill.date || bill.date,
        };
      }
    } else {
      savedBill = await addBill(bill);
    }

    if (savedBill && savedBill.limit_reached) {
      return;
    }

    if (savedBill) {
      setSelectedCustomer(null);
      setItems([]);
      setDiscount('0');
      setTax('0');

      navigation.navigate('Invoice', { bill: savedBill });
    } else {
      Alert.alert('Error', `Failed to ${isEditing ? 'update' : 'create'} bill. Please try again.`);
    }
  };

  const { subtotal, total } = calculateTotal();

  const handleShare = async () => {
    if (items.length === 0) {
      Alert.alert('Error', 'Please add items to share');
      return;
    }

    try {
      const itemsHTML = items.map(item => `
        <tr>
          <td style="padding: 8px; border-bottom: 1px solid #E2E8F0;">${item.name}</td>
          <td style="padding: 8px; border-bottom: 1px solid #E2E8F0; text-align: center;">${item.quantity} ${item.unit || 'Nos'}</td>
          <td style="padding: 8px; border-bottom: 1px solid #E2E8F0; text-align: right;">₹${item.price}</td>
          <td style="padding: 8px; border-bottom: 1px solid #E2E8F0; text-align: right;">₹${item.price * item.quantity}</td>
        </tr>
      `).join('');

      const htmlContent = `
        <!DOCTYPE html>
        <html>
          <head>
            <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no" />
            <style>
              body { font-family: Arial, sans-serif; padding: 20px; }
              .header { text-align: center; margin-bottom: 30px; }
              .company-name { font-size: 24px; font-weight: bold; color: #2563EB; }
              table { width: 100%; border-collapse: collapse; margin: 20px 0; }
              th { background-color: #2563EB; color: white; padding: 10px; text-align: left; }
              .totals { margin-top: 20px; text-align: right; }
              .grand-total { font-size: 20px; font-weight: bold; color: #2563EB; }
            </style>
          </head>
          <body>
            <div class="header">
              <div class="company-name">${user?.businessName || 'BINEST'}</div>
              <div style="font-size: 13px; color: #64748B;">ESTIMATE / INVOICE</div>
              <div style="color: #64748B;">Date: ${new Date().toLocaleDateString()}</div>
            </div>

            <table>
              <thead>
                <tr>
                  <th>Item</th>
                  <th style="text-align: center;">Qty</th>
                  <th style="text-align: right;">Price</th>
                  <th style="text-align: right;">Total</th>
                </tr>
              </thead>
              <tbody>
                ${itemsHTML}
              </tbody>
            </table>

            <div class="totals">
              <div class="total-row">Subtotal: ₹${subtotal.toFixed(2)}</div>
              <div class="total-row">Discount: -₹${parseFloat(discount).toFixed(2)}</div>
              <div class="total-row">Tax: ₹${parseFloat(tax).toFixed(2)}</div>
              <div class="grand-total">Total: ₹${total.toFixed(2)}</div>
            </div>
            
            <div style="margin-top: 40px; text-align: center; font-size: 12px; color: #64748B;">
              Generated by BINEST App
            </div>
          </body>
        </html>
      `;

      const { uri } = await Print.printToFileAsync({ html: htmlContent });
      if (await Sharing.isAvailableAsync()) {
        await Sharing.shareAsync(uri);
      }
    } catch (error) {
      // silent
      Alert.alert('Error', 'Failed to share bill');
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      <LinearGradient
        colors={['#E0E7FF', '#F8FAFC']}
        style={styles.header}
      >
        <View style={styles.headerTop}>
          <TouchableOpacity onPress={() => navigation.goBack()}>
            <Ionicons name="arrow-back" size={24} color="#1E293B" />
          </TouchableOpacity>
          <Text style={styles.headerTitle}>{t('billing')}</Text>
          <TouchableOpacity>
            <Ionicons name="ellipsis-vertical" size={24} color="#1E293B" />
          </TouchableOpacity>
        </View>
      </LinearGradient>

      <ScrollView style={styles.content}>
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>{t('selectCustomer')}</Text>
          <TouchableOpacity
            style={styles.customerSelector}
            onPress={() => setShowCustomerList(!showCustomerList)}
          >
            <View style={styles.customerInfo}>
              {selectedCustomer ? (
                <>
                  <View style={styles.customerAvatar}>
                    <Text style={styles.customerInitial}>
                      {selectedCustomer.name.charAt(0).toUpperCase()}
                    </Text>
                  </View>
                  <View>
                    <Text style={styles.customerName}>{selectedCustomer.name}</Text>
                    <Text style={styles.customerMobile}>{selectedCustomer.mobile}</Text>
                  </View>
                </>
              ) : (
                <Text style={styles.placeholderText}>Select a customer</Text>
              )}
            </View>
            <Ionicons name="chevron-down" size={20} color="#64748B" />
          </TouchableOpacity>

          {showCustomerList && (
            <View style={styles.dropdown}>
              {customers.map((customer) => (
                <TouchableOpacity
                  key={customer.id}
                  style={styles.dropdownItem}
                  onPress={() => {
                    setSelectedCustomer(customer);
                    setShowCustomerList(false);
                  }}
                >
                  <Text style={styles.dropdownText}>{customer.name}</Text>
                  <Text style={styles.dropdownSubtext}>{customer.mobile}</Text>
                </TouchableOpacity>
              ))}
            </View>
          )}
        </View>

        <View style={styles.section}>
          <View style={styles.sectionHeader}>
            <Text style={styles.sectionTitle}>Items</Text>
            <View style={styles.headerActions}>
              <TouchableOpacity
                style={styles.manualEntryButton}
                onPress={() => setShowManualEntry(true)}
              >
                <Ionicons name="create-outline" size={20} color="#10B981" />
              </TouchableOpacity>
              <TouchableOpacity onPress={() => setShowProductList(!showProductList)}>
                <Ionicons name="add-circle" size={24} color="#2563EB" />
              </TouchableOpacity>
            </View>
          </View>

          {showProductList && (
            <View style={styles.productList}>
              <View style={styles.searchContainer}>
                <Ionicons name="search" size={20} color="#64748B" />
                <TextInput
                  style={styles.searchInput}
                  placeholder={t('searchProducts')}
                  value={productSearch}
                  onChangeText={setProductSearch}
                />
                {productSearch.length > 0 && (
                  <TouchableOpacity onPress={() => setProductSearch('')}>
                    <Ionicons name="close-circle" size={20} color="#94A3B8" />
                  </TouchableOpacity>
                )}
              </View>
              {filteredProducts.map((product) => (
                <TouchableOpacity
                  key={product.id}
                  style={styles.productItem}
                  onPress={() => addItem(product)}
                >
                  <Text style={styles.productName}>{product.name}</Text>
                  <View style={styles.productRight}>
                    <Text style={styles.productPrice}>₹{product.price}</Text>
                    <Text style={styles.productStock}>Stock: {product.stock}</Text>
                  </View>
                </TouchableOpacity>
              ))}
              {filteredProducts.length === 0 && (
                <View style={styles.noResults}>
                  <Text style={styles.noResultsText}>No products found</Text>
                </View>
              )}
            </View>
          )}

          {showManualEntry && (
            <View style={styles.manualEntryModal}>
              <View style={styles.manualEntryHeader}>
                <Text style={styles.manualEntryTitle}>Add Custom Item</Text>
                <TouchableOpacity onPress={() => setShowManualEntry(false)}>
                  <Ionicons name="close" size={24} color="#64748B" />
                </TouchableOpacity>
              </View>
              <TextInput
                style={styles.manualInput}
                placeholder="Item Name"
                value={manualItemName}
                onChangeText={setManualItemName}
              />
              <View style={styles.manualInputRow}>
                <TextInput
                  style={[styles.manualInput, { flex: 1, marginRight: 8 }]}
                  placeholder="Price"
                  value={manualItemPrice}
                  onChangeText={setManualItemPrice}
                  keyboardType="numeric"
                />
                <TextInput
                  style={[styles.manualInput, { flex: 1 }]}
                  placeholder="Quantity"
                  value={manualItemQty}
                  onChangeText={setManualItemQty}
                  keyboardType="numeric"
                />
              </View>
              <Text style={styles.unitLabel}>Unit Type</Text>
              <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ marginBottom: 12 }}>
                <View style={{ flexDirection: 'row', gap: 8 }}>
                  {unitTypes.map((unit) => (
                    <TouchableOpacity
                      key={unit}
                      style={[
                        styles.unitButton,
                        manualItemUnit === unit && styles.unitButtonActive
                      ]}
                      onPress={() => setManualItemUnit(unit)}
                    >
                      <Text style={[
                        styles.unitButtonText,
                        manualItemUnit === unit && styles.unitButtonTextActive
                      ]}>{unit}</Text>
                    </TouchableOpacity>
                  ))}
                </View>
              </ScrollView>
              <TouchableOpacity style={styles.addManualButton} onPress={addManualItem}>
                <Text style={styles.addManualButtonText}>Add Item</Text>
              </TouchableOpacity>
            </View>
          )}

          {items.map((item) => (
            <View key={item.productId} style={styles.itemCard}>
              <View style={styles.itemInfo}>
                <Text style={styles.itemName}>{item.name}</Text>
                <Text style={styles.itemPrice}>₹{item.price}/{item.unit || 'Nos'}</Text>
              </View>
              <View style={styles.itemControls}>
                <View style={styles.quantityControl}>
                  <TouchableOpacity
                    style={styles.quantityButton}
                    onPress={() => updateQuantity(item.productId, -1)}
                  >
                    <Ionicons name="remove" size={16} color="#2563EB" />
                  </TouchableOpacity>
                  <TextInput
                    style={styles.quantityInput}
                    value={String(item.quantity)}
                    onChangeText={(value) => setQuantityDirectly(item.productId, value)}
                    keyboardType="numeric"
                    selectTextOnFocus
                  />
                  <Text style={styles.unitText}>{item.unit || 'Nos'}</Text>
                  <TouchableOpacity
                    style={styles.quantityButton}
                    onPress={() => updateQuantity(item.productId, 1)}
                  >
                    <Ionicons name="add" size={16} color="#2563EB" />
                  </TouchableOpacity>
                </View>
                <Text style={styles.itemTotal}>₹{item.price * item.quantity}</Text>
              </View>
            </View>
          ))}
        </View>

        <View style={styles.section}>
          <View style={styles.inputRow}>
            <Text style={styles.inputLabel}>{t('discount')}</Text>
            <View style={styles.inputContainer}>
              <Text style={styles.currencySymbol}>₹</Text>
              <TextInput
                style={styles.input}
                value={discount}
                onChangeText={setDiscount}
                keyboardType="numeric"
              />
            </View>
          </View>

          <View style={styles.inputRow}>
            <Text style={styles.inputLabel}>{t('tax')}</Text>
            <View style={styles.inputContainer}>
              <Text style={styles.currencySymbol}>₹</Text>
              <TextInput
                style={styles.input}
                value={tax}
                onChangeText={setTax}
                keyboardType="numeric"
              />
            </View>
          </View>
        </View>

        <View style={styles.totalCard}>
          <View style={styles.totalRow}>
            <Text style={styles.totalLabel}>Subtotal</Text>
            <Text style={styles.totalValue}>₹{(parseFloat(subtotal) || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</Text>
          </View>
          <View style={styles.totalRow}>
            <Text style={styles.totalLabel}>Discount</Text>
            <Text style={styles.totalValue}>-₹{(parseFloat(discount) || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</Text>
          </View>
          <View style={styles.totalRow}>
            <Text style={styles.totalLabel}>Tax</Text>
            <Text style={styles.totalValue}>₹{(parseFloat(tax) || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</Text>
          </View>
          <View style={[styles.totalRow, styles.grandTotal]}>
            <Text style={styles.grandTotalLabel}>Total</Text>
            <Text style={styles.grandTotalValue}>₹{(parseFloat(total) || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</Text>
          </View>
        </View>

        <View style={styles.paymentMethodSection}>
          <Text style={styles.paymentMethodLabel}>Payment Method</Text>
          <View style={styles.paymentMethodGrid}>
            {paymentMethods.map((method) => (
              <TouchableOpacity
                key={method}
                style={[
                  styles.paymentMethodButton,
                  paymentMethod === method && styles.paymentMethodButtonActive
                ]}
                onPress={() => setPaymentMethod(method)}
              >
                <Ionicons
                  name={
                    method === 'Cash' ? 'cash' :
                      method === 'Card' ? 'card' :
                        method === 'UPI' ? 'phone-portrait' :
                          method === 'Due' ? 'time' :
                            'globe'
                  }
                  size={20}
                  color={paymentMethod === method ? '#FFFFFF' : '#2563EB'}
                />
                <Text style={[
                  styles.paymentMethodText,
                  paymentMethod === method && styles.paymentMethodTextActive
                ]}>
                  {method}
                </Text>
              </TouchableOpacity>
            ))}
          </View>
        </View>

        {paymentMethod === 'Due' && (
          <View style={styles.dueDateSection}>
            <Text style={styles.dueDateLabel}>📅 Due Date (Payment Deadline)</Text>
            <TouchableOpacity style={styles.dueDateInput} onPress={() => {
              if (dueDate) {
                const [y, m, d] = dueDate.split('-');
                setTempDate({ day: d, month: m, year: y });
              } else {
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 7);
                setTempDate({ day: String(tomorrow.getDate()).padStart(2, '0'), month: String(tomorrow.getMonth() + 1).padStart(2, '0'), year: String(tomorrow.getFullYear()) });
              }
              setShowDatePicker(true);
            }}>
              <Ionicons name="calendar" size={20} color="#2563EB" />
              <Text style={[styles.dueDateText, !dueDate && { color: '#94A3B8' }]}>
                {dueDate ? new Date(dueDate).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' }) : 'Select due date...'}
              </Text>
              {dueDate ? (
                <TouchableOpacity onPress={() => setDueDate('')}>
                  <Ionicons name="close-circle" size={20} color="#EF4444" />
                </TouchableOpacity>
              ) : <Ionicons name="chevron-forward" size={18} color="#94A3B8" />}
            </TouchableOpacity>
            <View style={styles.quickDates}>
              {[
                { label: '1 Week', days: 7 },
                { label: '15 Days', days: 15 },
                { label: '1 Month', days: 30 },
                { label: '2 Months', days: 60 },
              ].map(opt => {
                const d = new Date(); d.setDate(d.getDate() + opt.days);
                const val = d.toISOString().split('T')[0];
                return (
                  <TouchableOpacity key={opt.label} style={[styles.quickDateBtn, dueDate === val && { backgroundColor: '#2563EB' }]} onPress={() => setDueDate(val)}>
                    <Text style={[styles.quickDateText, dueDate === val && { color: '#FFF' }]}>{opt.label}</Text>
                  </TouchableOpacity>
                );
              })}
            </View>
          </View>
        )}

        <View style={styles.actions}>
          <TouchableOpacity style={styles.saveButton} onPress={handleSave}>
            <Text style={styles.saveButtonText}>{isEditing ? 'Update Bill' : 'Save'}</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.shareButton} onPress={handleShare}>
            <Ionicons name="share-social" size={20} color="#FFFFFF" />
            <Text style={styles.shareButtonText}>Share</Text>
          </TouchableOpacity>
        </View>

        <View style={styles.paymentMode}>
          <Text style={styles.paymentModeText}>Payment Mode 💳 UPI Credit</Text>
        </View>
      </ScrollView>

      {/* Date Picker Modal */}
      <Modal visible={showDatePicker} transparent animationType="fade" onRequestClose={() => setShowDatePicker(false)}>
        <View style={{ flex: 1, backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'center', alignItems: 'center' }}>
          <View style={{ backgroundColor: '#FFF', borderRadius: 16, padding: 24, width: '85%' }}>
            <Text style={{ fontSize: 18, fontWeight: 'bold', color: '#1E293B', marginBottom: 16 }}>Select Due Date</Text>
            <View style={{ flexDirection: 'row', justifyContent: 'space-between', marginBottom: 16 }}>
              <View style={{ flex: 1, marginRight: 8 }}>
                <Text style={{ fontSize: 12, color: '#64748B', marginBottom: 4 }}>Day</Text>
                <TextInput style={{ borderWidth: 1, borderColor: '#E2E8F0', borderRadius: 8, padding: 10, fontSize: 16, textAlign: 'center' }} keyboardType="numeric" maxLength={2} value={tempDate.day} onChangeText={v => setTempDate({ ...tempDate, day: v })} placeholder="DD" />
              </View>
              <View style={{ flex: 1, marginRight: 8 }}>
                <Text style={{ fontSize: 12, color: '#64748B', marginBottom: 4 }}>Month</Text>
                <TextInput style={{ borderWidth: 1, borderColor: '#E2E8F0', borderRadius: 8, padding: 10, fontSize: 16, textAlign: 'center' }} keyboardType="numeric" maxLength={2} value={tempDate.month} onChangeText={v => setTempDate({ ...tempDate, month: v })} placeholder="MM" />
              </View>
              <View style={{ flex: 1 }}>
                <Text style={{ fontSize: 12, color: '#64748B', marginBottom: 4 }}>Year</Text>
                <TextInput style={{ borderWidth: 1, borderColor: '#E2E8F0', borderRadius: 8, padding: 10, fontSize: 16, textAlign: 'center' }} keyboardType="numeric" maxLength={4} value={tempDate.year} onChangeText={v => setTempDate({ ...tempDate, year: v })} placeholder="YYYY" />
              </View>
            </View>
            <View style={{ flexDirection: 'row', justifyContent: 'flex-end' }}>
              <TouchableOpacity onPress={() => setShowDatePicker(false)} style={{ paddingHorizontal: 20, paddingVertical: 10, marginRight: 10 }}>
                <Text style={{ color: '#64748B', fontWeight: '600' }}>Cancel</Text>
              </TouchableOpacity>
              <TouchableOpacity onPress={() => {
                const d = parseInt(tempDate.day), m = parseInt(tempDate.month), y = parseInt(tempDate.year);
                if (!d || !m || !y || d < 1 || d > 31 || m < 1 || m > 12 || y < 2024) {
                  Alert.alert('Error', 'Enter valid date');
                  return;
                }
                const dateStr = `${y}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                setDueDate(dateStr);
                setShowDatePicker(false);
              }} style={{ backgroundColor: '#2563EB', paddingHorizontal: 20, paddingVertical: 10, borderRadius: 8 }}>
                <Text style={{ color: '#FFF', fontWeight: 'bold' }}>Set Date</Text>
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>

    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F8FAFC',
  },
  header: {
    paddingHorizontal: 20,
    paddingVertical: 16,
  },
  headerTop: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  headerTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#1E293B',
  },
  content: {
    flex: 1,
    padding: 20,
  },
  section: {
    marginBottom: 20,
  },
  sectionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#1E293B',
    marginBottom: 12,
  },
  customerSelector: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    backgroundColor: '#FFFFFF',
    padding: 16,
    borderRadius: 12,
  },
  customerInfo: {
    flexDirection: 'row',
    alignItems: 'center',
    flex: 1,
  },
  customerAvatar: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: '#2563EB',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 12,
  },
  customerInitial: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  customerName: {
    fontSize: 14,
    fontWeight: '600',
    color: '#1E293B',
  },
  customerMobile: {
    fontSize: 12,
    color: '#64748B',
  },
  placeholderText: {
    fontSize: 14,
    color: '#94A3B8',
  },
  dropdown: {
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    marginTop: 8,
    maxHeight: 200,
  },
  dropdownItem: {
    padding: 16,
    borderBottomWidth: 1,
    borderBottomColor: '#F1F5F9',
  },
  dropdownText: {
    fontSize: 14,
    fontWeight: '600',
    color: '#1E293B',
  },
  dropdownSubtext: {
    fontSize: 12,
    color: '#64748B',
    marginTop: 2,
  },
  productList: {
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    marginBottom: 12,
    maxHeight: 200,
  },
  productItem: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 16,
    borderBottomWidth: 1,
    borderBottomColor: '#F1F5F9',
  },
  productName: {
    fontSize: 14,
    fontWeight: '600',
    color: '#1E293B',
  },
  productRight: {
    alignItems: 'flex-end',
  },
  productPrice: {
    fontSize: 14,
    fontWeight: 'bold',
    color: '#2563EB',
  },
  productStock: {
    fontSize: 12,
    color: '#64748B',
  },
  itemCard: {
    backgroundColor: '#FFFFFF',
    padding: 16,
    borderRadius: 12,
    marginBottom: 12,
  },
  itemInfo: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 12,
  },
  itemName: {
    fontSize: 14,
    fontWeight: '600',
    color: '#1E293B',
  },
  itemPrice: {
    fontSize: 14,
    color: '#64748B',
  },
  itemControls: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  quantityControl: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#F1F5F9',
    borderRadius: 8,
    padding: 4,
  },
  quantityButton: {
    width: 28,
    height: 28,
    justifyContent: 'center',
    alignItems: 'center',
  },
  quantity: {
    fontSize: 14,
    fontWeight: '600',
    color: '#1E293B',
  },
  quantityInput: {
    fontSize: 14,
    fontWeight: '600',
    color: '#1E293B',
    backgroundColor: '#F8FAFC',
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 6,
    minWidth: 50,
    textAlign: 'center',
    borderWidth: 1,
    borderColor: '#E2E8F0',
    marginHorizontal: 16,
  },
  itemTotal: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#10B981',
  },
  inputRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  inputLabel: {
    fontSize: 14,
    color: '#1E293B',
  },
  inputContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFFFFF',
    borderRadius: 8,
    paddingHorizontal: 12,
  },
  currencySymbol: {
    fontSize: 14,
    color: '#64748B',
    marginRight: 4,
  },
  input: {
    width: 80,
    padding: 8,
    fontSize: 14,
    color: '#1E293B',
  },
  totalCard: {
    backgroundColor: '#FFFFFF',
    padding: 20,
    borderRadius: 12,
    marginBottom: 20,
  },
  totalRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 12,
  },
  totalLabel: {
    fontSize: 14,
    color: '#64748B',
  },
  totalValue: {
    fontSize: 14,
    fontWeight: '600',
    color: '#1E293B',
  },
  grandTotal: {
    borderTopWidth: 1,
    borderTopColor: '#E2E8F0',
    paddingTop: 12,
    marginTop: 8,
  },
  grandTotalLabel: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#1E293B',
  },
  grandTotalValue: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#2563EB',
  },
  actions: {
    flexDirection: 'row',
    marginBottom: 20,
  },
  saveButton: {
    flex: 1,
    backgroundColor: '#10B981',
    padding: 16,
    borderRadius: 12,
    alignItems: 'center',
    marginRight: 8,
  },
  saveButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: 'bold',
  },
  shareButton: {
    flex: 1,
    backgroundColor: '#2563EB',
    padding: 16,
    borderRadius: 12,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    marginLeft: 8,
  },
  shareButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: 'bold',
    marginLeft: 8,
  },
  paymentMode: {
    alignItems: 'center',
    marginBottom: 20,
  },
  paymentModeText: {
    fontSize: 12,
    color: '#64748B',
  },
  headerActions: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  manualEntryButton: {
    marginRight: 12,
    padding: 4,
  },
  searchContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#F1F5F9',
    padding: 12,
    borderRadius: 8,
    marginBottom: 12,
  },
  searchInput: {
    flex: 1,
    marginLeft: 8,
    fontSize: 14,
    color: '#1E293B',
  },
  noResults: {
    padding: 20,
    alignItems: 'center',
  },
  noResultsText: {
    fontSize: 14,
    color: '#94A3B8',
  },
  manualEntryModal: {
    backgroundColor: '#F8FAFC',
    padding: 16,
    borderRadius: 12,
    marginBottom: 12,
    borderWidth: 2,
    borderColor: '#10B981',
  },
  manualEntryHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 16,
  },
  manualEntryTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#1E293B',
  },
  manualInput: {
    backgroundColor: '#FFFFFF',
    padding: 12,
    borderRadius: 8,
    fontSize: 14,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  manualInputRow: {
    flexDirection: 'row',
  },
  addManualButton: {
    backgroundColor: '#10B981',
    padding: 14,
    borderRadius: 8,
    alignItems: 'center',
  },
  addManualButtonText: {
    color: '#FFFFFF',
    fontSize: 14,
    fontWeight: 'bold',
  },
  unitButton: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 6,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    backgroundColor: '#FFFFFF',
  },
  unitButtonActive: {
    backgroundColor: '#2563EB',
    borderColor: '#2563EB',
  },
  unitButtonText: {
    fontSize: 12,
    fontWeight: '600',
    color: '#64748B',
  },
  unitButtonTextActive: {
    color: '#FFFFFF',
  },
  unitText: {
    fontSize: 12,
    color: '#64748B',
    marginLeft: 4,
    fontWeight: '600',
  },
  unitLabel: {
    fontSize: 14,
    fontWeight: '600',
    color: '#1E293B',
    marginBottom: 8,
  },
  paymentMethodSection: {
    marginBottom: 20,
  },
  paymentMethodLabel: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#1E293B',
    marginBottom: 12,
  },
  paymentMethodGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
  },
  paymentMethodButton: {
    width: '48%',
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#FFFFFF',
    padding: 14,
    borderRadius: 12,
    marginBottom: 12,
    borderWidth: 2,
    borderColor: '#E2E8F0',
  },
  paymentMethodButtonActive: {
    backgroundColor: '#2563EB',
    borderColor: '#2563EB',
  },
  paymentMethodText: {
    fontSize: 14,
    fontWeight: '600',
    color: '#2563EB',
    marginLeft: 8,
  },
  paymentMethodTextActive: {
    color: '#FFFFFF',
  },
  dueDateSection: {
    backgroundColor: '#FFF7ED',
    borderRadius: 12,
    padding: 16,
    marginBottom: 20,
    borderWidth: 1,
    borderColor: '#FED7AA',
  },
  dueDateLabel: {
    fontSize: 14,
    fontWeight: 'bold',
    color: '#1E293B',
    marginBottom: 10,
  },
  dueDateInput: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFFFFF',
    borderRadius: 8,
    padding: 12,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    marginBottom: 10,
  },
  dueDateText: {
    flex: 1,
    fontSize: 14,
    color: '#1E293B',
    marginLeft: 10,
  },
  quickDates: {
    flexDirection: 'row',
    flexWrap: 'wrap',
  },
  quickDateBtn: {
    backgroundColor: '#EFF6FF',
    paddingHorizontal: 14,
    paddingVertical: 7,
    borderRadius: 20,
    marginRight: 8,
    marginBottom: 6,
  },
  quickDateText: {
    color: '#2563EB',
    fontWeight: '600',
    fontSize: 12,
  },
});

export default BillingScreen;
