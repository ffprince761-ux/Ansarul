import React, { useContext, useMemo, useState } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, TextInput, Alert, Linking, Modal } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { AppContext } from '../../context/AppContext';
import { getDuePayments } from '../../services/api';
import useTranslation from '../../i18n/useTranslation';

const DueScreen = ({ navigation }) => {
  const { bills, updateDueStatus, addDuePayment } = useContext(AppContext);
  const { t } = useTranslation();
  const [searchQuery, setSearchQuery] = useState('');
  const [filter, setFilter] = useState('all');
  const [selectedCustomer, setSelectedCustomer] = useState(null);
  const [showPaymentModal, setShowPaymentModal] = useState(false);
  const [selectedBill, setSelectedBill] = useState(null);
  const [paymentAmount, setPaymentAmount] = useState('');
  const [paymentNote, setPaymentNote] = useState('');
  const [showHistoryModal, setShowHistoryModal] = useState(false);
  const [paymentHistory, setPaymentHistory] = useState([]);
  const [historyBill, setHistoryBill] = useState(null);

  const dueBills = useMemo(() => {
    const safeBills = bills || [];
    return safeBills.filter(bill => {
      const mode = (bill.payment_mode || bill.paymentMode || '').toLowerCase();
      return mode === 'due';
    });
  }, [bills]);

  // Group bills by customer
  const customerProfiles = useMemo(() => {
    const map = {};
    dueBills.forEach(bill => {
      const name = bill.customer_name || bill.customerName || 'Unknown';
      const phone = bill.customer_mobile || bill.customerMobile || '';
      const key = `${name}_${phone}`;
      if (!map[key]) {
        map[key] = {
          name,
          phone,
          email: bill.customer_email || bill.customerEmail || '',
          address: bill.customer_address || bill.customerAddress || '',
          bills: [],
          totalDue: 0,
          totalPaid: 0,
          totalRemaining: 0,
          billCount: 0,
          unpaidCount: 0,
        };
      }
      const total = parseFloat(bill.grand_total || bill.grandTotal || bill.total) || 0;
      const paid = parseFloat(bill.paid_amount) || 0;
      map[key].bills.push(bill);
      map[key].totalDue += total;
      map[key].totalPaid += paid;
      map[key].totalRemaining += (total - paid);
      map[key].billCount++;
      if (bill.due_status !== 'paid') map[key].unpaidCount++;
    });
    return Object.values(map);
  }, [dueBills]);

  const filteredCustomers = useMemo(() => {
    let list = customerProfiles;
    if (filter === 'unpaid') list = list.filter(c => c.unpaidCount > 0);
    else if (filter === 'paid') list = list.filter(c => c.unpaidCount === 0);
    if (searchQuery.trim()) {
      const q = searchQuery.toLowerCase();
      list = list.filter(c => c.name.toLowerCase().includes(q) || c.phone.includes(q));
    }
    return list.sort((a, b) => b.totalRemaining - a.totalRemaining);
  }, [customerProfiles, filter, searchQuery]);

  const stats = useMemo(() => {
    const totalDue = customerProfiles.reduce((s, c) => s + c.totalDue, 0);
    const totalPaid = customerProfiles.reduce((s, c) => s + c.totalPaid, 0);
    const totalPending = customerProfiles.reduce((s, c) => s + c.totalRemaining, 0);
    const unpaidCustomers = customerProfiles.filter(c => c.unpaidCount > 0).length;
    return { totalDue, totalPaid, totalPending, unpaidCustomers, totalCustomers: customerProfiles.length };
  }, [customerProfiles]);

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
      Alert.alert('Payment Recorded', result.due_status === 'paid' ? 'Fully Paid!' : `₹${amount} recorded. Remaining: ₹${result.remaining.toLocaleString('en-IN')}`);
    } else {
      Alert.alert('Error', result?.error || 'Failed');
    }
  };

  const handleMarkFullPaid = (bill) => {
    const total = parseFloat(bill.grand_total || bill.grandTotal || bill.total) || 0;
    const paid = parseFloat(bill.paid_amount) || 0;
    const remaining = total - paid;
    Alert.alert('Full Payment', `Pay remaining ₹${remaining.toLocaleString('en-IN')}?`, [
      { text: 'Cancel', style: 'cancel' },
      { text: 'Full Paid', onPress: async () => {
        const r = await addDuePayment(bill.id, remaining, 'Full payment');
        if (r?.success) Alert.alert('Done', 'Fully paid!');
      }}
    ]);
  };

  const openPaymentHistory = async (bill) => {
    setHistoryBill(bill);
    setPaymentHistory([]);
    setShowHistoryModal(true);
    const res = await getDuePayments(bill.id);
    if (res?.success) setPaymentHistory(res.payments || []);
  };

  const handleCall = (phone) => { if (phone) Linking.openURL(`tel:${phone}`); };

  const formatDate = (d) => {
    if (!d) return 'N/A';
    try { return new Date(d).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' }); }
    catch { return d; }
  };

  const parseItems = (items) => {
    if (!items) return [];
    if (Array.isArray(items)) return items;
    try { return JSON.parse(items); } catch { return []; }
  };

  const getStatusColor = (s) => s === 'paid' ? '#10B981' : s === 'partial' ? '#F59E0B' : '#DC2626';
  const getStatusLabel = (s) => s === 'paid' ? 'Paid' : s === 'partial' ? 'Partial' : 'Unpaid';

  // ========== CUSTOMER PROFILE VIEW ==========
  if (selectedCustomer) {
    const cust = selectedCustomer;
    const progress = cust.totalDue > 0 ? (cust.totalPaid / cust.totalDue) * 100 : 0;

    return (
      <SafeAreaView style={styles.container}>
        <LinearGradient colors={['#1E40AF', '#2563EB']} style={styles.header}>
          <View style={styles.headerRow}>
            <TouchableOpacity onPress={() => setSelectedCustomer(null)} style={styles.backButton}>
              <Ionicons name="arrow-back" size={24} color="#FFFFFF" />
            </TouchableOpacity>
            <Text style={styles.headerTitle}>Customer Profile</Text>
            {cust.phone ? (
              <TouchableOpacity onPress={() => handleCall(cust.phone)} style={styles.backButton}>
                <Ionicons name="call" size={20} color="#FFFFFF" />
              </TouchableOpacity>
            ) : <View style={{ width: 40 }} />}
          </View>

          <View style={styles.profileInfo}>
            <View style={styles.profileAvatar}>
              <Text style={styles.profileInitial}>{cust.name.charAt(0).toUpperCase()}</Text>
            </View>
            <View style={{ flex: 1 }}>
              <Text style={styles.profileName}>{cust.name}</Text>
              {cust.phone ? <Text style={styles.profilePhone}>{cust.phone}</Text> : null}
              {cust.address ? <Text style={styles.profileAddress}>{cust.address}</Text> : null}
            </View>
          </View>

          <View style={styles.profileStats}>
            <View style={styles.profileStatBox}>
              <Text style={styles.profileStatValue}>₹{Math.round(cust.totalRemaining).toLocaleString('en-IN')}</Text>
              <Text style={styles.profileStatLabel}>Remaining</Text>
            </View>
            <View style={[styles.profileStatBox, styles.profileStatMiddle]}>
              <Text style={styles.profileStatValue}>₹{Math.round(cust.totalPaid).toLocaleString('en-IN')}</Text>
              <Text style={styles.profileStatLabel}>Paid</Text>
            </View>
            <View style={styles.profileStatBox}>
              <Text style={styles.profileStatValue}>₹{Math.round(cust.totalDue).toLocaleString('en-IN')}</Text>
              <Text style={styles.profileStatLabel}>Total Due</Text>
            </View>
          </View>

          <View style={styles.profileProgress}>
            <View style={[styles.profileProgressFill, { width: `${Math.min(progress, 100)}%` }]} />
          </View>
          <Text style={styles.profileProgressText}>{Math.round(progress)}% paid — {cust.billCount} bills, {cust.unpaidCount} pending</Text>
        </LinearGradient>

        <ScrollView style={styles.content} showsVerticalScrollIndicator={false}>
          <Text style={styles.sectionTitle}>All Due Bills ({cust.bills.length})</Text>

          {cust.bills.sort((a, b) => new Date(b.date || b.created_at) - new Date(a.date || a.created_at)).map((bill, idx) => {
            const total = parseFloat(bill.grand_total || bill.grandTotal || bill.total) || 0;
            const paid = parseFloat(bill.paid_amount) || 0;
            const remaining = total - paid;
            const status = bill.due_status || 'unpaid';
            const billItems = parseItems(bill.items);
            const billProgress = total > 0 ? (paid / total) * 100 : 0;

            return (
              <View key={bill.id || idx} style={styles.billCard}>
                <View style={styles.billHeader}>
                  <View>
                    <Text style={styles.billDate}>{formatDate(bill.date || bill.created_at)}</Text>
                    {bill.invoice_number && <Text style={styles.billInvoice}>Invoice #{bill.invoice_number}</Text>}
                    {bill.due_date && status !== 'paid' && (() => {
                      const dd = new Date(bill.due_date); dd.setHours(0,0,0,0);
                      const now = new Date(); now.setHours(0,0,0,0);
                      const isOverdue = dd < now;
                      const isToday = dd.getTime() === now.getTime();
                      return (
                        <View style={{ flexDirection: 'row', alignItems: 'center', marginTop: 4 }}>
                          <Ionicons name="calendar" size={12} color={isOverdue ? '#DC2626' : isToday ? '#F59E0B' : '#64748B'} />
                          <Text style={{ fontSize: 11, marginLeft: 4, color: isOverdue ? '#DC2626' : isToday ? '#F59E0B' : '#64748B', fontWeight: isOverdue || isToday ? 'bold' : 'normal' }}>
                            Due: {formatDate(bill.due_date)}{isOverdue ? ' (OVERDUE!)' : isToday ? ' (TODAY!)' : ''}
                          </Text>
                        </View>
                      );
                    })()}
                  </View>
                  <View style={[styles.statusBadge, { backgroundColor: `${getStatusColor(status)}20` }]}>
                    <Text style={[styles.statusText, { color: getStatusColor(status) }]}>{getStatusLabel(status)}</Text>
                  </View>
                </View>

                <View style={styles.billAmounts}>
                  <View style={styles.billAmountRow}>
                    <Text style={styles.billAmountLabel}>Total</Text>
                    <Text style={styles.billAmountVal}>₹{total.toLocaleString('en-IN')}</Text>
                  </View>
                  <View style={styles.billAmountRow}>
                    <Text style={styles.billAmountLabel}>Paid</Text>
                    <Text style={[styles.billAmountVal, { color: '#10B981' }]}>₹{paid.toLocaleString('en-IN')}</Text>
                  </View>
                  <View style={styles.billAmountRow}>
                    <Text style={[styles.billAmountLabel, { fontWeight: 'bold' }]}>Remaining</Text>
                    <Text style={[styles.billAmountVal, { color: '#DC2626', fontWeight: 'bold' }]}>₹{remaining.toLocaleString('en-IN')}</Text>
                  </View>
                  <View style={styles.miniProgress}>
                    <View style={[styles.miniProgressFill, { width: `${Math.min(billProgress, 100)}%`, backgroundColor: getStatusColor(status) }]} />
                  </View>
                </View>

                {billItems.length > 0 && (
                  <View style={styles.billItems}>
                    {billItems.map((item, i) => (
                      <Text key={i} style={styles.billItemText}>• {item.name} x{item.quantity} — ₹{((parseFloat(item.price) || 0) * (item.quantity || 1)).toLocaleString('en-IN')}</Text>
                    ))}
                  </View>
                )}

                <View style={styles.billActions}>
                  {status !== 'paid' && (
                    <>
                      <TouchableOpacity style={styles.addPayBtn} onPress={() => openPaymentModal(bill)}>
                        <Ionicons name="cash" size={14} color="#FFF" />
                        <Text style={styles.addPayText}>{t('addPayment')}</Text>
                      </TouchableOpacity>
                      <TouchableOpacity style={styles.fullPayBtn} onPress={() => handleMarkFullPaid(bill)}>
                        <Ionicons name="checkmark-circle" size={14} color="#10B981" />
                        <Text style={styles.fullPayText}>{t('fullPay')}</Text>
                      </TouchableOpacity>
                    </>
                  )}
                  <TouchableOpacity style={styles.histBtn} onPress={() => openPaymentHistory(bill)}>
                    <Ionicons name="time" size={14} color="#2563EB" />
                    <Text style={styles.histBtnText}>History</Text>
                  </TouchableOpacity>
                </View>
              </View>
            );
          })}
        </ScrollView>

        {/* Payment Modal */}
        {renderPaymentModal()}
        {renderHistoryModal()}
      </SafeAreaView>
    );
  }

  // ========== HELPER: Render Modals ==========
  function renderPaymentModal() {
    return (
      <Modal visible={showPaymentModal} transparent animationType="slide" onRequestClose={() => setShowPaymentModal(false)}>
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>{t('addPayment')}</Text>
              <TouchableOpacity onPress={() => setShowPaymentModal(false)}><Ionicons name="close" size={24} color="#64748B" /></TouchableOpacity>
            </View>
            {selectedBill && (
              <>
                <Text style={styles.modalCustomer}>{selectedBill.customer_name || selectedBill.customerName}</Text>
                <View style={styles.modalAmountInfo}>
                  <View style={styles.modalRow}><Text style={styles.modalLabel}>Total</Text><Text style={styles.modalVal}>₹{(parseFloat(selectedBill.grand_total || selectedBill.grandTotal || selectedBill.total) || 0).toLocaleString('en-IN')}</Text></View>
                  <View style={styles.modalRow}><Text style={styles.modalLabel}>Paid</Text><Text style={[styles.modalVal, { color: '#10B981' }]}>₹{(parseFloat(selectedBill.paid_amount) || 0).toLocaleString('en-IN')}</Text></View>
                  <View style={[styles.modalRow, { borderTopWidth: 1, borderTopColor: '#E2E8F0', paddingTop: 8 }]}>
                    <Text style={[styles.modalLabel, { fontWeight: 'bold' }]}>Remaining</Text>
                    <Text style={[styles.modalVal, { color: '#DC2626', fontWeight: 'bold' }]}>₹{((parseFloat(selectedBill.grand_total || selectedBill.grandTotal || selectedBill.total) || 0) - (parseFloat(selectedBill.paid_amount) || 0)).toLocaleString('en-IN')}</Text>
                  </View>
                </View>
                <Text style={styles.inputLabel}>Amount (₹)</Text>
                <TextInput style={styles.input} placeholder="Enter amount..." keyboardType="numeric" value={paymentAmount} onChangeText={setPaymentAmount} autoFocus />
                <Text style={styles.inputLabel}>Note (optional)</Text>
                <TextInput style={[styles.input, { height: 50 }]} placeholder="e.g. Cash payment..." value={paymentNote} onChangeText={setPaymentNote} multiline />
                <View style={styles.quickAmounts}>
                  {(() => {
                    const rem = (parseFloat(selectedBill.grand_total || selectedBill.grandTotal || selectedBill.total) || 0) - (parseFloat(selectedBill.paid_amount) || 0);
                    const amts = [500, 1000, 2000, 5000].filter(a => a <= rem);
                    if (rem > 0 && !amts.includes(Math.round(rem))) amts.push(Math.round(rem));
                    return amts.sort((a, b) => a - b).slice(0, 4).map(a => (
                      <TouchableOpacity key={a} style={styles.quickBtn} onPress={() => setPaymentAmount(String(a))}>
                        <Text style={styles.quickBtnText}>₹{a.toLocaleString('en-IN')}</Text>
                      </TouchableOpacity>
                    ));
                  })()}
                </View>
                <TouchableOpacity style={styles.submitBtn} onPress={handleAddPayment}>
                  <Text style={styles.submitText}>Record Payment</Text>
                </TouchableOpacity>
              </>
            )}
          </View>
        </View>
      </Modal>
    );
  }

  function renderHistoryModal() {
    return (
      <Modal visible={showHistoryModal} transparent animationType="slide" onRequestClose={() => setShowHistoryModal(false)}>
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Payment History</Text>
              <TouchableOpacity onPress={() => setShowHistoryModal(false)}><Ionicons name="close" size={24} color="#64748B" /></TouchableOpacity>
            </View>
            {historyBill && <Text style={styles.modalCustomer}>{historyBill.customer_name || historyBill.customerName} — ₹{(parseFloat(historyBill.grand_total || historyBill.grandTotal || historyBill.total) || 0).toLocaleString('en-IN')}</Text>}
            <ScrollView style={{ maxHeight: 350 }}>
              {paymentHistory.length > 0 ? paymentHistory.map((p, i) => (
                <View key={p.id || i} style={styles.histItem}>
                  <View style={styles.histDot} />
                  <View style={styles.histContent}>
                    <View style={styles.histRow}><Text style={styles.histAmount}>₹{(parseFloat(p.amount) || 0).toLocaleString('en-IN')}</Text><Text style={styles.histDate}>{formatDate(p.payment_date)}</Text></View>
                    {p.note ? <Text style={styles.histNote}>{p.note}</Text> : null}
                  </View>
                </View>
              )) : (
                <View style={{ padding: 30, alignItems: 'center' }}>
                  <Ionicons name="document-text-outline" size={40} color="#CBD5E1" />
                  <Text style={{ color: '#94A3B8', marginTop: 8 }}>No payments yet</Text>
                </View>
              )}
            </ScrollView>
          </View>
        </View>
      </Modal>
    );
  }

  // ========== MAIN: CUSTOMER LIST VIEW ==========
  return (
    <SafeAreaView style={styles.container}>
      <LinearGradient colors={['#DC2626', '#991B1B']} style={styles.header}>
        <View style={styles.headerRow}>
          <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backButton}>
            <Ionicons name="arrow-back" size={24} color="#FFFFFF" />
          </TouchableOpacity>
          <Text style={styles.headerTitle}>{t('dueManagement')}</Text>
          <View style={{ width: 40 }} />
        </View>
        <View style={styles.statsRow}>
          <View style={styles.statBox}>
            <Text style={styles.statAmount}>₹{Math.round(stats.totalPending).toLocaleString('en-IN')}</Text>
            <Text style={styles.statLabel}>Pending</Text>
          </View>
          <View style={[styles.statBox, styles.statBoxMiddle]}>
            <Text style={styles.statAmount}>₹{Math.round(stats.totalPaid).toLocaleString('en-IN')}</Text>
            <Text style={styles.statLabel}>Received</Text>
          </View>
          <View style={styles.statBox}>
            <Text style={styles.statAmount}>{stats.unpaidCustomers}</Text>
            <Text style={styles.statLabel}>Customers</Text>
          </View>
        </View>
      </LinearGradient>

      <View style={styles.searchBar}>
        <Ionicons name="search" size={20} color="#94A3B8" />
        <TextInput style={styles.searchInput} placeholder="Search customer..." placeholderTextColor="#94A3B8" value={searchQuery} onChangeText={setSearchQuery} />
      </View>

      <View style={styles.filterRow}>
        {['all', 'unpaid', 'paid'].map(f => (
          <TouchableOpacity key={f} style={[styles.filterButton, filter === f && styles.filterButtonActive]} onPress={() => setFilter(f)}>
            <Text style={[styles.filterText, filter === f && styles.filterTextActive]}>
              {f === 'all' ? `All (${customerProfiles.length})` : f === 'unpaid' ? 'Pending' : 'Cleared'}
            </Text>
          </TouchableOpacity>
        ))}
      </View>

      <ScrollView style={styles.content} showsVerticalScrollIndicator={false}>
        {filteredCustomers.length > 0 ? filteredCustomers.map((cust, index) => {
          const progress = cust.totalDue > 0 ? (cust.totalPaid / cust.totalDue) * 100 : 0;
          const hasUnpaid = cust.unpaidCount > 0;

          return (
            <TouchableOpacity key={index} style={styles.customerCard} onPress={() => setSelectedCustomer(cust)} activeOpacity={0.7}>
              <View style={styles.customerCardHeader}>
                <View style={[styles.custAvatar, { backgroundColor: hasUnpaid ? '#FEE2E2' : '#D1FAE5' }]}>
                  <Text style={[styles.custInitial, { color: hasUnpaid ? '#DC2626' : '#10B981' }]}>{cust.name.charAt(0).toUpperCase()}</Text>
                </View>
                <View style={styles.custInfo}>
                  <Text style={styles.custName}>{cust.name}</Text>
                  <Text style={styles.custMeta}>{cust.phone || 'No phone'} • {cust.billCount} bill{cust.billCount > 1 ? 's' : ''}</Text>
                </View>
                <View style={{ alignItems: 'flex-end' }}>
                  <Text style={[styles.custAmount, { color: hasUnpaid ? '#DC2626' : '#10B981' }]}>
                    ₹{Math.round(cust.totalRemaining).toLocaleString('en-IN')}
                  </Text>
                  <Text style={styles.custSubAmount}>of ₹{Math.round(cust.totalDue).toLocaleString('en-IN')}</Text>
                </View>
              </View>
              <View style={styles.custProgress}>
                <View style={[styles.custProgressFill, { width: `${Math.min(progress, 100)}%`, backgroundColor: hasUnpaid ? (progress > 50 ? '#F59E0B' : '#DC2626') : '#10B981' }]} />
              </View>
              <View style={styles.custFooter}>
                <Text style={styles.custFooterText}>{Math.round(progress)}% paid</Text>
                {cust.unpaidCount > 0 && <Text style={styles.custPending}>{cust.unpaidCount} pending</Text>}
                <Ionicons name="chevron-forward" size={16} color="#94A3B8" />
              </View>
            </TouchableOpacity>
          );
        }) : (
          <View style={styles.emptyState}>
            <Ionicons name="people-outline" size={60} color="#CBD5E1" />
            <Text style={styles.emptyTitle}>No Due Customers</Text>
            <Text style={styles.emptyText}>Create a bill with "Due" payment to track here</Text>
          </View>
        )}
      </ScrollView>

      {renderPaymentModal()}
      {renderHistoryModal()}
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F8FAFC' },
  header: { paddingHorizontal: 20, paddingTop: 10, paddingBottom: 20, borderBottomLeftRadius: 24, borderBottomRightRadius: 24 },
  headerRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: 16 },
  backButton: { width: 40, height: 40, borderRadius: 20, backgroundColor: 'rgba(255,255,255,0.2)', justifyContent: 'center', alignItems: 'center' },
  headerTitle: { fontSize: 20, fontWeight: 'bold', color: '#FFFFFF' },
  statsRow: { flexDirection: 'row', justifyContent: 'space-between' },
  statBox: { flex: 1, alignItems: 'center' },
  statBoxMiddle: { borderLeftWidth: 1, borderRightWidth: 1, borderColor: 'rgba(255,255,255,0.3)' },
  statAmount: { fontSize: 16, fontWeight: 'bold', color: '#FFFFFF' },
  statLabel: { fontSize: 11, color: 'rgba(255,255,255,0.8)', marginTop: 4 },
  searchBar: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#FFF', marginHorizontal: 20, marginTop: 16, paddingHorizontal: 16, paddingVertical: 12, borderRadius: 12, elevation: 2 },
  searchInput: { flex: 1, marginLeft: 10, fontSize: 14, color: '#1E293B' },
  filterRow: { flexDirection: 'row', paddingHorizontal: 20, marginTop: 12, marginBottom: 8 },
  filterButton: { paddingHorizontal: 14, paddingVertical: 8, borderRadius: 20, backgroundColor: '#E2E8F0', marginRight: 8 },
  filterButtonActive: { backgroundColor: '#DC2626' },
  filterText: { fontSize: 13, fontWeight: '600', color: '#64748B' },
  filterTextActive: { color: '#FFFFFF' },
  content: { flex: 1, padding: 20 },
  // Customer List Card
  customerCard: { backgroundColor: '#FFF', borderRadius: 16, padding: 16, marginBottom: 12, elevation: 3 },
  customerCardHeader: { flexDirection: 'row', alignItems: 'center' },
  custAvatar: { width: 48, height: 48, borderRadius: 24, justifyContent: 'center', alignItems: 'center', marginRight: 12 },
  custInitial: { fontSize: 20, fontWeight: 'bold' },
  custInfo: { flex: 1 },
  custName: { fontSize: 16, fontWeight: '600', color: '#1E293B' },
  custMeta: { fontSize: 12, color: '#94A3B8', marginTop: 2 },
  custAmount: { fontSize: 18, fontWeight: 'bold' },
  custSubAmount: { fontSize: 11, color: '#94A3B8', marginTop: 2 },
  custProgress: { height: 6, backgroundColor: '#E2E8F0', borderRadius: 3, marginTop: 12, overflow: 'hidden' },
  custProgressFill: { height: '100%', borderRadius: 3 },
  custFooter: { flexDirection: 'row', alignItems: 'center', marginTop: 8 },
  custFooterText: { fontSize: 12, color: '#94A3B8', flex: 1 },
  custPending: { fontSize: 12, color: '#DC2626', fontWeight: '600', marginRight: 8 },
  // Profile Header
  profileInfo: { flexDirection: 'row', alignItems: 'center', marginBottom: 16 },
  profileAvatar: { width: 56, height: 56, borderRadius: 28, backgroundColor: 'rgba(255,255,255,0.2)', justifyContent: 'center', alignItems: 'center', marginRight: 16 },
  profileInitial: { fontSize: 24, fontWeight: 'bold', color: '#FFF' },
  profileName: { fontSize: 22, fontWeight: 'bold', color: '#FFF' },
  profilePhone: { fontSize: 14, color: 'rgba(255,255,255,0.8)', marginTop: 2 },
  profileAddress: { fontSize: 12, color: 'rgba(255,255,255,0.6)', marginTop: 2 },
  profileStats: { flexDirection: 'row', justifyContent: 'space-between', marginTop: 8 },
  profileStatBox: { flex: 1, alignItems: 'center' },
  profileStatMiddle: { borderLeftWidth: 1, borderRightWidth: 1, borderColor: 'rgba(255,255,255,0.3)' },
  profileStatValue: { fontSize: 16, fontWeight: 'bold', color: '#FFF' },
  profileStatLabel: { fontSize: 11, color: 'rgba(255,255,255,0.7)', marginTop: 4 },
  profileProgress: { height: 6, backgroundColor: 'rgba(255,255,255,0.2)', borderRadius: 3, marginTop: 12, overflow: 'hidden' },
  profileProgressFill: { height: '100%', borderRadius: 3, backgroundColor: '#FFF' },
  profileProgressText: { fontSize: 11, color: 'rgba(255,255,255,0.7)', marginTop: 6, textAlign: 'center' },
  sectionTitle: { fontSize: 16, fontWeight: 'bold', color: '#1E293B', marginBottom: 12 },
  // Bill Card (in profile)
  billCard: { backgroundColor: '#FFF', borderRadius: 14, padding: 14, marginBottom: 10, elevation: 2 },
  billHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 10 },
  billDate: { fontSize: 14, fontWeight: '600', color: '#1E293B' },
  billInvoice: { fontSize: 12, color: '#94A3B8', marginTop: 2 },
  statusBadge: { paddingHorizontal: 10, paddingVertical: 4, borderRadius: 12 },
  statusText: { fontSize: 11, fontWeight: 'bold' },
  billAmounts: { backgroundColor: '#F8FAFC', borderRadius: 10, padding: 10, marginBottom: 8 },
  billAmountRow: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 3 },
  billAmountLabel: { fontSize: 13, color: '#64748B' },
  billAmountVal: { fontSize: 13, fontWeight: '600', color: '#1E293B' },
  miniProgress: { height: 5, backgroundColor: '#E2E8F0', borderRadius: 3, marginTop: 6, overflow: 'hidden' },
  miniProgressFill: { height: '100%', borderRadius: 3 },
  billItems: { borderTopWidth: 1, borderTopColor: '#F1F5F9', paddingTop: 6, marginBottom: 6 },
  billItemText: { fontSize: 12, color: '#64748B', marginBottom: 1 },
  billActions: { flexDirection: 'row', borderTopWidth: 1, borderTopColor: '#F1F5F9', paddingTop: 10 },
  addPayBtn: { flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', backgroundColor: '#2563EB', paddingVertical: 8, borderRadius: 8, marginRight: 6 },
  addPayText: { color: '#FFF', fontWeight: '600', fontSize: 12, marginLeft: 4 },
  fullPayBtn: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#D1FAE5', paddingVertical: 8, paddingHorizontal: 10, borderRadius: 8, marginRight: 6 },
  fullPayText: { color: '#10B981', fontWeight: '600', fontSize: 12, marginLeft: 4 },
  histBtn: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#EFF6FF', paddingVertical: 8, paddingHorizontal: 10, borderRadius: 8 },
  histBtnText: { color: '#2563EB', fontWeight: '600', fontSize: 12, marginLeft: 4 },
  // Empty
  emptyState: { alignItems: 'center', paddingTop: 60 },
  emptyTitle: { fontSize: 18, fontWeight: 'bold', color: '#94A3B8', marginTop: 16 },
  emptyText: { fontSize: 14, color: '#CBD5E1', marginTop: 8, textAlign: 'center' },
  // Modal
  modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'flex-end' },
  modalContent: { backgroundColor: '#FFF', borderTopLeftRadius: 24, borderTopRightRadius: 24, padding: 24, maxHeight: '85%' },
  modalHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 },
  modalTitle: { fontSize: 20, fontWeight: 'bold', color: '#1E293B' },
  modalCustomer: { fontSize: 16, fontWeight: '600', color: '#64748B', marginBottom: 16 },
  modalAmountInfo: { backgroundColor: '#F8FAFC', borderRadius: 12, padding: 12, marginBottom: 16 },
  modalRow: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 6 },
  modalLabel: { fontSize: 14, color: '#64748B' },
  modalVal: { fontSize: 14, fontWeight: '600', color: '#1E293B' },
  inputLabel: { fontSize: 14, fontWeight: '600', color: '#1E293B', marginBottom: 6 },
  input: { borderWidth: 1, borderColor: '#E2E8F0', borderRadius: 12, padding: 14, fontSize: 16, color: '#1E293B', marginBottom: 12, backgroundColor: '#F8FAFC' },
  quickAmounts: { flexDirection: 'row', flexWrap: 'wrap', marginBottom: 16 },
  quickBtn: { backgroundColor: '#EFF6FF', paddingHorizontal: 16, paddingVertical: 8, borderRadius: 20, marginRight: 8, marginBottom: 8 },
  quickBtnText: { color: '#2563EB', fontWeight: '600', fontSize: 13 },
  submitBtn: { backgroundColor: '#2563EB', paddingVertical: 14, borderRadius: 12, alignItems: 'center' },
  submitText: { color: '#FFF', fontWeight: 'bold', fontSize: 16 },
  // History
  histItem: { flexDirection: 'row', marginBottom: 12, alignItems: 'flex-start' },
  histDot: { width: 10, height: 10, borderRadius: 5, backgroundColor: '#10B981', marginTop: 5, marginRight: 12 },
  histContent: { flex: 1, borderBottomWidth: 1, borderBottomColor: '#F1F5F9', paddingBottom: 12 },
  histRow: { flexDirection: 'row', justifyContent: 'space-between' },
  histAmount: { fontSize: 16, fontWeight: 'bold', color: '#10B981' },
  histDate: { fontSize: 13, color: '#64748B' },
  histNote: { fontSize: 12, color: '#94A3B8', marginTop: 4 },
});

export default DueScreen;
