import React, { useContext, useState } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { AppContext } from '../../context/AppContext';
import useTranslation from '../../i18n/useTranslation';

const ExpenseScreen = ({ navigation }) => {
  const { expenses } = useContext(AppContext);
  const { t } = useTranslation();
  const [selectedPeriod, setSelectedPeriod] = useState('daily');

  const filterExpenses = () => {
    const now = new Date();
    const safeExpenses = expenses || [];
    return safeExpenses.filter(expense => {
      const expenseDate = new Date(expense.date);
      if (selectedPeriod === 'daily') {
        return expenseDate.toDateString() === now.toDateString();
      } else if (selectedPeriod === 'monthly') {
        return expenseDate.getMonth() === now.getMonth() &&
          expenseDate.getFullYear() === now.getFullYear();
      } else {
        return expenseDate.getFullYear() === now.getFullYear();
      }
    });
  };

  const filteredExpenses = filterExpenses();
  const totalExpense = Math.round(filteredExpenses.reduce((sum, exp) => sum + (parseFloat(exp.amount) || 0), 0) * 100) / 100;

  return (
    <SafeAreaView style={styles.container}>
      <LinearGradient
        colors={['#F59E0B', '#D97706']}
        style={styles.header}
      >
        <View style={styles.headerTop}>
          <View style={styles.headerLeft}>
            <Ionicons name="wallet" size={24} color="#FFFFFF" />
            <Text style={styles.headerTitle}>{t('expenses')}</Text>
          </View>
          <TouchableOpacity
            style={styles.headerButton}
            onPress={() => navigation.navigate('AddExpense')}
          >
            <Ionicons name="add" size={24} color="#FFFFFF" />
          </TouchableOpacity>
        </View>

        <View style={styles.totalCard}>
          <Text style={styles.totalLabel}>{t('totalExpenses')}</Text>
          <Text style={styles.totalValue}>₹{(totalExpense || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</Text>
        </View>
      </LinearGradient>

      <View style={styles.periodSelector}>
        {['daily', 'monthly', 'yearly'].map((period) => (
          <TouchableOpacity
            key={period}
            style={[
              styles.periodButton,
              selectedPeriod === period && styles.periodButtonActive
            ]}
            onPress={() => setSelectedPeriod(period)}
          >
            <Text style={[
              styles.periodButtonText,
              selectedPeriod === period && styles.periodButtonTextActive
            ]}>
              {period.charAt(0).toUpperCase() + period.slice(1)}
            </Text>
          </TouchableOpacity>
        ))}
      </View>

      <ScrollView style={styles.content}>
        {filteredExpenses.length > 0 ? (
          filteredExpenses.map((expense) => (
            <View key={expense.id} style={styles.expenseCard}>
              <View style={styles.expenseIcon}>
                <Ionicons name="wallet-outline" size={24} color="#F59E0B" />
              </View>
              <View style={styles.expenseInfo}>
                <Text style={styles.expenseCategory}>{expense.category}</Text>
                <Text style={styles.expenseDate}>
                  {new Date(expense.date).toLocaleDateString()}
                </Text>
                {expense.description && (
                  <Text style={styles.expenseDescription}>{expense.description}</Text>
                )}
              </View>
              <Text style={styles.expenseAmount}>₹{(parseFloat(expense.amount) || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</Text>
            </View>
          ))
        ) : (
          <View style={styles.emptyState}>
            <Ionicons name="wallet-outline" size={64} color="#CBD5E1" />
            <Text style={styles.emptyStateText}>{t('noExpenses')}</Text>
            <TouchableOpacity
              style={styles.addButton}
              onPress={() => navigation.navigate('AddExpense')}
            >
              <Text style={styles.addButtonText}>{t('addExpense')}</Text>
            </TouchableOpacity>
          </View>
        )}
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
    paddingTop: 20,
    paddingBottom: 20,
    borderBottomLeftRadius: 24,
    borderBottomRightRadius: 24,
  },
  headerTop: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 20,
  },
  headerLeft: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  headerTitle: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginLeft: 12,
  },
  headerButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: 'rgba(255, 255, 255, 0.2)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  totalCard: {
    backgroundColor: 'rgba(255, 255, 255, 0.2)',
    padding: 20,
    borderRadius: 12,
    alignItems: 'center',
  },
  totalLabel: {
    fontSize: 14,
    color: '#FFFFFF',
    opacity: 0.9,
    marginBottom: 8,
  },
  totalValue: {
    fontSize: 32,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  periodSelector: {
    flexDirection: 'row',
    padding: 20,
    justifyContent: 'space-around',
  },
  periodButton: {
    paddingHorizontal: 24,
    paddingVertical: 10,
    borderRadius: 20,
    backgroundColor: '#F1F5F9',
  },
  periodButtonActive: {
    backgroundColor: '#2563EB',
  },
  periodButtonText: {
    fontSize: 14,
    color: '#64748B',
    fontWeight: '600',
  },
  periodButtonTextActive: {
    color: '#FFFFFF',
  },
  content: {
    flex: 1,
    padding: 20,
  },
  expenseCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFFFFF',
    padding: 16,
    borderRadius: 12,
    marginBottom: 12,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 2,
    elevation: 2,
  },
  expenseIcon: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: '#FEF3C7',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 12,
  },
  expenseInfo: {
    flex: 1,
  },
  expenseCategory: {
    fontSize: 16,
    fontWeight: '600',
    color: '#1E293B',
    marginBottom: 4,
  },
  expenseDate: {
    fontSize: 12,
    color: '#64748B',
  },
  expenseDescription: {
    fontSize: 12,
    color: '#94A3B8',
    marginTop: 2,
  },
  expenseAmount: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#EF4444',
  },
  emptyState: {
    alignItems: 'center',
    paddingVertical: 60,
  },
  emptyStateText: {
    fontSize: 16,
    color: '#94A3B8',
    marginTop: 16,
    marginBottom: 24,
  },
  addButton: {
    backgroundColor: '#2563EB',
    paddingHorizontal: 24,
    paddingVertical: 12,
    borderRadius: 8,
  },
  addButtonText: {
    color: '#FFFFFF',
    fontSize: 14,
    fontWeight: '600',
  },
});

export default ExpenseScreen;
