import React, { useContext, useState } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, TextInput, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { AppContext } from '../../context/AppContext';
import useTranslation from '../../i18n/useTranslation';

const CustomersScreen = ({ navigation }) => {
  const { customers, bills, deleteCustomer } = useContext(AppContext);
  const { t } = useTranslation();
  const [searchQuery, setSearchQuery] = useState('');

  const safeCustomers = customers || [];
  const safeBills = bills || [];

  const filteredCustomers = safeCustomers.filter(customer =>
    customer.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
    customer.mobile.includes(searchQuery)
  );

  const getCustomerPurchaseCount = (customerId) => {
    return safeBills.filter(bill => 
      bill.customerId === customerId || 
      bill.customer_id === customerId ||
      String(bill.customerId) === String(customerId) ||
      String(bill.customer_id) === String(customerId)
    ).length;
  };

  const handleDeleteCustomer = (customer) => {
    Alert.alert(
      'Delete Customer',
      `"${customer.name}" ko delete karna hai?`,
      [
        { text: 'Cancel', style: 'cancel' },
        { text: 'Delete', style: 'destructive', onPress: async () => {
          await deleteCustomer(customer.id);
        }}
      ]
    );
  };

  return (
    <SafeAreaView style={styles.container}>
      <LinearGradient
        colors={['#2563EB', '#1E40AF']}
        style={styles.header}
      >
        <View style={styles.headerTop}>
          <View style={styles.headerLeft}>
            <Ionicons name="people" size={24} color="#FFFFFF" />
            <Text style={styles.headerTitle}>{t('customers')}</Text>
          </View>
          <TouchableOpacity 
            style={styles.headerButton}
            onPress={() => navigation.navigate('AddCustomer')}
          >
            <Ionicons name="add" size={24} color="#FFFFFF" />
          </TouchableOpacity>
        </View>

        <View style={styles.searchContainer}>
          <Ionicons name="search" size={20} color="#64748B" />
          <TextInput
            style={styles.searchInput}
            placeholder={t('searchCustomers')}
            placeholderTextColor="#94A3B8"
            value={searchQuery}
            onChangeText={setSearchQuery}
          />
        </View>
      </LinearGradient>

      <ScrollView style={styles.content}>
        <View style={styles.statsRow}>
          <View style={styles.statCard}>
            <Text style={styles.statValue}>{customers.length}</Text>
            <Text style={styles.statLabel}>{t('totalCustomers')}</Text>
          </View>
          <View style={styles.statCard}>
            <Text style={styles.statValue}>
              {customers.filter(c => getCustomerPurchaseCount(c.id) > 0).length}
            </Text>
            <Text style={styles.statLabel}>{t('active')}</Text>
          </View>
        </View>

        <View style={styles.customerList}>
          {filteredCustomers.length > 0 ? (
            filteredCustomers.map((customer) => (
              <TouchableOpacity
                key={customer.id}
                style={styles.customerCard}
                onPress={() => navigation.navigate('CustomerDetails', { customer })}
                onLongPress={() => handleDeleteCustomer(customer)}
              >
                <View style={styles.customerAvatar}>
                  <Text style={styles.customerInitial}>
                    {customer.name.charAt(0).toUpperCase()}
                  </Text>
                </View>
                <View style={styles.customerInfo}>
                  <Text style={styles.customerName}>{customer.name}</Text>
                  <Text style={styles.customerMobile}>{customer.mobile}</Text>
                </View>
                <View style={styles.customerRight}>
                  <Text style={styles.customerPurchases}>
                    {getCustomerPurchaseCount(customer.id)} {t('purchases')}
                  </Text>
                  <Ionicons name="chevron-forward" size={20} color="#94A3B8" />
                </View>
              </TouchableOpacity>
            ))
          ) : (
            <View style={styles.emptyState}>
              <Ionicons name="people-outline" size={64} color="#CBD5E1" />
              <Text style={styles.emptyStateText}>{t('noCustomersFound')}</Text>
              <TouchableOpacity 
                style={styles.addButton}
                onPress={() => navigation.navigate('AddCustomer')}
              >
                <Text style={styles.addButtonText}>{t('addCustomer')}</Text>
              </TouchableOpacity>
            </View>
          )}
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
    paddingTop: 20,
    paddingBottom: 20,
    borderBottomLeftRadius: 24,
    borderBottomRightRadius: 24,
  },
  headerTop: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 16,
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
  searchContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    paddingHorizontal: 16,
    paddingVertical: 12,
  },
  searchInput: {
    flex: 1,
    marginLeft: 8,
    fontSize: 16,
    color: '#1E293B',
  },
  content: {
    flex: 1,
    padding: 20,
  },
  statsRow: {
    flexDirection: 'row',
    marginBottom: 20,
  },
  statCard: {
    flex: 1,
    backgroundColor: '#FFFFFF',
    padding: 20,
    borderRadius: 12,
    marginHorizontal: 4,
    alignItems: 'center',
  },
  statValue: {
    fontSize: 28,
    fontWeight: 'bold',
    color: '#2563EB',
    marginBottom: 4,
  },
  statLabel: {
    fontSize: 14,
    color: '#64748B',
  },
  customerList: {
    marginBottom: 20,
  },
  customerCard: {
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
  customerInfo: {
    flex: 1,
  },
  customerName: {
    fontSize: 16,
    fontWeight: '600',
    color: '#1E293B',
    marginBottom: 4,
  },
  customerMobile: {
    fontSize: 14,
    color: '#64748B',
  },
  customerRight: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  customerPurchases: {
    fontSize: 12,
    color: '#64748B',
    marginRight: 8,
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

export default CustomersScreen;
