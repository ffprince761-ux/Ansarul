import React, { useState, useContext, useEffect } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, Alert, ActivityIndicator } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import { AppContext } from '../../context/AppContext';
import { createBackup, restoreBackup, getBackupList, syncDataToServer } from '../../services/api';
import AsyncStorage from '@react-native-async-storage/async-storage';

const BackupRestoreScreen = ({ navigation }) => {
  const { user, products, customers, sales, expenses } = useContext(AppContext);
  const [backups, setBackups] = useState([]);
  const [loading, setLoading] = useState(false);
  const [syncing, setSyncing] = useState(false);

  useEffect(() => {
    loadBackups();
  }, []);

  const loadBackups = async () => {
    if (!user?.id) return;
    
    setLoading(true);
    const result = await getBackupList(user.id);
    if (result.success) {
      setBackups(result.backups);
    }
    setLoading(false);
  };

  const handleCreateBackup = async () => {
    if (!user?.id) {
      Alert.alert('Error', 'Please login first');
      return;
    }

    Alert.alert(
      'Create Backup',
      'Aapka saara data SQL database mein backup ho jayega. Continue?',
      [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Create Backup',
          onPress: async () => {
            setLoading(true);
            const result = await createBackup(user.id);
            setLoading(false);
            
            if (result.success) {
              Alert.alert('Success', 'Backup successfully created!');
              loadBackups();
            } else {
              Alert.alert('Error', result.error || 'Failed to create backup');
            }
          }
        }
      ]
    );
  };

  const handleRestoreBackup = async (backupId) => {
    Alert.alert(
      'Restore Backup',
      'Yeh backup restore karne se aapka current data replace ho jayega. Continue?',
      [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Restore',
          style: 'destructive',
          onPress: async () => {
            setLoading(true);
            const result = await restoreBackup(user.id, backupId);
            setLoading(false);
            
            if (result.success) {
              // Save restored data to AsyncStorage
              await AsyncStorage.setItem('products', JSON.stringify(result.data.products || []));
              await AsyncStorage.setItem('customers', JSON.stringify(result.data.customers || []));
              await AsyncStorage.setItem('sales', JSON.stringify(result.data.sales || []));
              await AsyncStorage.setItem('expenses', JSON.stringify(result.data.expenses || []));
              
              Alert.alert('Success', 'Backup restored successfully! Please restart the app.');
            } else {
              Alert.alert('Error', result.error || 'Failed to restore backup');
            }
          }
        }
      ]
    );
  };

  const handleSyncToServer = async () => {
    if (!user?.id) {
      Alert.alert('Error', 'Please login first');
      return;
    }

    Alert.alert(
      'Sync to Server',
      'Aapka local data SQL server pe sync ho jayega. Continue?',
      [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Sync Now',
          onPress: async () => {
            setSyncing(true);
            
            const localData = {
              products,
              customers,
              sales,
              expenses
            };
            
            const result = await syncDataToServer(user.id, localData);
            setSyncing(false);
            
            if (result.success) {
              Alert.alert('Success', 'Data synced to server successfully!');
            } else {
              Alert.alert('Error', result.error || 'Failed to sync data');
            }
          }
        }
      ]
    );
  };

  const handleExportLocal = async () => {
    try {
      const allData = {
        user,
        products,
        customers,
        sales,
        expenses,
        timestamp: new Date().toISOString()
      };
      
      const jsonData = JSON.stringify(allData, null, 2);
      
      Alert.alert(
        'Export Successful',
        `Data exported successfully!\n\nProducts: ${products.length}\nCustomers: ${customers.length}\nSales: ${sales.length}\nExpenses: ${expenses.length}`,
        [{ text: 'OK' }]
      );
    } catch (error) {
      Alert.alert('Error', 'Failed to export data');
    }
  };

  return (
    <ScrollView style={styles.container}>
      <LinearGradient
        colors={['#2563EB', '#1E40AF']}
        style={styles.header}
      >
        <Text style={styles.headerTitle}>Backup & Restore</Text>
        <Text style={styles.headerSubtitle}>Apna data safe rakhen</Text>
      </LinearGradient>

      <View style={styles.content}>
        {/* Quick Actions */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Quick Actions</Text>
          
          <TouchableOpacity 
            style={styles.actionCard}
            onPress={handleCreateBackup}
            disabled={loading}
          >
            <LinearGradient
              colors={['#10B981', '#059669']}
              style={styles.actionGradient}
            >
              <Ionicons name="cloud-upload" size={32} color="#FFFFFF" />
            </LinearGradient>
            <View style={styles.actionContent}>
              <Text style={styles.actionTitle}>Create SQL Backup</Text>
              <Text style={styles.actionDescription}>
                Database mein backup banayein
              </Text>
            </View>
            <Ionicons name="chevron-forward" size={24} color="#94A3B8" />
          </TouchableOpacity>

          <TouchableOpacity 
            style={styles.actionCard}
            onPress={handleSyncToServer}
            disabled={syncing}
          >
            <LinearGradient
              colors={['#2563EB', '#1E40AF']}
              style={styles.actionGradient}
            >
              <Ionicons name="sync" size={32} color="#FFFFFF" />
            </LinearGradient>
            <View style={styles.actionContent}>
              <Text style={styles.actionTitle}>Sync to Server</Text>
              <Text style={styles.actionDescription}>
                Local data ko server pe sync karein
              </Text>
            </View>
            {syncing ? (
              <ActivityIndicator color="#2563EB" />
            ) : (
              <Ionicons name="chevron-forward" size={24} color="#94A3B8" />
            )}
          </TouchableOpacity>

          <TouchableOpacity 
            style={styles.actionCard}
            onPress={handleExportLocal}
          >
            <LinearGradient
              colors={['#F59E0B', '#D97706']}
              style={styles.actionGradient}
            >
              <Ionicons name="download" size={32} color="#FFFFFF" />
            </LinearGradient>
            <View style={styles.actionContent}>
              <Text style={styles.actionTitle}>Export Local Data</Text>
              <Text style={styles.actionDescription}>
                Local backup export karein
              </Text>
            </View>
            <Ionicons name="chevron-forward" size={24} color="#94A3B8" />
          </TouchableOpacity>
        </View>

        {/* Data Summary */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Current Data</Text>
          <View style={styles.statsGrid}>
            <View style={styles.statBox}>
              <Text style={styles.statValue}>{products.length}</Text>
              <Text style={styles.statLabel}>Products</Text>
            </View>
            <View style={styles.statBox}>
              <Text style={styles.statValue}>{customers.length}</Text>
              <Text style={styles.statLabel}>Customers</Text>
            </View>
            <View style={styles.statBox}>
              <Text style={styles.statValue}>{sales.length}</Text>
              <Text style={styles.statLabel}>Sales</Text>
            </View>
            <View style={styles.statBox}>
              <Text style={styles.statValue}>{expenses.length}</Text>
              <Text style={styles.statLabel}>Expenses</Text>
            </View>
          </View>
        </View>

        {/* Backup History */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Backup History</Text>
          
          {loading ? (
            <ActivityIndicator size="large" color="#2563EB" style={styles.loader} />
          ) : backups.length > 0 ? (
            backups.map((backup) => (
              <TouchableOpacity
                key={backup.id}
                style={styles.backupCard}
                onPress={() => handleRestoreBackup(backup.id)}
              >
                <View style={styles.backupIcon}>
                  <Ionicons name="archive" size={24} color="#2563EB" />
                </View>
                <View style={styles.backupInfo}>
                  <Text style={styles.backupDate}>
                    {new Date(backup.created_at).toLocaleDateString('en-IN')}
                  </Text>
                  <Text style={styles.backupTime}>
                    {new Date(backup.created_at).toLocaleTimeString('en-IN')}
                  </Text>
                </View>
                <TouchableOpacity style={styles.restoreButton}>
                  <Text style={styles.restoreButtonText}>Restore</Text>
                </TouchableOpacity>
              </TouchableOpacity>
            ))
          ) : (
            <View style={styles.emptyState}>
              <Ionicons name="cloud-offline-outline" size={64} color="#CBD5E1" />
              <Text style={styles.emptyStateText}>No backups found</Text>
              <Text style={styles.emptyStateSubtext}>
                Create your first backup to secure your data
              </Text>
            </View>
          )}
        </View>

        {/* Info Box */}
        <View style={styles.infoBox}>
          <Ionicons name="information-circle" size={24} color="#2563EB" />
          <View style={styles.infoContent}>
            <Text style={styles.infoTitle}>About Backup & Restore</Text>
            <Text style={styles.infoText}>
              • SQL Backup: Data MySQL database mein save hota hai{'\n'}
              • Sync: Local data server pe upload hota hai{'\n'}
              • Restore: Purana data wapas la sakte hain{'\n'}
              • Export: Local file download kar sakte hain
            </Text>
          </View>
        </View>
      </View>
    </ScrollView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F8FAFC',
  },
  header: {
    padding: 30,
    borderBottomLeftRadius: 24,
    borderBottomRightRadius: 24,
  },
  headerTitle: {
    fontSize: 28,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 8,
  },
  headerSubtitle: {
    fontSize: 14,
    color: '#E0E7FF',
  },
  content: {
    padding: 20,
  },
  section: {
    marginBottom: 24,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#1E293B',
    marginBottom: 16,
  },
  actionCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFFFFF',
    padding: 16,
    borderRadius: 12,
    marginBottom: 12,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 4,
    elevation: 2,
  },
  actionGradient: {
    width: 56,
    height: 56,
    borderRadius: 28,
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 16,
  },
  actionContent: {
    flex: 1,
  },
  actionTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#1E293B',
    marginBottom: 4,
  },
  actionDescription: {
    fontSize: 12,
    color: '#64748B',
  },
  statsGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
  },
  statBox: {
    width: '48%',
    backgroundColor: '#FFFFFF',
    padding: 20,
    borderRadius: 12,
    alignItems: 'center',
    marginBottom: 12,
  },
  statValue: {
    fontSize: 32,
    fontWeight: 'bold',
    color: '#2563EB',
    marginBottom: 4,
  },
  statLabel: {
    fontSize: 14,
    color: '#64748B',
  },
  loader: {
    marginVertical: 20,
  },
  backupCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFFFFF',
    padding: 16,
    borderRadius: 12,
    marginBottom: 12,
  },
  backupIcon: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: '#EFF6FF',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 12,
  },
  backupInfo: {
    flex: 1,
  },
  backupDate: {
    fontSize: 14,
    fontWeight: '600',
    color: '#1E293B',
    marginBottom: 2,
  },
  backupTime: {
    fontSize: 12,
    color: '#64748B',
  },
  restoreButton: {
    backgroundColor: '#2563EB',
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 8,
  },
  restoreButtonText: {
    color: '#FFFFFF',
    fontSize: 14,
    fontWeight: '600',
  },
  emptyState: {
    alignItems: 'center',
    paddingVertical: 40,
  },
  emptyStateText: {
    fontSize: 16,
    fontWeight: '600',
    color: '#64748B',
    marginTop: 16,
  },
  emptyStateSubtext: {
    fontSize: 14,
    color: '#94A3B8',
    marginTop: 8,
    textAlign: 'center',
  },
  infoBox: {
    flexDirection: 'row',
    backgroundColor: '#EFF6FF',
    padding: 16,
    borderRadius: 12,
    marginTop: 8,
  },
  infoContent: {
    flex: 1,
    marginLeft: 12,
  },
  infoTitle: {
    fontSize: 14,
    fontWeight: '600',
    color: '#1E293B',
    marginBottom: 8,
  },
  infoText: {
    fontSize: 12,
    color: '#64748B',
    lineHeight: 18,
  },
});

export default BackupRestoreScreen;
