import React, { useContext, useMemo, useEffect, useRef } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { AppContext } from '../../context/AppContext';
import { useNavigation } from '@react-navigation/native';
import useTranslation from '../../i18n/useTranslation';

const DashboardScreen = () => {
  const { user, sales, expenses, products, bills } = useContext(AppContext);
  const navigation = useNavigation();
  const dueAlertShown = useRef(false);
  const { t } = useTranslation();

  // Due date alert checker - runs once when dashboard loads with bills
  useEffect(() => {
    if (dueAlertShown.current || !bills || bills.length === 0) return;
    dueAlertShown.current = true;

    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const todayStr = today.toISOString().split('T')[0];

    const dueBills = (bills || []).filter(b => {
      const mode = (b.payment_mode || b.paymentMode || '').toLowerCase();
      return mode === 'due' && b.due_status !== 'paid' && b.due_date;
    });

    const todayDue = dueBills.filter(b => b.due_date === todayStr);
    const overdue = dueBills.filter(b => {
      const dd = new Date(b.due_date);
      dd.setHours(0, 0, 0, 0);
      return dd < today;
    });

    const alerts = [];
    if (overdue.length > 0) {
      const totalOverdue = overdue.reduce((s, b) => {
        const total = parseFloat(b.grand_total || b.grandTotal || b.total) || 0;
        const paid = parseFloat(b.paid_amount) || 0;
        return s + (total - paid);
      }, 0);
      alerts.push(`⚠️ ${overdue.length} OVERDUE bill${overdue.length > 1 ? 's' : ''} (₹${Math.round(totalOverdue).toLocaleString('en-IN')})`);
      overdue.forEach(b => {
        alerts.push(`  • ${b.customer_name || b.customerName} - ₹${Math.round((parseFloat(b.grand_total || b.grandTotal || b.total) || 0) - (parseFloat(b.paid_amount) || 0)).toLocaleString('en-IN')} (Due: ${new Date(b.due_date).toLocaleDateString('en-IN', { day: 'numeric', month: 'short' })})`);
      });
    }
    if (todayDue.length > 0) {
      const totalToday = todayDue.reduce((s, b) => {
        const total = parseFloat(b.grand_total || b.grandTotal || b.total) || 0;
        const paid = parseFloat(b.paid_amount) || 0;
        return s + (total - paid);
      }, 0);
      if (alerts.length > 0) alerts.push('');
      alerts.push(`📅 ${todayDue.length} bill${todayDue.length > 1 ? 's' : ''} DUE TODAY (₹${Math.round(totalToday).toLocaleString('en-IN')})`);
      todayDue.forEach(b => {
        alerts.push(`  • ${b.customer_name || b.customerName} - ₹${Math.round((parseFloat(b.grand_total || b.grandTotal || b.total) || 0) - (parseFloat(b.paid_amount) || 0)).toLocaleString('en-IN')}`);
      });
    }

    if (alerts.length > 0) {
      setTimeout(() => {
        Alert.alert(
          '🔔 Due Payment Reminder',
          alerts.join('\n'),
          [
            { text: 'View Dues', onPress: () => navigation.navigate('DueScreen') },
            { text: 'OK', style: 'cancel' }
          ]
        );
      }, 1000);
    }
  }, [bills]);

  const todayStats = useMemo(() => {
    const today = new Date().toDateString();
    const safeSales = sales || [];
    const safeExpenses = expenses || [];

    const todaySales = safeSales.filter(sale => {
      try { return new Date(sale.date || sale.created_at).toDateString() === today; }
      catch { return false; }
    });

    const todayExpenses = safeExpenses.filter(expense => {
      try { return new Date(expense.date || expense.created_at).toDateString() === today; }
      catch { return false; }
    });

    const sales_total = Math.round(todaySales.reduce((sum, sale) => sum + (parseFloat(sale.amount) || 0), 0) * 100) / 100;
    const expenses_total = Math.round(todayExpenses.reduce((sum, expense) => sum + (parseFloat(expense.amount) || 0), 0) * 100) / 100;
    const profit = Math.round((sales_total - expenses_total) * 100) / 100;

    return {
      sales: sales_total,
      expenses: expenses_total,
      profit,
      orders: todaySales.length,
      profitPercentage: sales_total > 0 ? Math.round((profit / sales_total) * 100) : 0,
    };
  }, [sales, expenses]);

  const lowStockProducts = useMemo(() => {
    const safeProducts = products || [];
    return safeProducts.filter(product => {
      const stock = parseInt(product.stock || product.quantity) || 0;
      const threshold = parseInt(product.low_stock_threshold || product.lowStockThreshold || product.minStock) || 10;
      return stock <= threshold;
    });
  }, [products]);

  const recentActivity = useMemo(() => {
    const safeExpenses = expenses || [];
    const safeBills = bills || [];
    const allActivity = [
      ...safeExpenses.map(expense => ({
        ...expense,
        type: 'expense',
        displayAmount: parseFloat(expense.amount) || 0,
        displayDate: expense.date || expense.created_at,
        displayName: expense.category || expense.description || 'Expense',
      })),
      ...safeBills.map(bill => ({
        ...bill,
        type: 'bill',
        displayAmount: parseFloat(bill.grand_total || bill.grandTotal || bill.total) || 0,
        displayDate: bill.date || bill.created_at,
        displayName: `Bill - ${bill.customer_name || bill.customerName || 'Customer'}`,
      }))
    ];
    return allActivity
      .sort((a, b) => new Date(b.displayDate || 0) - new Date(a.displayDate || 0))
      .slice(0, 5);
  }, [expenses, bills]);

  const dueStats = useMemo(() => {
    const safeBills = bills || [];
    const dueBills = safeBills.filter(b => {
      const mode = (b.payment_mode || b.paymentMode || '').toLowerCase();
      return mode === 'due' && b.due_status !== 'paid';
    });
    const totalRemaining = dueBills.reduce((sum, b) => {
      const total = parseFloat(b.grand_total || b.grandTotal || b.total) || 0;
      const paid = parseFloat(b.paid_amount) || 0;
      return sum + (total - paid);
    }, 0);
    return { totalDue: Math.round(totalRemaining * 100) / 100, count: dueBills.length };
  }, [bills]);

  const getGreeting = () => {
    const hour = new Date().getHours();
    if (hour < 12) return t('goodMorning');
    if (hour < 17) return t('goodAfternoon');
    return t('goodEvening');
  };

  return (
    <SafeAreaView style={styles.container}>
      <LinearGradient
        colors={['#2563EB', '#1E40AF']}
        style={styles.header}
      >
        <View style={styles.headerContent}>
          <View>
            <Text style={styles.greeting}>{getGreeting()},</Text>
            <Text style={styles.businessName}>{user?.businessName || user?.name || 'Binest Store'}</Text>
          </View>
          <TouchableOpacity
            style={styles.notificationButton}
            onPress={() => navigation.navigate('Notifications')}
          >
            <Ionicons name="notifications-outline" size={24} color="#FFFFFF" />
            {lowStockProducts.length > 0 && (
              <View style={styles.notificationBadge}>
                <Text style={styles.notificationBadgeText}>{lowStockProducts.length}</Text>
              </View>
            )}
          </TouchableOpacity>
        </View>
      </LinearGradient>

      <ScrollView style={styles.content} showsVerticalScrollIndicator={false}>
        {/* Today's Stats Cards */}
        <View style={styles.statsContainer}>
          <LinearGradient colors={['#10B981', '#059669']} style={styles.statCard}>
            <View style={styles.statHeader}>
              <Text style={styles.statLabel}>{t('todaysSales')}</Text>
              <View style={styles.badge}>
                <Ionicons name="cart" size={12} color="#10B981" />
                <Text style={styles.badgeText}>{todayStats.orders} {t('orders')}</Text>
              </View>
            </View>
            <Text style={styles.statValue}>₹ {todayStats.sales.toLocaleString()}</Text>
          </LinearGradient>

          <LinearGradient colors={['#F59E0B', '#D97706']} style={styles.statCard}>
            <View style={styles.statHeader}>
              <Text style={styles.statLabel}>{t('todaysExpenses')}</Text>
              <View style={styles.badge}>
                <Ionicons name="wallet" size={12} color="#F59E0B" />
              </View>
            </View>
            <Text style={styles.statValue}>₹ {todayStats.expenses.toLocaleString()}</Text>
          </LinearGradient>

          <LinearGradient
            colors={todayStats.profit >= 0 ? ['#06B6D4', '#0891B2'] : ['#EF4444', '#DC2626']}
            style={styles.statCard}
          >
            <View style={styles.statHeader}>
              <Text style={styles.statLabel}>{t('todaysProfit')}</Text>
              <View style={styles.badge}>
                <Ionicons name={todayStats.profit >= 0 ? 'trending-up' : 'trending-down'} size={12} color={todayStats.profit >= 0 ? '#06B6D4' : '#EF4444'} />
                <Text style={styles.badgeText}>{todayStats.profitPercentage}%</Text>
              </View>
            </View>
            <Text style={styles.statValue}>₹ {todayStats.profit.toLocaleString()}</Text>
          </LinearGradient>

          <TouchableOpacity onPress={() => navigation.navigate('DueScreen')}>
            <LinearGradient colors={['#DC2626', '#991B1B']} style={styles.statCard}>
              <View style={styles.statHeader}>
                <Text style={styles.statLabel}>{t('totalDue')}</Text>
                <View style={styles.badge}>
                  <Ionicons name="time" size={12} color="#DC2626" />
                  <Text style={styles.badgeText}>{dueStats.count} {t('pending')}</Text>
                </View>
              </View>
              <Text style={styles.statValue}>₹ {dueStats.totalDue.toLocaleString()}</Text>
              <Text style={styles.statSub}>{t('tapToViewDue')}</Text>
            </LinearGradient>
          </TouchableOpacity>
        </View>

        {/* Overview Row */}
        <View style={styles.overviewRow}>
          <View style={styles.overviewCard}>
            <Ionicons name="cube-outline" size={24} color="#2563EB" />
            <Text style={styles.overviewValue}>{(products || []).length}</Text>
            <Text style={styles.overviewLabel}>{t('products')}</Text>
          </View>
          <View style={styles.overviewCard}>
            <Ionicons name="receipt-outline" size={24} color="#10B981" />
            <Text style={styles.overviewValue}>{(bills || []).length}</Text>
            <Text style={styles.overviewLabel}>{t('bills')}</Text>
          </View>
          <View style={styles.overviewCard}>
            <Ionicons name="people-outline" size={24} color="#8B5CF6" />
            <Text style={styles.overviewValue}>{todayStats.orders}</Text>
            <Text style={styles.overviewLabel}>{t('todayOrders')}</Text>
          </View>
        </View>

        {/* Low Stock Alert */}
        {lowStockProducts.length > 0 && (
          <TouchableOpacity style={styles.alertCard} onPress={() => navigation.navigate('Inventory')}>
            <View style={styles.alertIcon}>
              <Ionicons name="warning" size={24} color="#F59E0B" />
            </View>
            <View style={styles.alertContent}>
              <Text style={styles.alertTitle}>{t('lowStockAlert')}</Text>
              <Text style={styles.alertText}>
                {lowStockProducts.slice(0, 3).map(p => p.name || p.product_name).join(', ')}
                {lowStockProducts.length > 3 ? ` +${lowStockProducts.length - 3} ${t('more')}` : ''}
              </Text>
            </View>
            <View style={styles.alertBadge}>
              <Text style={styles.alertBadgeText}>{lowStockProducts.length}</Text>
            </View>
          </TouchableOpacity>
        )}

        {/* Recent Activity */}
        <View style={styles.sectionHeader}>
          <Text style={styles.sectionTitle}>{t('recentActivity')}</Text>
          <TouchableOpacity>
            <Ionicons name="chevron-forward" size={20} color="#64748B" />
          </TouchableOpacity>
        </View>

        <View style={styles.activityList}>
          {recentActivity.length > 0 ? (
            recentActivity.map((activity, index) => {
              const isBill = activity.type === 'bill';
              const isExpense = activity.type === 'expense';

              return (
                <View key={index} style={styles.activityItem}>
                  <View style={[
                    styles.activityIcon,
                    { backgroundColor: isBill ? '#D1FAE5' : '#FEE2E2' }
                  ]}>
                    <Ionicons
                      name={isBill ? 'receipt' : 'wallet'}
                      size={20}
                      color={isBill ? '#10B981' : '#EF4444'}
                    />
                  </View>
                  <View style={styles.activityContent}>
                    <Text style={styles.activityTitle}>{activity.displayName}</Text>
                    <Text style={styles.activityTime}>
                      {activity.displayDate ? new Date(activity.displayDate).toLocaleDateString('en-IN', {
                        day: 'numeric', month: 'short', year: 'numeric'
                      }) : 'N/A'}
                    </Text>
                  </View>
                  <Text style={[
                    styles.activityAmount,
                    { color: isExpense ? '#EF4444' : '#10B981' }
                  ]}>
                    {isExpense ? '-' : '+'}₹{activity.displayAmount.toLocaleString('en-IN')}
                  </Text>
                </View>
              );
            })
          ) : (
            <View style={styles.emptyState}>
              <Ionicons name="document-text-outline" size={40} color="#CBD5E1" />
              <Text style={styles.emptyStateText}>No recent activity</Text>
            </View>
          )}
        </View>

        {/* Quick Actions */}
        <View style={styles.quickActions}>
          <Text style={styles.sectionTitle}>Quick Actions</Text>
          <View style={styles.actionGrid}>
            <TouchableOpacity style={styles.actionButton} onPress={() => navigation.navigate('Billing')}>
              <LinearGradient colors={['#2563EB', '#1E40AF']} style={styles.actionGradient}>
                <Ionicons name="receipt" size={24} color="#FFFFFF" />
              </LinearGradient>
              <Text style={styles.actionText}>Create Bill</Text>
            </TouchableOpacity>

            <TouchableOpacity style={styles.actionButton} onPress={() => navigation.navigate('Inventory', { screen: 'AddProduct' })}>
              <LinearGradient colors={['#10B981', '#059669']} style={styles.actionGradient}>
                <Ionicons name="add-circle" size={24} color="#FFFFFF" />
              </LinearGradient>
              <Text style={styles.actionText}>Add Product</Text>
            </TouchableOpacity>

            <TouchableOpacity style={styles.actionButton} onPress={() => navigation.navigate('Expense')}>
              <LinearGradient colors={['#F59E0B', '#D97706']} style={styles.actionGradient}>
                <Ionicons name="wallet" size={24} color="#FFFFFF" />
              </LinearGradient>
              <Text style={styles.actionText}>Add Expense</Text>
            </TouchableOpacity>

            <TouchableOpacity style={styles.actionButton} onPress={() => navigation.navigate('Customers', { screen: 'AddCustomer' })}>
              <LinearGradient colors={['#8B5CF6', '#7C3AED']} style={styles.actionGradient}>
                <Ionicons name="person-add" size={24} color="#FFFFFF" />
              </LinearGradient>
              <Text style={styles.actionText}>Add Customer</Text>
            </TouchableOpacity>
          </View>
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
    paddingVertical: 20,
    borderBottomLeftRadius: 24,
    borderBottomRightRadius: 24,
  },
  headerContent: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  greeting: {
    fontSize: 14,
    color: '#E0E7FF',
  },
  businessName: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginTop: 4,
  },
  notificationButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: 'rgba(255, 255, 255, 0.2)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  notificationBadge: {
    position: 'absolute',
    top: -2,
    right: -2,
    backgroundColor: '#EF4444',
    borderRadius: 10,
    minWidth: 20,
    height: 20,
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 2,
    borderColor: '#2563EB',
  },
  notificationBadgeText: {
    color: '#FFFFFF',
    fontSize: 10,
    fontWeight: 'bold',
  },
  content: {
    flex: 1,
    padding: 20,
  },
  statsContainer: {
    marginBottom: 20,
  },
  statCard: {
    padding: 20,
    borderRadius: 16,
    marginBottom: 12,
  },
  statHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  statLabel: {
    fontSize: 14,
    color: '#FFFFFF',
    opacity: 0.9,
  },
  badge: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFFFFF',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 12,
  },
  badgeText: {
    fontSize: 12,
    fontWeight: 'bold',
    marginLeft: 4,
  },
  statValue: {
    fontSize: 28,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 4,
  },
  statSub: {
    fontSize: 12,
    color: 'rgba(255,255,255,0.8)',
  },
  overviewRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 20,
  },
  overviewCard: {
    flex: 1,
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    padding: 16,
    alignItems: 'center',
    marginHorizontal: 4,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 2,
    elevation: 2,
  },
  overviewValue: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#1E293B',
    marginTop: 8,
  },
  overviewLabel: {
    fontSize: 11,
    color: '#64748B',
    marginTop: 4,
  },
  alertCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FEF3C7',
    padding: 16,
    borderRadius: 12,
    marginBottom: 20,
  },
  alertIcon: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: '#FFFFFF',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 12,
  },
  alertContent: {
    flex: 1,
  },
  alertTitle: {
    fontSize: 14,
    fontWeight: 'bold',
    color: '#92400E',
    marginBottom: 2,
  },
  alertText: {
    fontSize: 12,
    color: '#92400E',
  },
  alertBadge: {
    backgroundColor: '#F59E0B',
    width: 28,
    height: 28,
    borderRadius: 14,
    justifyContent: 'center',
    alignItems: 'center',
  },
  alertBadgeText: {
    color: '#FFFFFF',
    fontSize: 12,
    fontWeight: 'bold',
  },
  sectionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 16,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#1E293B',
  },
  activityList: {
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    padding: 16,
    marginBottom: 20,
  },
  activityItem: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: '#F1F5F9',
  },
  activityIcon: {
    width: 40,
    height: 40,
    borderRadius: 20,
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 12,
  },
  activityContent: {
    flex: 1,
  },
  activityTitle: {
    fontSize: 14,
    fontWeight: '600',
    color: '#1E293B',
    marginBottom: 2,
  },
  activityTime: {
    fontSize: 12,
    color: '#64748B',
  },
  activityAmount: {
    fontSize: 14,
    fontWeight: 'bold',
  },
  emptyState: {
    padding: 20,
    alignItems: 'center',
  },
  emptyStateText: {
    fontSize: 14,
    color: '#94A3B8',
    marginTop: 8,
  },
  quickActions: {
    marginBottom: 20,
  },
  actionGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
    marginTop: 12,
  },
  actionButton: {
    width: '48%',
    alignItems: 'center',
    marginBottom: 16,
  },
  actionGradient: {
    width: 60,
    height: 60,
    borderRadius: 30,
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 8,
  },
  actionText: {
    fontSize: 12,
    color: '#64748B',
    textAlign: 'center',
  },
});

export default DashboardScreen;
