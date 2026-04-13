import React, { useContext, useMemo, useState, useEffect } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { AppContext } from '../../context/AppContext';
import { getOwnerNotifications } from '../../services/api';
import useTranslation from '../../i18n/useTranslation';

const NotificationScreen = ({ navigation }) => {
  const { products, bills, expenses, user } = useContext(AppContext);
  const { t: tr } = useTranslation();
  const [serverNotifs, setServerNotifs] = useState([]);

  useEffect(() => {
    const fetchServerNotifs = async () => {
      try {
        if (user?.id) {
          const res = await getOwnerNotifications(user.id);
          if (res.success && res.notifications) {
            setServerNotifs(res.notifications);
          }
        }
      } catch (e) {}
    };
    fetchServerNotifs();
  }, [user]);

  const safeDate = (d) => {
    if (!d) return '';
    try { const dt = new Date(d); return isNaN(dt.getTime()) ? '' : dt.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit' }); }
    catch { return ''; }
  };

  const data = useMemo(() => {
    const today = new Date();
    const todayStr = today.toDateString();
    const todayISO = today.toISOString().split('T')[0];
    const safeBills = bills || [];
    const safeExpenses = expenses || [];
    const safeProducts = products || [];

    // Today's bills
    const todayBills = safeBills.filter(b => {
      try { return new Date(b.date || b.created_at).toDateString() === todayStr; }
      catch { return false; }
    });

    // Today's expenses
    const todayExpenses = safeExpenses.filter(e => {
      try { return new Date(e.date || e.created_at).toDateString() === todayStr; }
      catch { return false; }
    });

    // Due alerts: overdue + due today
    const overdueBills = safeBills.filter(b => {
      const mode = (b.payment_mode || b.paymentMode || '').toLowerCase();
      if (mode !== 'due' || b.due_status === 'paid' || !b.due_date) return false;
      const dd = new Date(b.due_date); dd.setHours(0,0,0,0);
      const now = new Date(); now.setHours(0,0,0,0);
      return dd < now;
    });

    const dueTodayBills = safeBills.filter(b => {
      const mode = (b.payment_mode || b.paymentMode || '').toLowerCase();
      return mode === 'due' && b.due_status !== 'paid' && b.due_date === todayISO;
    });

    // Low stock
    const lowStock = safeProducts.filter(p => {
      const stock = parseInt(p.stock || p.quantity) || 0;
      const threshold = parseInt(p.low_stock_threshold || p.lowStockThreshold || p.minStock) || 10;
      return stock <= threshold;
    });

    // Build notifications list
    const notifs = [];

    // Overdue alerts (red)
    overdueBills.forEach(bill => {
      const dd = new Date(bill.due_date); dd.setHours(0,0,0,0);
      const now = new Date(); now.setHours(0,0,0,0);
      const days = Math.floor((now - dd) / (1000*60*60*24));
      const rem = (parseFloat(bill.grand_total || bill.grandTotal || bill.total) || 0) - (parseFloat(bill.paid_amount) || 0);
      notifs.push({ id: `od-${bill.id}`, type: 'overdue', icon: 'warning', iconColor: '#DC2626', iconBg: '#FEE2E2',
        title: `OVERDUE ${days} din!`, msg: `${bill.customer_name || bill.customerName} - Rs.${Math.round(rem)}`, time: bill.due_date });
    });

    // Due today (orange)
    dueTodayBills.forEach(bill => {
      const rem = (parseFloat(bill.grand_total || bill.grandTotal || bill.total) || 0) - (parseFloat(bill.paid_amount) || 0);
      notifs.push({ id: `dt-${bill.id}`, type: 'due-today', icon: 'calendar', iconColor: '#F59E0B', iconBg: '#FEF3C7',
        title: 'Aaj Due Hai!', msg: `${bill.customer_name || bill.customerName} - Rs.${Math.round(rem)}`, time: 'Today' });
    });

    // Today's bills (green)
    todayBills.forEach(bill => {
      const amt = parseFloat(bill.grand_total || bill.grandTotal || bill.total) || 0;
      const mode = bill.payment_mode || bill.paymentMode || 'Cash';
      notifs.push({ id: `bill-${bill.id}`, type: 'bill', icon: 'receipt', iconColor: '#10B981', iconBg: '#D1FAE5',
        title: `Bill - ${bill.customer_name || bill.customerName}`, msg: `Rs.${Math.round(amt)} (${mode})`, time: safeDate(bill.created_at || bill.date), bill });
    });

    // Today's expenses (red-orange)
    todayExpenses.forEach(exp => {
      const amt = parseFloat(exp.amount) || 0;
      notifs.push({ id: `exp-${exp.id}`, type: 'expense', icon: 'trending-down', iconColor: '#EF4444', iconBg: '#FEE2E2',
        title: `Expense - ${exp.category || 'Other'}`, msg: `Rs.${Math.round(amt)}${exp.description ? ' - ' + exp.description : ''}`, time: safeDate(exp.created_at || exp.date) });
    });

    // Low stock (yellow)
    lowStock.forEach(p => {
      notifs.push({ id: `ls-${p.id}`, type: 'low-stock', icon: 'alert-circle', iconColor: '#F59E0B', iconBg: '#FEF3C7',
        title: 'Low Stock Alert', msg: `${p.name} - sirf ${p.stock || p.quantity || 0} bacha`, time: '' });
    });

    return {
      notifs,
      todayBillsCount: todayBills.length,
      todayBillsTotal: todayBills.reduce((s, b) => s + (parseFloat(b.grand_total || b.grandTotal || b.total) || 0), 0),
      todayExpCount: todayExpenses.length,
      todayExpTotal: todayExpenses.reduce((s, e) => s + (parseFloat(e.amount) || 0), 0),
      overdueCount: overdueBills.length,
      dueTodayCount: dueTodayBills.length,
      lowStockCount: lowStock.length,
    };
  }, [products, bills, expenses]);

  const handlePress = (n) => {
    if (n.type === 'overdue' || n.type === 'due-today') navigation.navigate('DueScreen');
    else if (n.type === 'bill' && n.bill) navigation.navigate('Invoice', { bill: n.bill });
    else if (n.type === 'low-stock') navigation.navigate('Inventory');
  };

  return (
    <SafeAreaView style={styles.container}>
      <LinearGradient colors={['#2563EB', '#1E40AF']} style={styles.header}>
        <View style={styles.headerTop}>
          <TouchableOpacity onPress={() => navigation.goBack()}>
            <Ionicons name="arrow-back" size={24} color="#FFFFFF" />
          </TouchableOpacity>
          <Text style={styles.headerTitle}>{tr('notifications')}</Text>
          <View style={{ width: 24 }} />
        </View>
        <Text style={styles.headerDate}>{new Date().toLocaleDateString('en-IN', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}</Text>
      </LinearGradient>

      {/* Stats Cards */}
      <View style={styles.statsRow}>
        <View style={[styles.statCard, { borderLeftColor: '#10B981' }]}>
          <Ionicons name="receipt" size={18} color="#10B981" />
          <Text style={styles.statNum}>{data.todayBillsCount}</Text>
          <Text style={styles.statLabel}>Bills</Text>
          <Text style={[styles.statAmt, { color: '#10B981' }]}>Rs.{Math.round(data.todayBillsTotal)}</Text>
        </View>
        <View style={[styles.statCard, { borderLeftColor: '#EF4444' }]}>
          <Ionicons name="trending-down" size={18} color="#EF4444" />
          <Text style={styles.statNum}>{data.todayExpCount}</Text>
          <Text style={styles.statLabel}>Expenses</Text>
          <Text style={[styles.statAmt, { color: '#EF4444' }]}>Rs.{Math.round(data.todayExpTotal)}</Text>
        </View>
        <View style={[styles.statCard, { borderLeftColor: '#DC2626' }]}>
          <Ionicons name="warning" size={18} color="#DC2626" />
          <Text style={styles.statNum}>{data.overdueCount + data.dueTodayCount}</Text>
          <Text style={styles.statLabel}>Due Alert</Text>
        </View>
        <View style={[styles.statCard, { borderLeftColor: '#F59E0B' }]}>
          <Ionicons name="alert-circle" size={18} color="#F59E0B" />
          <Text style={styles.statNum}>{data.lowStockCount}</Text>
          <Text style={styles.statLabel}>Low Stock</Text>
        </View>
      </View>

      <ScrollView style={styles.content}>
        {/* Server Notifications from Owner */}
        {serverNotifs.map((sn) => {
          const typeMap = { info: { icon: 'megaphone', color: '#2563EB', bg: '#DBEAFE' }, success: { icon: 'checkmark-circle', color: '#10B981', bg: '#D1FAE5' }, warning: { icon: 'alert-circle', color: '#F59E0B', bg: '#FEF3C7' }, urgent: { icon: 'flame', color: '#DC2626', bg: '#FEE2E2' } };
          const t = typeMap[sn.type] || typeMap.info;
          const timeStr = (() => { try { const d = new Date(sn.created_at); const now = new Date(); const diff = Math.floor((now - d) / 60000); if (diff < 60) return `${diff}m ago`; if (diff < 1440) return `${Math.floor(diff/60)}h ago`; return `${Math.floor(diff/1440)}d ago`; } catch { return ''; } })();
          return (
            <View key={`sn-${sn.id}`} style={[styles.notifCard, { borderLeftWidth: 4, borderLeftColor: t.color }]}>
              <View style={[styles.notifIcon, { backgroundColor: t.bg }]}>
                <Ionicons name={t.icon} size={20} color={t.color} />
              </View>
              <View style={styles.notifBody}>
                <Text style={[styles.notifTitle, { color: t.color }]}>{sn.title}</Text>
                <Text style={styles.notifMsg} numberOfLines={2}>{sn.message}</Text>
              </View>
              {timeStr ? <Text style={styles.notifTime}>{timeStr}</Text> : null}
            </View>
          );
        })}

        {data.notifs.length === 0 && serverNotifs.length === 0 ? (
          <View style={styles.emptyState}>
            <Ionicons name="checkmark-circle" size={64} color="#10B981" />
            <Text style={styles.emptyText}>{tr('allClear')}</Text>
            <Text style={styles.emptySubtext}>{tr('noActivityToday')}</Text>
          </View>
        ) : (
          data.notifs.map((n) => (
            <TouchableOpacity key={n.id} style={[styles.notifCard, n.type === 'overdue' && { borderLeftWidth: 4, borderLeftColor: '#DC2626' }, n.type === 'due-today' && { borderLeftWidth: 4, borderLeftColor: '#F59E0B' }]} onPress={() => handlePress(n)}>
              <View style={[styles.notifIcon, { backgroundColor: n.iconBg }]}>
                <Ionicons name={n.icon} size={20} color={n.iconColor} />
              </View>
              <View style={styles.notifBody}>
                <Text style={[styles.notifTitle, n.type === 'overdue' && { color: '#DC2626' }, n.type === 'due-today' && { color: '#F59E0B' }]}>{n.title}</Text>
                <Text style={styles.notifMsg} numberOfLines={1}>{n.msg}</Text>
              </View>
              {n.time ? <Text style={styles.notifTime}>{n.time}</Text> : null}
              <Ionicons name="chevron-forward" size={16} color="#CBD5E1" />
            </TouchableOpacity>
          ))
        )}
      </ScrollView>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F8FAFC' },
  header: { paddingTop: 20, paddingBottom: 16, paddingHorizontal: 20 },
  headerTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  headerTitle: { fontSize: 20, fontWeight: 'bold', color: '#FFFFFF' },
  headerDate: { color: '#E0E7FF', fontSize: 13, marginTop: 8 },
  statsRow: { flexDirection: 'row', paddingHorizontal: 12, paddingVertical: 12 },
  statCard: { flex: 1, backgroundColor: '#FFF', borderRadius: 10, padding: 10, marginHorizontal: 4, alignItems: 'center', borderLeftWidth: 3, elevation: 2 },
  statNum: { fontSize: 20, fontWeight: 'bold', color: '#1E293B', marginTop: 4 },
  statLabel: { fontSize: 10, color: '#64748B', marginTop: 2 },
  statAmt: { fontSize: 11, fontWeight: '600', marginTop: 2 },
  content: { flex: 1, paddingHorizontal: 16, paddingTop: 4 },
  notifCard: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#FFF', padding: 12, borderRadius: 12, marginBottom: 8, elevation: 1 },
  notifIcon: { width: 38, height: 38, borderRadius: 19, justifyContent: 'center', alignItems: 'center', marginRight: 10 },
  notifBody: { flex: 1 },
  notifTitle: { fontSize: 13, fontWeight: 'bold', color: '#1E293B', marginBottom: 2 },
  notifMsg: { fontSize: 12, color: '#64748B' },
  notifTime: { fontSize: 10, color: '#94A3B8', marginRight: 4 },
  emptyState: { alignItems: 'center', paddingVertical: 60 },
  emptyText: { fontSize: 18, fontWeight: '600', color: '#10B981', marginTop: 12 },
  emptySubtext: { fontSize: 14, color: '#94A3B8', marginTop: 4 },
});

export default NotificationScreen;
