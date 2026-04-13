import React, { useContext, useState, useRef } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import * as Print from 'expo-print';
import * as Sharing from 'expo-sharing';
import { AppContext } from '../../context/AppContext';

const InvoiceScreen = ({ route, navigation }) => {
  const { bill } = route.params || {};
  const { user } = useContext(AppContext);
  const isPrinting = useRef(false);

  // Safety check for bill data
  if (!bill) {
    return (
      <SafeAreaView style={styles.container}>
        <View style={styles.errorContainer}>
          <Ionicons name="alert-circle" size={64} color="#EF4444" />
          <Text style={styles.errorText}>Bill data not found</Text>
          <TouchableOpacity
            style={styles.backButton}
            onPress={() => navigation.goBack()}
          >
            <Text style={styles.backButtonText}>Go Back</Text>
          </TouchableOpacity>
        </View>
      </SafeAreaView>
    );
  }

  // Parse items if it's a JSON string from database
  let parsedItems = bill.items;
  if (typeof bill.items === 'string') {
    try {
      parsedItems = JSON.parse(bill.items);
    } catch (e) {
      // silent
      parsedItems = [];
    }
  }
  const safeItems = Array.isArray(parsedItems) ? parsedItems : [];

  const safeDate = (d) => {
    if (!d) return 'N/A';
    try {
      const date = new Date(d);
      if (isNaN(date.getTime())) return 'N/A';
      return date.toLocaleDateString('en-IN', { day: 'numeric', month: 'long', year: 'numeric' });
    } catch { return 'N/A'; }
  };

  const safeDateShort = (d) => {
    if (!d) return 'N/A';
    try {
      const date = new Date(d);
      if (isNaN(date.getTime())) return 'N/A';
      return date.toLocaleDateString();
    } catch { return 'N/A'; }
  };

  const safeNum = (v) => (parseFloat(v) || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const isDueBill = (bill.paymentMode || bill.payment_mode || '').toLowerCase() === 'due';

  const generateHTML = () => {
    const itemsHTML = safeItems.map(item => {
      const price = parseFloat(item.price) || 0;
      const qty = parseFloat(item.quantity) || 0;
      return `
      <tr>
        <td style="padding: 8px; border-bottom: 1px solid #E2E8F0;">${item.name || 'Item'}</td>
        <td style="padding: 8px; border-bottom: 1px solid #E2E8F0; text-align: center;">${qty} ${item.unit || 'Nos'}</td>
        <td style="padding: 8px; border-bottom: 1px solid #E2E8F0; text-align: right;">₹${price}</td>
        <td style="padding: 8px; border-bottom: 1px solid #E2E8F0; text-align: right;">₹${price * qty}</td>
      </tr>
    `;
    }).join('');

    const dueDateHTML = isDueBill && bill.due_date ? `
          <div style="margin-top: 15px; padding: 15px; background-color: #FEF2F2; border-radius: 8px; border: 1px solid #FECACA;">
            <div style="font-weight: bold; margin-bottom: 5px; color: #DC2626;">Due Date:</div>
            <div style="font-size: 16px; font-weight: bold; color: #DC2626;">${safeDate(bill.due_date)}</div>
            <div style="font-size: 12px; color: #64748B; margin-top: 4px;">Payment must be made on or before this date</div>
          </div>
    ` : '';

    return `
      <!DOCTYPE html>
      <html>
        <head>
          <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no" />
          <style>
            body { font-family: Arial, sans-serif; padding: 20px; }
            .header { text-align: center; margin-bottom: 30px; }
            .company-name { font-size: 24px; font-weight: bold; color: #2563EB; }
            .invoice-title { font-size: 18px; margin-top: 10px; }
            .info-section { margin-bottom: 20px; }
            .info-row { display: flex; justify-content: space-between; margin-bottom: 8px; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th { background-color: #2563EB; color: white; padding: 10px; text-align: left; }
            .totals { margin-top: 20px; text-align: right; }
            .total-row { margin: 8px 0; }
            .grand-total { font-size: 20px; font-weight: bold; color: #2563EB; margin-top: 10px; }
            .footer { text-align: center; margin-top: 40px; color: #64748B; font-size: 12px; }
          </style>
        </head>
        <body>
          <div class="header">
            <div class="company-name">${user?.businessName || 'BINEST'}</div>
            ${user?.address ? `<div style="font-size: 13px; color: #64748B; margin-top: 5px;">${user.address}</div>` : ''}
            <div style="font-size: 13px; color: #64748B; margin-top: 3px;">
              ${user?.mobile ? `Phone: ${user.mobile}` : ''}
              ${user?.email && user?.mobile ? ' | ' : ''}
              ${user?.email ? `Email: ${user.email}` : ''}
            </div>
            <div class="invoice-title" style="margin-top: 15px;">INVOICE</div>
            <div style="margin-top: 10px; color: #64748B;">Invoice #${bill.invoiceNumber || bill.invoice_number || bill.id}</div>
            <div style="color: #64748B;">${safeDateShort(bill.date)}</div>
            ${bill.time ? `<div style="color: #64748B;">Time: ${bill.time}</div>` : ''}
          </div>

          <div style="margin-bottom: 30px;">
            <div class="info-section">
              <div style="font-weight: bold; margin-bottom: 10px; color: #2563EB;">Bill To (Customer):</div>
              <div style="font-weight: bold;">${bill.customer_name || bill.customerName || 'Customer'}</div>
              <div style="color: #64748B; margin-top: 5px;">Customer ID: ${bill.customer_id || bill.customerId || 'N/A'}</div>
              ${(bill.customer_mobile || bill.customerMobile) ? `<div style="color: #64748B;">Phone: ${bill.customer_mobile || bill.customerMobile}</div>` : ''}
              ${(bill.customer_email || bill.customerEmail) ? `<div style="color: #64748B;">Email: ${bill.customer_email || bill.customerEmail}</div>` : ''}
              ${(bill.customer_address || bill.customerAddress) ? `<div style="color: #64748B;">Address: ${bill.customer_address || bill.customerAddress}</div>` : ''}
            </div>
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
            <div class="total-row">Subtotal: ₹${safeNum(bill.subtotal || bill.total || 0)}</div>
            <div class="total-row">Discount: -₹${safeNum(bill.discount || 0)}</div>
            <div class="total-row">Tax: ₹${safeNum(bill.tax || 0)}</div>
            <div class="grand-total">Total: ₹${safeNum(bill.grandTotal || bill.grand_total || bill.total || 0)}</div>
          </div>

          <div style="margin-top: 30px; padding: 15px; background-color: #F1F5F9; border-radius: 8px;">
            <div style="font-weight: bold; margin-bottom: 5px;">Payment Mode:</div>
            <div>${bill.paymentMode || bill.payment_mode || 'Cash'}</div>
          </div>

          ${dueDateHTML}

          <div class="footer">
            <div>Thank you for your business!</div>
            <div style="margin-top: 10px;">Generated by BINEST App</div>
          </div>
        </body>
      </html>
    `;
  };

  const downloadPDF = async () => {
    try {
      const html = generateHTML();
      const { uri } = await Print.printToFileAsync({ html });

      Alert.alert('Success', 'PDF generated successfully!', [
        {
          text: 'Share',
          onPress: async () => {
            if (await Sharing.isAvailableAsync()) {
              await Sharing.shareAsync(uri);
            }
          }
        },
        { text: 'OK' }
      ]);
    } catch (error) {
      // silent
      Alert.alert('Error', 'Failed to generate PDF');
    }
  };

  const printInvoice = async () => {
    if (isPrinting.current) return;
    isPrinting.current = true;
    try {
      const html = generateHTML();
      await Print.printAsync({ html });
    } catch (error) {
      Alert.alert('Error', 'Failed to print invoice: ' + (error?.message || 'Unknown error'));
    } finally {
      isPrinting.current = false;
    }
  };

  const subtotal = (parseFloat(bill.total || bill.grandTotal || bill.grand_total || 0) || 0) + (parseFloat(bill.discount) || 0) - (parseFloat(bill.tax) || 0);

  return (
    <SafeAreaView style={styles.container}>
      <LinearGradient
        colors={['#2563EB', '#1E40AF']}
        style={styles.header}
      >
        <View style={styles.headerTop}>
          <TouchableOpacity onPress={() => navigation.goBack()}>
            <Ionicons name="arrow-back" size={24} color="#FFFFFF" />
          </TouchableOpacity>
          <Text style={styles.headerTitle}>Invoice</Text>
          <View style={{ flexDirection: 'row', alignItems: 'center' }}>
            <TouchableOpacity onPress={() => navigation.navigate('Billing', { editBill: bill })} style={{ marginRight: 16 }}>
              <Ionicons name="create-outline" size={24} color="#FFFFFF" />
            </TouchableOpacity>
            <TouchableOpacity onPress={downloadPDF}>
              <Ionicons name="download" size={24} color="#FFFFFF" />
            </TouchableOpacity>
          </View>
        </View>
      </LinearGradient>

      <ScrollView style={styles.content}>
        <View style={styles.invoiceCard}>
          <View style={styles.invoiceHeader}>
            <Text style={styles.companyName}>{user?.businessName || 'BINEST'}</Text>
            {user?.address && (
              <Text style={styles.businessDetail}>{user.address}</Text>
            )}
            <View style={styles.businessContactRow}>
              {user?.mobile && (
                <Text style={styles.businessDetail}>Phone: {user.mobile}</Text>
              )}
              {user?.email && user?.mobile && (
                <Text style={styles.businessDetail}> | </Text>
              )}
              {user?.email && (
                <Text style={styles.businessDetail}>Email: {user.email}</Text>
              )}
            </View>
            <Text style={styles.invoiceTitle}>INVOICE</Text>
            <Text style={styles.invoiceNumber}>#{bill.invoiceNumber || bill.id}</Text>
            <Text style={styles.invoiceDate}>
              {new Date(bill.date).toLocaleDateString('en-IN', {
                day: 'numeric',
                month: 'long',
                year: 'numeric'
              })}
            </Text>
            {bill.time && (
              <Text style={styles.invoiceTime}>Time: {bill.time}</Text>
            )}
          </View>

          <View style={styles.customerSection}>
            <Text style={styles.sectionLabel}>Bill To (Customer):</Text>
            <View style={styles.customerInfo}>
              <View style={styles.customerAvatar}>
                <Text style={styles.customerInitial}>
                  {(bill.customer_name || bill.customerName || 'C').charAt(0).toUpperCase()}
                </Text>
              </View>
              <View style={styles.customerDetails}>
                <Text style={styles.customerName}>{bill.customer_name || bill.customerName || 'Customer'}</Text>
                <Text style={styles.customerId}>Customer ID: {bill.customer_id || bill.customerId || 'N/A'}</Text>
                {(bill.customer_mobile || bill.customerMobile) && (
                  <Text style={styles.customerDetail}>Phone: {bill.customer_mobile || bill.customerMobile}</Text>
                )}
                {(bill.customer_email || bill.customerEmail) && (
                  <Text style={styles.customerDetail}>Email: {bill.customer_email || bill.customerEmail}</Text>
                )}
                {(bill.customer_address || bill.customerAddress) && (
                  <Text style={styles.customerDetail}>Address: {bill.customer_address || bill.customerAddress}</Text>
                )}
              </View>
            </View>
          </View>

          <View style={styles.itemsSection}>
            <Text style={styles.sectionLabel}>Items:</Text>
            <View style={styles.itemsTable}>
              <View style={styles.tableHeader}>
                <Text style={[styles.tableHeaderText, { flex: 2 }]}>Item</Text>
                <Text style={[styles.tableHeaderText, { flex: 1, textAlign: 'center' }]}>Qty</Text>
                <Text style={[styles.tableHeaderText, { flex: 1, textAlign: 'right' }]}>Price</Text>
                <Text style={[styles.tableHeaderText, { flex: 1, textAlign: 'right' }]}>Total</Text>
              </View>
              {safeItems.map((item, index) => (
                <View key={index} style={styles.tableRow}>
                  <Text style={[styles.tableCell, { flex: 2 }]}>{item.name || 'Item'}</Text>
                  <Text style={[styles.tableCell, { flex: 1, textAlign: 'center' }]}>{item.quantity || 0} {item.unit || 'Nos'}</Text>
                  <Text style={[styles.tableCell, { flex: 1, textAlign: 'right' }]}>₹{item.price || 0}</Text>
                  <Text style={[styles.tableCell, { flex: 1, textAlign: 'right' }]}>₹{(item.price || 0) * (item.quantity || 0)}</Text>
                </View>
              ))}
            </View>
          </View>

          <View style={styles.totalsSection}>
            <View style={styles.totalRow}>
              <Text style={styles.totalLabel}>Subtotal</Text>
              <Text style={styles.totalValue}>₹{parseFloat(subtotal || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</Text>
            </View>
            <View style={styles.totalRow}>
              <Text style={styles.totalLabel}>Discount</Text>
              <Text style={styles.totalValue}>-₹{parseFloat(bill.discount || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</Text>
            </View>
            <View style={styles.totalRow}>
              <Text style={styles.totalLabel}>Tax</Text>
              <Text style={styles.totalValue}>₹{parseFloat(bill.tax || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</Text>
            </View>
            <View style={[styles.totalRow, styles.grandTotalRow]}>
              <Text style={styles.grandTotalLabel}>Total</Text>
              <Text style={styles.grandTotalValue}>₹{parseFloat(bill.total || bill.grandTotal || bill.grand_total || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</Text>
            </View>
          </View>

          <View style={styles.paymentSection}>
            <Text style={styles.paymentLabel}>Payment Mode:</Text>
            <Text style={styles.paymentValue}>{bill.paymentMode || bill.payment_mode || 'Cash'}</Text>
          </View>

          {(bill.paymentMode || bill.payment_mode || '').toLowerCase() === 'due' && bill.due_date && (
            <View style={styles.dueDateSection}>
              <View style={styles.dueDateRow}>
                <Ionicons name="calendar" size={20} color="#DC2626" />
                <Text style={styles.dueDateLabel}>Due Date</Text>
              </View>
              <Text style={styles.dueDateValue}>
                {new Date(bill.due_date).toLocaleDateString('en-IN', { day: 'numeric', month: 'long', year: 'numeric' })}
              </Text>
              <Text style={styles.dueDateNote}>Payment must be made on or before this date</Text>
            </View>
          )}
        </View>

        <View style={styles.actions}>
          <TouchableOpacity style={styles.actionButton} onPress={() => navigation.navigate('Billing', { editBill: bill })}>
            <LinearGradient
              colors={['#F59E0B', '#D97706']}
              style={styles.actionGradient}
            >
              <Ionicons name="create" size={20} color="#FFFFFF" />
              <Text style={styles.actionText}>Edit</Text>
            </LinearGradient>
          </TouchableOpacity>

          <TouchableOpacity style={styles.actionButton} onPress={downloadPDF}>
            <LinearGradient
              colors={['#2563EB', '#1E40AF']}
              style={styles.actionGradient}
            >
              <Ionicons name="download" size={20} color="#FFFFFF" />
              <Text style={styles.actionText}>PDF</Text>
            </LinearGradient>
          </TouchableOpacity>

          <TouchableOpacity style={styles.actionButton} onPress={printInvoice}>
            <LinearGradient
              colors={['#10B981', '#059669']}
              style={styles.actionGradient}
            >
              <Ionicons name="print" size={20} color="#FFFFFF" />
              <Text style={styles.actionText}>Print</Text>
            </LinearGradient>
          </TouchableOpacity>
        </View>

        <View style={styles.footer}>
          <Text style={styles.footerText}>Thank you for your business!</Text>
          <Text style={styles.footerSubtext}>Generated by BINEST App</Text>
        </View>
      </ScrollView>
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
    color: '#FFFFFF',
  },
  content: {
    flex: 1,
    padding: 20,
  },
  invoiceCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    padding: 20,
    marginBottom: 20,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  invoiceHeader: {
    alignItems: 'center',
    paddingBottom: 20,
    borderBottomWidth: 2,
    borderBottomColor: '#E2E8F0',
    marginBottom: 20,
  },
  companyName: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#2563EB',
    marginBottom: 8,
  },
  invoiceTitle: {
    fontSize: 18,
    fontWeight: '600',
    color: '#1E293B',
    marginBottom: 8,
  },
  invoiceNumber: {
    fontSize: 14,
    color: '#64748B',
    marginBottom: 4,
  },
  invoiceDate: {
    fontSize: 14,
    color: '#64748B',
  },
  invoiceTime: {
    fontSize: 12,
    color: '#64748B',
    marginTop: 4,
  },
  customerSection: {
    marginBottom: 20,
  },
  sectionLabel: {
    fontSize: 14,
    fontWeight: '600',
    color: '#64748B',
    marginBottom: 12,
  },
  customerInfo: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  customerAvatar: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: '#2563EB',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 12,
  },
  customerInitial: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  customerName: {
    fontSize: 16,
    fontWeight: '600',
    color: '#1E293B',
    marginBottom: 4,
  },
  customerId: {
    fontSize: 12,
    color: '#64748B',
  },
  businessDetail: {
    fontSize: 12,
    color: '#64748B',
    marginTop: 2,
  },
  businessContactRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'center',
    marginBottom: 8,
  },
  customerDetails: {
    flex: 1,
  },
  customerDetail: {
    fontSize: 12,
    color: '#64748B',
    marginTop: 4,
  },
  itemsSection: {
    marginBottom: 20,
  },
  itemsTable: {
    borderWidth: 1,
    borderColor: '#E2E8F0',
    borderRadius: 8,
    overflow: 'hidden',
  },
  tableHeader: {
    flexDirection: 'row',
    backgroundColor: '#2563EB',
    padding: 12,
  },
  tableHeaderText: {
    fontSize: 12,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  tableRow: {
    flexDirection: 'row',
    padding: 12,
    borderBottomWidth: 1,
    borderBottomColor: '#E2E8F0',
  },
  tableCell: {
    fontSize: 14,
    color: '#1E293B',
  },
  totalsSection: {
    paddingTop: 16,
    borderTopWidth: 1,
    borderTopColor: '#E2E8F0',
  },
  totalRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 8,
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
  grandTotalRow: {
    marginTop: 8,
    paddingTop: 12,
    borderTopWidth: 2,
    borderTopColor: '#E2E8F0',
  },
  grandTotalLabel: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#1E293B',
  },
  grandTotalValue: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#2563EB',
  },
  paymentSection: {
    marginTop: 20,
    padding: 16,
    backgroundColor: '#F1F5F9',
    borderRadius: 8,
  },
  paymentLabel: {
    fontSize: 12,
    fontWeight: '600',
    color: '#64748B',
    marginBottom: 4,
  },
  paymentValue: {
    fontSize: 14,
    fontWeight: '600',
    color: '#1E293B',
  },
  dueDateSection: {
    marginTop: 12,
    padding: 16,
    backgroundColor: '#FEF2F2',
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#FECACA',
  },
  dueDateRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 8,
  },
  dueDateLabel: {
    fontSize: 14,
    fontWeight: 'bold',
    color: '#DC2626',
    marginLeft: 8,
  },
  dueDateValue: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#DC2626',
    marginBottom: 4,
  },
  dueDateNote: {
    fontSize: 11,
    color: '#64748B',
  },
  actions: {
    flexDirection: 'row',
    marginBottom: 20,
  },
  actionButton: {
    flex: 1,
    marginHorizontal: 4,
  },
  actionGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    padding: 16,
    borderRadius: 12,
  },
  actionText: {
    color: '#FFFFFF',
    fontSize: 14,
    fontWeight: 'bold',
    marginLeft: 8,
  },
  footer: {
    alignItems: 'center',
    paddingVertical: 20,
  },
  footerText: {
    fontSize: 14,
    color: '#64748B',
    marginBottom: 4,
  },
  footerSubtext: {
    fontSize: 12,
    color: '#94A3B8',
  },
  sellerInfo: {
    marginTop: 8,
  },
  sellerName: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#1E293B',
    marginBottom: 4,
  },
  sellerDetail: {
    fontSize: 14,
    color: '#64748B',
    marginTop: 2,
  },
  errorContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
  },
  errorText: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#1E293B',
    marginTop: 20,
    marginBottom: 30,
  },
  backButton: {
    backgroundColor: '#2563EB',
    paddingHorizontal: 30,
    paddingVertical: 12,
    borderRadius: 8,
  },
  backButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: 'bold',
  },
});

export default InvoiceScreen;
