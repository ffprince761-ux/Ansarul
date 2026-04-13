import React, { useContext, useState, useMemo } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, Alert, Modal, TextInput } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import { AppContext } from '../../context/AppContext';
import { getDuePayments } from '../../services/api';
import useTranslation from '../../i18n/useTranslation';

const CustomerDetailsScreen = ({ route, navigation }) => {
  const { customer } = route.params;
  const { bills, deleteBill, addDuePayment, deleteCustomer } = useContext(AppContext);
  const { t } = useTranslation();
  const [activeTab, setActiveTab] = useState('due');
  const [showPaymentModal, setShowPaymentModal] = useState(false);
  const [selectedBill, setSelectedBill] = useState(null);
  const [paymentAmount, setPaymentAmount] = useState('');
  const [paymentNote, setPaymentNote] = useState('');
  const [showHistoryModal, setShowHistoryModal] = useState(false);
  const [paymentHistory, setPaymentHistory] = useState([]);
  const [historyBill, setHistoryBill] = useState(null);

  const customerBills = useMemo(() => {
    return (bills || []).filter(bill =>
      bill.customerId === customer.id ||
      bill.customer_id === customer.id ||
      String(bill.customerId) === String(customer.id) ||
      String(bill.customer_id) === String(customer.id)
    ).sort((a, b) => new Date(b.date || b.created_at) - new Date(a.date || a.created_at));
  }, [bills, customer.id]);

  const paidBills = useMemo(() => {
    return customerBills.filter(b => {
      const mode = (b.payment_mode || b.paymentMode || '').toLowerCase();
      if (mode === 'due') return b.due_status === 'paid';
      return true; // non-due bills are always "paid"
    });
  }, [customerBills]);

  const dueBills = useMemo(() => {
    return customerBills.filter(b => {
      const mode = (b.payment_mode || b.paymentMode || '').toLowerCase();
      return mode === 'due' && b.due_status !== 'paid';
    });
  }, [customerBills]);

  const stats = useMemo(() => {
    const totalPurchases = customerBills.reduce((s, b) => s + (parseFloat(b.grandTotal || b.grand_total || b.total) || 0), 0);
    const totalDue = dueBills.reduce((s, b) => s + (parseFloat(b.grand_total || b.grandTotal || b.total) || 0), 0);
    const totalDuePaid = dueBills.reduce((s, b) => s + (parseFloat(b.paid_amount) || 0), 0);
    const totalRemaining = totalDue - totalDuePaid;
    return {
      totalPurchases: Math.round(totalPurchases * 100) / 100,
      totalRemaining: Math.round(totalRemaining * 100) / 100,
      totalDuePaid: Math.round(totalDuePaid * 100) / 100,
    };
  }, [customerBills, dueBills]);

  const activeBills = activeTab === 'due' ? dueBills : paidBills;

  const handleDeleteBill = (bill) => {
    Alert.alert('Delete Bill', `Delete Invoice #${bill.invoice_number || bill.invoiceNumber || (bill.id ? String(bill.id).slice(-6) : 'N/A')}?`, [
      { text: 'Cancel', style: 'cancel' },
      { text: 'Delete', style: 'destructive', onPress: async () => {
        const success = await deleteBill(bill.id);
        if (success) Alert.alert('Success', 'Bill deleted');
        else Alert.alert('Error', 'Failed to delete');
      }}
    ]);
  };

  const openPaymentModal = (bill) => {
    setSelectedBill(bill);
    setPaymentAmount('');
    setPaymentNote('');
    setShowPaymentModal(true);
  };

  const handleAddPayment = async () => {
    const amount = parseFloat(paymentAmount);
    if (!amount || amount <= 0) { Alert.alert('Error', 'Enter valid amount'); return; }
    const total = parseFloat(selectedBill.grand_total || selectedBill.grandTotal || selectedBill.total) || 0;
    const paid = parseFloat(selectedBill.paid_amount) || 0;
    const remaining = total - paid;
    if (amount > remaining) { Alert.alert('Error', `Max ₹${remaining.toLocaleString('en-IN')}`); return; }

    const result = await addDuePayment(selectedBill.id, amount, paymentNote);
    if (result?.success) {
      setShowPaymentModal(false);
      Alert.alert('Payment Recorded', result.due_status === 'paid' ? 'Fully Paid! Bill moved to Paid tab.' : `₹${amount} recorded. Remaining: ₹${result.remaining.toLocaleString('en-IN')}`);
    } else {
      Alert.alert('Error', result?.error || 'Failed');
    }
  };

  const handleFullPay = (bill) => {
    const total = parseFloat(bill.grand_total || bill.grandTotal || bill.total) || 0;
    const paid = parseFloat(bill.paid_amount) || 0;
    const remaining = total - paid;
    Alert.alert('Full Payment', `Pay remaining ₹${remaining.toLocaleString('en-IN')}?`, [
      { text: 'Cancel', style: 'cancel' },
      { text: 'Full Paid', onPress: async () => {
        const r = await addDuePayment(bill.id, remaining, 'Full payment');
        if (r?.success) Alert.alert('Done', 'Fully paid! Bill moved to Paid tab.');
      }}
    ]);
  };

  const openHistory = async (bill) => {
    setHistoryBill(bill);
    setPaymentHistory([]);
    setShowHistoryModal(true);
    const res = await getDuePayments(bill.id);
    if (res?.success) setPaymentHistory(res.payments || []);
  };

  const formatDate = (d) => {
    if (!d) return 'N/A';
    try { return new Date(d).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' }); }
    catch { return d; }
  };

  const handleDeleteCustomer = () => {
    Alert.alert(
      'Delete Customer',
      `"${customer.name}" ${t('deleteConfirm')}`,
      [
        { text: 'Cancel', style: 'cancel' },
        { text: 'Delete', style: 'destructive', onPress: async () => {
          await deleteCustomer(customer.id);
          navigation.goBack();
        }}
      ]
    );
  };

  const getStatusColor = (s) => s === 'paid' ? '#10B981' : s === 'partial' ? '#F59E0B' : '#DC2626';
  const getStatusLabel = (s) => s === 'paid' ? 'Paid' : s === 'partial' ? 'Partial' : 'Unpaid';

  return (
    <View style={styles.container}>
      <ScrollView>
        <LinearGradient colors={['#2563EB', '#1E40AF']} style={styles.header}>
          <View style={{ position: 'absolute', top: 20, right: 20, flexDirection: 'row', gap: 8 }}>
            <TouchableOpacity style={styles.headerActionBtn} onPress={() => navigation.navigate('EditCustomer', { customer })}>
              <Ionicons name="create-outline" size={20} color="#FFFFFF" />
            </TouchableOpacity>
            <TouchableOpacity style={[styles.headerActionBtn, { backgroundColor: 'rgba(239,68,68,0.3)' }]} onPress={handleDeleteCustomer}>
              <Ionicons name="trash-outline" size={20} color="#FFFFFF" />
            </TouchableOpacity>
          </View>
          <View style={styles.avatar}>
            <Text style={styles.avatarText}>{customer.name.charAt(0).toUpperCase()}</Text>
          </View>
          <Text style={styles.name}>{customer.name}</Text>
          <Text style={styles.mobile}>{customer.mobile}</Text>
        </LinearGradient>

        <View style={styles.content}>
          <View style={styles.infoCard}>
            <View style={styles.infoRow}><Ionicons name="call-outline" size={20} color="#64748B" /><Text style={styles.infoText}>{customer.mobile || 'No mobile'}</Text></View>
            <View style={styles.infoRow}><Ionicons name="mail-outline" size={20} color="#64748B" /><Text style={styles.infoText}>{customer.email || 'No email'}</Text></View>
            <View style={styles.infoRow}><Ionicons name="location-outline" size={20} color="#64748B" /><Text style={styles.infoText}>{customer.address || 'No address'}</Text></View>
          </View>

          {/* Stats Row */}
          <View style={styles.statsRow}>
            <View style={styles.statBox}>
              <Ionicons name="receipt" size={20} color="#2563EB" />
              <Text style={styles.statVal}>{customerBills.length}</Text>
              <Text style={styles.statLbl}>Bills</Text>
            </View>
            <View style={styles.statBox}>
              <Ionicons name="cash" size={20} color="#10B981" />
              <Text style={styles.statVal}>₹{stats.totalPurchases.toLocaleString('en-IN')}</Text>
              <Text style={styles.statLbl}>Total</Text>
            </View>
            <View style={[styles.statBox, stats.totalRemaining > 0 && { borderWidth: 2, borderColor: '#DC2626' }]}>
              <Ionicons name="time" size={20} color="#DC2626" />
              <Text style={[styles.statVal, { color: '#DC2626' }]}>₹{stats.totalRemaining.toLocaleString('en-IN')}</Text>
              <Text style={styles.statLbl}>Due</Text>
            </View>
          </View>

          {/* Tabs */}
          <View style={styles.tabRow}>
            <TouchableOpacity style={[styles.tab, activeTab === 'due' && styles.tabActive]} onPress={() => setActiveTab('due')}>
              <Ionicons name="time" size={16} color={activeTab === 'due' ? '#FFF' : '#DC2626'} />
              <Text style={[styles.tabText, activeTab === 'due' && styles.tabTextActive]}>Due ({dueBills.length})</Text>
            </TouchableOpacity>
            <TouchableOpacity style={[styles.tab, activeTab === 'paid' && styles.tabActivePaid]} onPress={() => setActiveTab('paid')}>
              <Ionicons name="checkmark-circle" size={16} color={activeTab === 'paid' ? '#FFF' : '#10B981'} />
              <Text style={[styles.tabText, activeTab === 'paid' && styles.tabTextActive]}>Paid ({paidBills.length})</Text>
            </TouchableOpacity>
          </View>

          {/* Bill List */}
          {activeBills.length > 0 ? activeBills.map((bill) => {
            const total = parseFloat(bill.grand_total || bill.grandTotal || bill.total) || 0;
            const paid = parseFloat(bill.paid_amount) || 0;
            const remaining = total - paid;
            const isDue = (bill.payment_mode || bill.paymentMode || '').toLowerCase() === 'due';
            const status = bill.due_status || (isDue ? 'unpaid' : 'paid');
            const progress = total > 0 ? (paid / total) * 100 : 100;

            return (
              <View key={bill.id} style={styles.billCard}>
                <TouchableOpacity onPress={() => navigation.navigate('Invoice', { bill })} activeOpacity={0.7}>
                  <View style={styles.billHeader}>
                    <View>
                      <Text style={styles.billNumber}>Invoice #{bill.invoice_number || bill.invoiceNumber || String(bill.id).slice(-6)}</Text>
                      <Text style={styles.billDate}>{formatDate(bill.date || bill.created_at)}</Text>
                      {bill.due_date && isDue && status !== 'paid' && (() => {
                        const dd = new Date(bill.due_date); dd.setHours(0,0,0,0);
                        const now = new Date(); now.setHours(0,0,0,0);
                        const isOverdue = dd < now;
                        const isToday = dd.getTime() === now.getTime();
                        return (
                          <View style={{ flexDirection: 'row', alignItems: 'center', marginTop: 3 }}>
                            <Ionicons name="calendar" size={11} color={isOverdue ? '#DC2626' : isToday ? '#F59E0B' : '#64748B'} />
                            <Text style={{ fontSize: 11, marginLeft: 4, color: isOverdue ? '#DC2626' : isToday ? '#F59E0B' : '#64748B', fontWeight: isOverdue || isToday ? 'bold' : 'normal' }}>
                              Due: {formatDate(bill.due_date)}{isOverdue ? ' (OVERDUE!)' : isToday ? ' (TODAY!)' : ''}
                            </Text>
                          </View>
                        );
                      })()}
                    </View>
                    <View style={{ alignItems: 'flex-end' }}>
                      <Text style={[styles.billAmount, { color: status === 'paid' ? '#10B981' : '#DC2626' }]}>₹{total.toLocaleString('en-IN')}</Text>
                      <View style={[styles.statusBadge, { backgroundColor: `${getStatusColor(status)}15` }]}>
                        <Text style={[styles.statusText, { color: getStatusColor(status) }]}>{bill.paymentMode || bill.payment_mode || 'Cash'} • {getStatusLabel(status)}</Text>
                      </View>
                    </View>
                  </View>

                  {isDue && status !== 'paid' && (
                    <View style={styles.dueInfo}>
                      <View style={styles.dueInfoRow}>
                        <Text style={styles.dueInfoLabel}>Paid: ₹{paid.toLocaleString('en-IN')}</Text>
                        <Text style={[styles.dueInfoLabel, { color: '#DC2626', fontWeight: 'bold' }]}>Remaining: ₹{remaining.toLocaleString('en-IN')}</Text>
                      </View>
                      <View style={styles.progressBar}>
                        <View style={[styles.progressFill, { width: `${Math.min(progress, 100)}%`, backgroundColor: getStatusColor(status) }]} />
                      </View>
                    </View>
                  )}
                </TouchableOpacity>

                <View style={styles.billActions}>
                  {isDue && status !== 'paid' && (
                    <>
                      <TouchableOpacity style={styles.payBtn} onPress={() => openPaymentModal(bill)}>
                        <Ionicons name="cash" size={14} color="#FFF" />
                        <Text style={styles.payBtnText}>Add Payment</Text>
                      </TouchableOpacity>
                      <TouchableOpacity style={styles.fullBtn} onPress={() => handleFullPay(bill)}>
                        <Ionicons name="checkmark-circle" size={14} color="#10B981" />
                        <Text style={styles.fullBtnText}>Full Pay</Text>
                      </TouchableOpacity>
                    </>
                  )}
                  {isDue && (
                    <TouchableOpacity style={styles.historyBtn} onPress={() => openHistory(bill)}>
                      <Ionicons name="time" size={14} color="#2563EB" />
                    </TouchableOpacity>
                  )}
                  <TouchableOpacity style={styles.deleteBtn} onPress={() => handleDeleteBill(bill)}>
                    <Ionicons name="trash-outline" size={14} color="#EF4444" />
                  </TouchableOpacity>
                </View>
              </View>
            );
          }) : (
            <View style={styles.emptyState}>
              <Ionicons name={activeTab === 'due' ? 'checkmark-circle-outline' : 'receipt-outline'} size={48} color="#CBD5E1" />
              <Text style={styles.emptyText}>{activeTab === 'due' ? 'No pending dues!' : 'No paid bills yet'}</Text>
            </View>
          )}

          <TouchableOpacity style={styles.createBillButton} onPress={() => navigation.navigate('Home', { screen: 'Billing' })}>
            <Ionicons name="add-circle" size={20} color="#FFFFFF" style={{ marginRight: 8 }} />
            <Text style={styles.createBillButtonText}>Create New Bill</Text>
          </TouchableOpacity>
        </View>
      </ScrollView>

      {/* Payment Modal */}
      <Modal visible={showPaymentModal} transparent animationType="slide" onRequestClose={() => setShowPaymentModal(false)}>
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <View style={styles.modalHead}><Text style={styles.modalTitle}>Add Payment</Text><TouchableOpacity onPress={() => setShowPaymentModal(false)}><Ionicons name="close" size={24} color="#64748B" /></TouchableOpacity></View>
            {selectedBill && (() => {
              const t = parseFloat(selectedBill.grand_total || selectedBill.grandTotal || selectedBill.total) || 0;
              const p = parseFloat(selectedBill.paid_amount) || 0;
              const r = t - p;
              return (
                <>
                  <View style={styles.modalInfo}>
                    <View style={styles.modalInfoRow}><Text style={styles.mLabel}>Total</Text><Text style={styles.mVal}>₹{t.toLocaleString('en-IN')}</Text></View>
                    <View style={styles.modalInfoRow}><Text style={styles.mLabel}>Paid</Text><Text style={[styles.mVal, { color: '#10B981' }]}>₹{p.toLocaleString('en-IN')}</Text></View>
                    <View style={[styles.modalInfoRow, { borderTopWidth: 1, borderTopColor: '#E2E8F0', paddingTop: 8 }]}><Text style={[styles.mLabel, { fontWeight: 'bold' }]}>Remaining</Text><Text style={[styles.mVal, { color: '#DC2626', fontWeight: 'bold' }]}>₹{r.toLocaleString('en-IN')}</Text></View>
                  </View>
                  <Text style={styles.inputLabel}>Amount (₹)</Text>
                  <TextInput style={styles.input} placeholder="Enter amount..." keyboardType="numeric" value={paymentAmount} onChangeText={setPaymentAmount} autoFocus />
                  <Text style={styles.inputLabel}>Note (optional)</Text>
                  <TextInput style={[styles.input, { height: 50 }]} placeholder="e.g. Cash..." value={paymentNote} onChangeText={setPaymentNote} multiline />
                  <View style={styles.quickRow}>
                    {[500, 1000, 2000, 5000].filter(a => a <= r).concat(r > 0 && ![500,1000,2000,5000].includes(Math.round(r)) ? [Math.round(r)] : []).sort((a, b) => a - b).slice(0, 4).map(a => (
                      <TouchableOpacity key={a} style={styles.quickBtn} onPress={() => setPaymentAmount(String(a))}>
                        <Text style={styles.quickBtnText}>₹{a.toLocaleString('en-IN')}</Text>
                      </TouchableOpacity>
                    ))}
                  </View>
                  <TouchableOpacity style={styles.submitBtn} onPress={handleAddPayment}><Text style={styles.submitText}>Record Payment</Text></TouchableOpacity>
                </>
              );
            })()}
          </View>
        </View>
      </Modal>

      {/* History Modal */}
      <Modal visible={showHistoryModal} transparent animationType="slide" onRequestClose={() => setShowHistoryModal(false)}>
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <View style={styles.modalHead}><Text style={styles.modalTitle}>Payment History</Text><TouchableOpacity onPress={() => setShowHistoryModal(false)}><Ionicons name="close" size={24} color="#64748B" /></TouchableOpacity></View>
            {historyBill && <Text style={styles.modalSub}>Invoice #{historyBill.invoice_number || historyBill.invoiceNumber || String(historyBill.id).slice(-6)}</Text>}
            <ScrollView style={{ maxHeight: 350 }}>
              {paymentHistory.length > 0 ? paymentHistory.map((p, i) => (
                <View key={p.id || i} style={styles.histItem}>
                  <View style={styles.histDot} />
                  <View style={styles.histBody}>
                    <View style={{ flexDirection: 'row', justifyContent: 'space-between' }}><Text style={styles.histAmt}>₹{(parseFloat(p.amount) || 0).toLocaleString('en-IN')}</Text><Text style={styles.histDate}>{formatDate(p.payment_date)}</Text></View>
                    {p.note ? <Text style={styles.histNote}>{p.note}</Text> : null}
                  </View>
                </View>
              )) : (
                <View style={{ padding: 30, alignItems: 'center' }}><Ionicons name="document-text-outline" size={40} color="#CBD5E1" /><Text style={{ color: '#94A3B8', marginTop: 8 }}>No payments yet</Text></View>
              )}
            </ScrollView>
          </View>
        </View>
      </Modal>
    </View>
  );
};

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F8FAFC' },
  header: { alignItems: 'center', paddingVertical: 40, borderBottomLeftRadius: 24, borderBottomRightRadius: 24, position: 'relative' },
  headerActionBtn: { width: 36, height: 36, borderRadius: 18, backgroundColor: 'rgba(255,255,255,0.2)', justifyContent: 'center', alignItems: 'center' },
  avatar: { width: 80, height: 80, borderRadius: 40, backgroundColor: 'rgba(255,255,255,0.2)', justifyContent: 'center', alignItems: 'center', marginBottom: 16 },
  avatarText: { fontSize: 32, fontWeight: 'bold', color: '#FFFFFF' },
  name: { fontSize: 24, fontWeight: 'bold', color: '#FFFFFF', marginBottom: 4 },
  mobile: { fontSize: 16, color: '#E0E7FF' },
  content: { padding: 20 },
  infoCard: { backgroundColor: '#FFF', borderRadius: 12, padding: 16, marginBottom: 16 },
  infoRow: { flexDirection: 'row', alignItems: 'center', paddingVertical: 10 },
  infoText: { fontSize: 14, color: '#1E293B', marginLeft: 12 },
  // Stats
  statsRow: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 16 },
  statBox: { flex: 1, backgroundColor: '#FFF', borderRadius: 12, padding: 12, alignItems: 'center', marginHorizontal: 4, elevation: 2 },
  statVal: { fontSize: 14, fontWeight: 'bold', color: '#1E293B', marginTop: 6 },
  statLbl: { fontSize: 11, color: '#94A3B8', marginTop: 2 },
  // Tabs
  tabRow: { flexDirection: 'row', marginBottom: 16, backgroundColor: '#E2E8F0', borderRadius: 12, padding: 4 },
  tab: { flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', paddingVertical: 10, borderRadius: 10 },
  tabActive: { backgroundColor: '#DC2626' },
  tabActivePaid: { backgroundColor: '#10B981' },
  tabText: { fontSize: 14, fontWeight: '600', color: '#64748B', marginLeft: 6 },
  tabTextActive: { color: '#FFFFFF' },
  // Bill Card
  billCard: { backgroundColor: '#FFF', borderRadius: 14, padding: 14, marginBottom: 10, elevation: 2 },
  billHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start' },
  billNumber: { fontSize: 14, fontWeight: '600', color: '#1E293B', marginBottom: 4 },
  billDate: { fontSize: 12, color: '#94A3B8' },
  billAmount: { fontSize: 16, fontWeight: 'bold', marginBottom: 4 },
  statusBadge: { paddingHorizontal: 8, paddingVertical: 3, borderRadius: 10 },
  statusText: { fontSize: 11, fontWeight: '600' },
  dueInfo: { marginTop: 10, backgroundColor: '#FFF7ED', borderRadius: 8, padding: 8 },
  dueInfoRow: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 6 },
  dueInfoLabel: { fontSize: 12, color: '#64748B' },
  progressBar: { height: 6, backgroundColor: '#E2E8F0', borderRadius: 3, overflow: 'hidden' },
  progressFill: { height: '100%', borderRadius: 3 },
  billActions: { flexDirection: 'row', alignItems: 'center', marginTop: 10, borderTopWidth: 1, borderTopColor: '#F1F5F9', paddingTop: 10 },
  payBtn: { flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', backgroundColor: '#2563EB', paddingVertical: 8, borderRadius: 8, marginRight: 6 },
  payBtnText: { color: '#FFF', fontWeight: '600', fontSize: 12, marginLeft: 4 },
  fullBtn: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#D1FAE5', paddingVertical: 8, paddingHorizontal: 10, borderRadius: 8, marginRight: 6 },
  fullBtnText: { color: '#10B981', fontWeight: '600', fontSize: 12, marginLeft: 4 },
  historyBtn: { width: 34, height: 34, borderRadius: 17, backgroundColor: '#EFF6FF', justifyContent: 'center', alignItems: 'center', marginRight: 6 },
  deleteBtn: { width: 34, height: 34, borderRadius: 17, backgroundColor: '#FEE2E2', justifyContent: 'center', alignItems: 'center' },
  emptyState: { backgroundColor: '#FFF', borderRadius: 12, padding: 40, alignItems: 'center' },
  emptyText: { fontSize: 14, color: '#94A3B8', marginTop: 8 },
  createBillButton: { backgroundColor: '#2563EB', padding: 18, borderRadius: 12, alignItems: 'center', flexDirection: 'row', justifyContent: 'center', marginTop: 10 },
  createBillButtonText: { color: '#FFFFFF', fontSize: 16, fontWeight: 'bold' },
  // Modal
  modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'flex-end' },
  modalContent: { backgroundColor: '#FFF', borderTopLeftRadius: 24, borderTopRightRadius: 24, padding: 24, maxHeight: '85%' },
  modalHead: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 },
  modalTitle: { fontSize: 20, fontWeight: 'bold', color: '#1E293B' },
  modalSub: { fontSize: 14, color: '#64748B', marginBottom: 16 },
  modalInfo: { backgroundColor: '#F8FAFC', borderRadius: 12, padding: 12, marginBottom: 16 },
  modalInfoRow: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 6 },
  mLabel: { fontSize: 14, color: '#64748B' },
  mVal: { fontSize: 14, fontWeight: '600', color: '#1E293B' },
  inputLabel: { fontSize: 14, fontWeight: '600', color: '#1E293B', marginBottom: 6 },
  input: { borderWidth: 1, borderColor: '#E2E8F0', borderRadius: 12, padding: 14, fontSize: 16, color: '#1E293B', marginBottom: 12, backgroundColor: '#F8FAFC' },
  quickRow: { flexDirection: 'row', flexWrap: 'wrap', marginBottom: 16 },
  quickBtn: { backgroundColor: '#EFF6FF', paddingHorizontal: 16, paddingVertical: 8, borderRadius: 20, marginRight: 8, marginBottom: 8 },
  quickBtnText: { color: '#2563EB', fontWeight: '600', fontSize: 13 },
  submitBtn: { backgroundColor: '#2563EB', paddingVertical: 14, borderRadius: 12, alignItems: 'center' },
  submitText: { color: '#FFF', fontWeight: 'bold', fontSize: 16 },
  // History
  histItem: { flexDirection: 'row', marginBottom: 12 },
  histDot: { width: 10, height: 10, borderRadius: 5, backgroundColor: '#10B981', marginTop: 5, marginRight: 12 },
  histBody: { flex: 1, borderBottomWidth: 1, borderBottomColor: '#F1F5F9', paddingBottom: 12 },
  histAmt: { fontSize: 16, fontWeight: 'bold', color: '#10B981' },
  histDate: { fontSize: 13, color: '#64748B' },
  histNote: { fontSize: 12, color: '#94A3B8', marginTop: 4 },
});

export default CustomerDetailsScreen;
