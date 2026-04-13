import React, { useContext, useMemo, useState, useEffect, useRef } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, Animated, RefreshControl } from 'react-native';
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
  const [refreshing, setRefreshing] = useState(false);
  const [activeFilter, setActiveFilter] = useState('all');
  const fadeAnims = useRef({}).current;

  const fetchServerNotifs = async () => {
    try {
      if (user?.id) {
        const res = await getOwnerNotifications(user.id);
        if (res.success && res.notifications) setServerNotifs(res.notifications);
      }
    } catch (e) {}
  };

  useEffect(() => { fetchServerNotifs(); }, [user]);

  const onRefresh = async () => {
    setRefreshing(true);
    await fetchServerNotifs();
    setRefreshing(false);
  };

  const getOrCreateAnim = (id) => {
    if (!fadeAnims[id]) {
      fadeAnims[id] = new Animated.Value(0);
      Animated.timing(fadeAnims[id], { toValue: 1, duration: 400, useNativeDriver: true }).start();
    }
    return fadeAnims[id];
  };

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

  const FILTERS = [
    { key: 'all', label: 'Sab', icon: 'apps' },
    { key: 'overdue', label: 'Overdue', icon: 'warning' },
    { key: 'bill', label: 'Bills', icon: 'receipt' },
    { key: 'expense', label: 'Expenses', icon: 'trending-down' },
    { key: 'low-stock', label: 'Stock', icon: 'alert-circle' },
  ];

  const allNotifs = [
    ...serverNotifs.map(sn => {
      const typeMap = { info: { icon: 'megaphone', iconColor: '#2563EB', iconBg: '#DBEAFE' }, success: { icon: 'checkmark-circle', iconColor: '#10B981', iconBg: '#D1FAE5' }, warning: { icon: 'alert-circle', iconColor: '#F59E0B', iconBg: '#FEF3C7' }, urgent: { icon: 'flame', iconColor: '#DC2626', iconBg: '#FEE2E2' } };
      const t = typeMap[sn.type] || typeMap.info;
      const diff = Math.floor((new Date() - new Date(sn.created_at)) / 60000);
      const timeStr = diff < 60 ? `${diff}m ago` : diff < 1440 ? `${Math.floor(diff/60)}h ago` : `${Math.floor(diff/1440)}d ago`;
      return { id: `sn-${sn.id}`, type: 'server', ...t, title: sn.title, msg: sn.message, time: timeStr, accent: t.iconColor };
    }),
    ...data.notifs,
  ];

  const filtered = activeFilter === 'all' ? allNotifs : allNotifs.filter(n => n.type === activeFilter);

  const totalCount = data.notifs.length + serverNotifs.length;

  return (
    <SafeAreaView style={styles.container}>
      {/* Header */}
      <LinearGradient colors={['#1D4ED8', '#2563EB', '#3B82F6']} start={{x:0,y:0}} end={{x:1,y:1}} style={styles.header}>
        <View style={styles.headerTop}>
          <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
            <Ionicons name="arrow-back" size={22} color="#FFFFFF" />
          </TouchableOpacity>
          <View style={styles.headerCenter}>
            <Text style={styles.headerTitle}>Notifications</Text>
            <Text style={styles.headerDate}>{new Date().toLocaleDateString('en-IN', { weekday: 'long', day: 'numeric', month: 'long' })}</Text>
          </View>
          {totalCount > 0 ? (
            <View style={styles.badgeCircle}>
              <Text style={styles.badgeText}>{totalCount}</Text>
            </View>
          ) : <View style={{width: 36}} />}
        </View>

        {/* Stats Row inside header */}
        <View style={styles.statsRow}>
          <View style={styles.statPill}>
            <Ionicons name="receipt-outline" size={14} color="#FFFFFF" />
            <Text style={styles.statPillNum}>{data.todayBillsCount}</Text>
            <Text style={styles.statPillLabel}>Bills</Text>
          </View>
          <View style={styles.statDivider} />
          <View style={styles.statPill}>
            <Ionicons name="trending-down-outline" size={14} color="#FFFFFF" />
            <Text style={styles.statPillNum}>{data.todayExpCount}</Text>
            <Text style={styles.statPillLabel}>Expenses</Text>
          </View>
          <View style={styles.statDivider} />
          <View style={styles.statPill}>
            <Ionicons name="warning-outline" size={14} color="#FCD34D" />
            <Text style={[styles.statPillNum, {color:'#FCD34D'}]}>{data.overdueCount + data.dueTodayCount}</Text>
            <Text style={styles.statPillLabel}>Due</Text>
          </View>
          <View style={styles.statDivider} />
          <View style={styles.statPill}>
            <Ionicons name="alert-circle-outline" size={14} color="#FCA5A5" />
            <Text style={[styles.statPillNum, {color:'#FCA5A5'}]}>{data.lowStockCount}</Text>
            <Text style={styles.statPillLabel}>Stock</Text>
          </View>
        </View>
      </LinearGradient>

      {/* Filter Tabs */}
      <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.filterBar} contentContainerStyle={styles.filterContent}>
        {FILTERS.map(f => (
          <TouchableOpacity key={f.key} style={[styles.filterTab, activeFilter === f.key && styles.filterTabActive]} onPress={() => setActiveFilter(f.key)}>
            <Ionicons name={f.icon} size={13} color={activeFilter === f.key ? '#FFFFFF' : '#64748B'} />
            <Text style={[styles.filterLabel, activeFilter === f.key && styles.filterLabelActive]}>{f.label}</Text>
          </TouchableOpacity>
        ))}
      </ScrollView>

      {/* Notifications List */}
      <ScrollView
        style={styles.content}
        contentContainerStyle={styles.listContent}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={['#2563EB']} />}
        showsVerticalScrollIndicator={false}
      >
        {filtered.length === 0 ? (
          <View style={styles.emptyState}>
            <View style={styles.emptyIconWrap}>
              <Ionicons name="checkmark-circle" size={52} color="#10B981" />
            </View>
            <Text style={styles.emptyTitle}>Sab Clear Hai!</Text>
            <Text style={styles.emptySubtext}>Aaj koi activity nahi mili</Text>
          </View>
        ) : (
          filtered.map((n, index) => {
            const anim = getOrCreateAnim(n.id);
            const isAlert = n.type === 'overdue' || n.type === 'due-today';
            return (
              <Animated.View key={n.id} style={{ opacity: anim, transform: [{ translateY: anim.interpolate({ inputRange: [0,1], outputRange: [20,0] }) }] }}>
                <TouchableOpacity
                  style={[styles.card, isAlert && styles.cardAlert, { borderLeftColor: n.iconColor || n.accent || '#2563EB' }]}
                  onPress={() => handlePress(n)}
                  activeOpacity={0.85}
                >
                  <View style={[styles.iconWrap, { backgroundColor: n.iconBg }]}>
                    <Ionicons name={n.icon} size={22} color={n.iconColor} />
                  </View>
                  <View style={styles.cardBody}>
                    <View style={styles.cardTop}>
                      <Text style={[styles.cardTitle, { color: n.iconColor }]} numberOfLines={1}>{n.title}</Text>
                      {n.time ? <Text style={styles.cardTime}>{n.time}</Text> : null}
                    </View>
                    <Text style={styles.cardMsg} numberOfLines={2}>{n.msg}</Text>
                  </View>
                  <Ionicons name="chevron-forward" size={16} color="#CBD5E1" style={{marginLeft: 4}} />
                </TouchableOpacity>
              </Animated.View>
            );
          })
        )}
        <View style={{height: 24}} />
      </ScrollView>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F1F5F9' },

  header: { paddingHorizontal: 16, paddingTop: 12, paddingBottom: 18 },
  headerTop: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: 16 },
  backBtn: { width: 36, height: 36, borderRadius: 18, backgroundColor: 'rgba(255,255,255,0.2)', justifyContent: 'center', alignItems: 'center' },
  headerCenter: { flex: 1, alignItems: 'center' },
  headerTitle: { fontSize: 18, fontWeight: '700', color: '#FFFFFF', letterSpacing: 0.3 },
  headerDate: { fontSize: 12, color: '#BFDBFE', marginTop: 2 },
  badgeCircle: { width: 36, height: 36, borderRadius: 18, backgroundColor: '#EF4444', justifyContent: 'center', alignItems: 'center' },
  badgeText: { fontSize: 14, fontWeight: '700', color: '#FFFFFF' },

  statsRow: { flexDirection: 'row', backgroundColor: 'rgba(255,255,255,0.15)', borderRadius: 14, paddingVertical: 10, paddingHorizontal: 8, alignItems: 'center' },
  statPill: { flex: 1, alignItems: 'center', gap: 2 },
  statPillNum: { fontSize: 17, fontWeight: '800', color: '#FFFFFF' },
  statPillLabel: { fontSize: 10, color: '#BFDBFE', fontWeight: '500' },
  statDivider: { width: 1, height: 28, backgroundColor: 'rgba(255,255,255,0.25)' },

  filterBar: { maxHeight: 52, backgroundColor: '#FFFFFF' },
  filterContent: { flexDirection: 'row', paddingHorizontal: 12, paddingVertical: 10, gap: 8, alignItems: 'center' },
  filterTab: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 12, paddingVertical: 6, borderRadius: 20, backgroundColor: '#F1F5F9', gap: 5 },
  filterTabActive: { backgroundColor: '#2563EB' },
  filterLabel: { fontSize: 12, color: '#64748B', fontWeight: '600' },
  filterLabelActive: { color: '#FFFFFF' },

  content: { flex: 1 },
  listContent: { paddingHorizontal: 14, paddingTop: 14 },

  card: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#FFFFFF', borderRadius: 14, padding: 13, marginBottom: 10, borderLeftWidth: 4, elevation: 2, shadowColor: '#94A3B8', shadowOffset: {width:0, height:2}, shadowOpacity: 0.1, shadowRadius: 6 },
  cardAlert: { backgroundColor: '#FFFBEB' },
  iconWrap: { width: 44, height: 44, borderRadius: 22, justifyContent: 'center', alignItems: 'center', marginRight: 12 },
  cardBody: { flex: 1 },
  cardTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 4 },
  cardTitle: { fontSize: 13, fontWeight: '700', flex: 1, marginRight: 8 },
  cardTime: { fontSize: 10, color: '#94A3B8', fontWeight: '500' },
  cardMsg: { fontSize: 12, color: '#475569', lineHeight: 17 },

  emptyState: { alignItems: 'center', paddingVertical: 70 },
  emptyIconWrap: { width: 90, height: 90, borderRadius: 45, backgroundColor: '#D1FAE5', justifyContent: 'center', alignItems: 'center', marginBottom: 16 },
  emptyTitle: { fontSize: 17, fontWeight: '700', color: '#1E293B' },
  emptySubtext: { fontSize: 13, color: '#94A3B8', marginTop: 6 },
});

export default NotificationScreen;
