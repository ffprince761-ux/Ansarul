import React, { useContext, useState, useEffect } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, TextInput, Alert, Modal } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { AppContext } from '../../context/AppContext';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { API_URL } from '../../services/api';
import useTranslation from '../../i18n/useTranslation';

const ProfileScreen = ({ navigation }) => {
  const { user, logout, saveUser, saveLanguage, language, clearAllData, bills, expenses, products } = useContext(AppContext);
  const { t } = useTranslation();
  const [isEditingBusiness, setIsEditingBusiness] = useState(false);
  const [businessName, setBusinessName] = useState(user?.businessName || '');
  const [mobile, setMobile] = useState(user?.mobile || '');
  const [email, setEmail] = useState(user?.email || '');
  const [address, setAddress] = useState(user?.address || '');
  const [showPasswordModal, setShowPasswordModal] = useState(false);
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [showLanguageModal, setShowLanguageModal] = useState(false);
  const [appSettings, setAppSettings] = useState({
    support_email: 'admin@biswamart.com',
    support_phone: '+91 7608081767',
    app_version: '1.0.1'
  });
  const [isRefreshing, setIsRefreshing] = useState(false);

  useEffect(() => {
    loadSettings();
  }, []);

  const loadSettings = async () => {
    // First load from AsyncStorage (cached)
    try {
      const cached = await AsyncStorage.getItem('app_settings');
      if (cached) {
        setAppSettings(JSON.parse(cached));
      }
    } catch (error) {
      // silent
    }
    
    // Then fetch fresh from API
    fetchAppSettings();
  };

  const fetchAppSettings = async (showAlert = false) => {
    if (showAlert) setIsRefreshing(true);
    
    try {
      const response = await fetch(`${API_URL}/get_app_settings.php`, {
        method: 'GET',
        headers: {
          'Cache-Control': 'no-cache'
        }
      });
      const data = await response.json();
      
      if (data.success && data.settings) {
        setAppSettings(data.settings);
        // Save to AsyncStorage for offline use
        await AsyncStorage.setItem('app_settings', JSON.stringify(data.settings));
        
        if (showAlert) {
          Alert.alert('Success', 'Settings refreshed successfully!');
        }
      } else if (showAlert) {
        Alert.alert('Error', 'Failed to fetch settings from server');
      }
    } catch (error) {
      // silent
      if (showAlert) {
        Alert.alert('Error', 'Cannot connect to server. Using cached settings.');
      }
    } finally {
      if (showAlert) setIsRefreshing(false);
    }
  };

  const handleRefreshSettings = () => {
    fetchAppSettings(true);
  };

  const handleSaveBusinessInfo = async () => {
    if (!businessName || !mobile || !email) {
      Alert.alert('Error', 'Please fill in business name, mobile and email');
      return;
    }

    await saveUser({ ...user, businessName, mobile, email, address });
    setIsEditingBusiness(false);
    Alert.alert('Success', 'Business information updated');
  };

  const handleLogout = () => {
    Alert.alert(
      'Logout',
      'Are you sure you want to logout?',
      [
        { text: 'Cancel', style: 'cancel' },
        { 
          text: 'Logout', 
          style: 'destructive',
          onPress: async () => {
            await logout();
            navigation.replace('Login');
          }
        }
      ]
    );
  };

  const handleChangeLanguage = () => {
    setShowLanguageModal(true);
  };

  const handleSelectLanguage = async (selectedLanguage) => {
    await saveLanguage(selectedLanguage);
    setShowLanguageModal(false);
    if (selectedLanguage === 'en') {
      Alert.alert('Success', 'Language changed to English');
    } else if (selectedLanguage === 'hi') {
      Alert.alert('Success', 'भाषा हिंदी में बदल गई');
    }
  };

  const handleChangePassword = () => {
    setShowPasswordModal(true);
  };

  const handleSavePassword = async () => {
    if (!currentPassword || !newPassword || !confirmPassword) {
      Alert.alert('Error', 'Please fill all password fields');
      return;
    }

    if (newPassword !== confirmPassword) {
      Alert.alert('Error', 'New password and confirm password do not match');
      return;
    }

    if (newPassword.length < 6) {
      Alert.alert('Error', 'Password must be at least 6 characters long');
      return;
    }

    try {
      const response = await fetch(`${API_URL}/auth.php?action=changePassword`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          userId: user.id,
          currentPassword,
          newPassword
        })
      });

      const data = await response.json();

      if (data.success) {
        Alert.alert('Success', 'Password changed successfully!');
        setShowPasswordModal(false);
        setCurrentPassword('');
        setNewPassword('');
        setConfirmPassword('');
      } else {
        Alert.alert('Error', data.error || 'Failed to change password');
      }
    } catch (error) {
      // silent
      Alert.alert('Error', 'Failed to change password. Please try again.');
    }
  };

  const handleHelpSupport = () => {
    navigation.navigate('HelpSupport');
  };

  const handleAbout = () => {
    const aboutMessage = language === 'hi' 
      ? 'बिज़ीनोट - व्यापार प्रबंधन ऐप\n\n' +
        `📱 संस्करण: ${appSettings.app_version}\n\n` +
        '✨ विशेषताएं:\n' +
        '• इन्वेंटरी प्रबंधन - उत्पादों को ट्रैक करें\n' +
        '• ग्राहक प्रबंधन - ग्राहक डेटा संग्रहीत करें\n' +
        '• बिलिंग सिस्टम - तेज़ और आसान चालान\n' +
        '• खर्च ट्रैकिंग - खर्चों को मॉनिटर करें\n' +
        '• रिपोर्ट्स - CSV और PDF में निर्यात करें\n' +
        '• बैकअप और रिस्टोर - डेटा सुरक्षा\n' +
        '• बहुभाषी - हिंदी और अंग्रेजी\n\n' +
        '🎯 उद्देश्य:\n' +
        'छोटे और मध्यम व्यवसायों के लिए एक संपूर्ण समाधान जो आपके व्यवसाय को डिजिटल बनाता है और प्रबंधन को सरल बनाता है।\n\n' +
        '🛠️ तकनीक:\n' +
        'React Native, Expo, PHP, MySQL\n\n' +
        '📧 संपर्क:\n' +
        `${appSettings.support_email}\n\n` +
        '© 2026 Binest. सर्वाधिकार सुरक्षित।'
      : 'Binest - Business Management App\n\n' +
        `📱 Version: ${appSettings.app_version}\n\n` +
        '✨ Features:\n' +
        '• Inventory Management - Track products & stock\n' +
        '• Customer Management - Store customer data\n' +
        '• Billing System - Fast and easy invoicing\n' +
        '• Expense Tracking - Monitor your expenses\n' +
        '• Reports - Export in CSV and PDF formats\n' +
        '• Backup & Restore - Data security\n' +
        '• Multi-language - Hindi and English support\n\n' +
        '🎯 Purpose:\n' +
        'A complete solution for small and medium businesses that digitizes your business and simplifies management.\n\n' +
        '🛠️ Technology:\n' +
        'React Native, Expo, PHP, MySQL\n\n' +
        '📧 Contact:\n' +
        `${appSettings.support_email}\n\n` +
        '© 2026 Binest. All rights reserved.';

    Alert.alert(
      language === 'hi' ? 'बाइनेस्ट के बारे में' : 'About Binest',
      aboutMessage,
      [{ text: language === 'hi' ? 'ठीक है' : 'OK' }]
    );
  };


  const handleClearData = () => {
    Alert.alert(
      'Clear All Data',
      'This will delete all products, customers, bills, and sales data. This action cannot be undone. Are you sure?',
      [
        { text: 'Cancel', style: 'cancel' },
        { 
          text: 'Clear All', 
          style: 'destructive',
          onPress: async () => {
            await clearAllData();
            Alert.alert('Success', 'All data cleared successfully. You can now add fresh data.');
          }
        }
      ]
    );
  };

  const handleBackup = async () => {
    try {
      const allData = await AsyncStorage.getAllKeys();
      const data = await AsyncStorage.multiGet(allData);
      Alert.alert('Success', 'Data backed up successfully');
    } catch (error) {
      Alert.alert('Error', 'Failed to backup data');
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      <LinearGradient
        colors={['#2563EB', '#1E40AF']}
        style={styles.header}
      >
        <View style={styles.profileHeader}>
          <View style={styles.avatar}>
            <Text style={styles.avatarText}>
              {user?.businessName?.charAt(0).toUpperCase() || 'B'}
            </Text>
          </View>
          <Text style={styles.businessName}>{user?.businessName || 'Business Name'}</Text>
          <Text style={styles.email}>{user?.email || 'email@example.com'}</Text>
        </View>
      </LinearGradient>

      <ScrollView style={styles.content}>
        <View style={styles.section}>
          <View style={styles.sectionHeader}>
            <Text style={styles.sectionTitle}>Business Information</Text>
            <TouchableOpacity onPress={() => setIsEditingBusiness(!isEditingBusiness)}>
              <Ionicons 
                name={isEditingBusiness ? 'close' : 'pencil'} 
                size={20} 
                color="#2563EB" 
              />
            </TouchableOpacity>
          </View>

          {isEditingBusiness ? (
            <View style={styles.editForm}>
              <View style={styles.inputGroup}>
                <Text style={styles.label}>Business Name</Text>
                <TextInput
                  style={styles.input}
                  value={businessName}
                  onChangeText={setBusinessName}
                  placeholder="Enter business name"
                />
              </View>
              <View style={styles.inputGroup}>
                <Text style={styles.label}>Mobile</Text>
                <TextInput
                  style={styles.input}
                  value={mobile}
                  onChangeText={setMobile}
                  placeholder="Enter mobile number"
                  keyboardType="phone-pad"
                />
              </View>
              <View style={styles.inputGroup}>
                <Text style={styles.label}>Email</Text>
                <TextInput
                  style={styles.input}
                  value={email}
                  onChangeText={setEmail}
                  placeholder="Enter email"
                  keyboardType="email-address"
                />
              </View>
              <View style={styles.inputGroup}>
                <Text style={styles.label}>Address (Optional)</Text>
                <TextInput
                  style={styles.input}
                  value={address}
                  onChangeText={setAddress}
                  placeholder="Enter business address"
                  multiline
                  numberOfLines={2}
                />
              </View>
              <TouchableOpacity style={styles.saveButton} onPress={handleSaveBusinessInfo}>
                <Text style={styles.saveButtonText}>Save Changes</Text>
              </TouchableOpacity>
            </View>
          ) : (
            <View style={styles.infoCard}>
              <View style={styles.infoRow}>
                <Ionicons name="business-outline" size={20} color="#64748B" />
                <Text style={styles.infoText}>{user?.businessName || 'Not set'}</Text>
              </View>
              <View style={styles.infoRow}>
                <Ionicons name="call-outline" size={20} color="#64748B" />
                <Text style={styles.infoText}>{user?.mobile || 'Not set'}</Text>
              </View>
              <View style={styles.infoRow}>
                <Ionicons name="mail-outline" size={20} color="#64748B" />
                <Text style={styles.infoText}>{user?.email || 'Not set'}</Text>
              </View>
              <View style={styles.infoRow}>
                <Ionicons name="location-outline" size={20} color="#64748B" />
                <Text style={styles.infoText}>{user?.address || 'Not set'}</Text>
              </View>
            </View>
          )}
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>{t('settings')}</Text>
          
          <TouchableOpacity 
            style={styles.menuItem}
            onPress={handleChangeLanguage}
          >
            <View style={styles.menuItemLeft}>
              <View style={[styles.menuIcon, { backgroundColor: '#DBEAFE' }]}>
                <Ionicons name="language" size={20} color="#2563EB" />
              </View>
              <Text style={styles.menuItemText}>{t('changeLanguage')}</Text>
            </View>
            <View style={styles.menuItemRight}>
              <Text style={styles.menuItemValue}>
                {language === 'en' ? 'English' : language === 'hi' ? 'हिंदी' : 'English'}
              </Text>
              <Ionicons name="chevron-forward" size={20} color="#94A3B8" />
            </View>
          </TouchableOpacity>

          <TouchableOpacity 
            style={styles.menuItem}
            onPress={handleChangePassword}
          >
            <View style={styles.menuItemLeft}>
              <View style={[styles.menuIcon, { backgroundColor: '#FEF3C7' }]}>
                <Ionicons name="lock-closed" size={20} color="#F59E0B" />
              </View>
              <Text style={styles.menuItemText}>{t('changePassword')}</Text>
            </View>
            <Ionicons name="chevron-forward" size={20} color="#94A3B8" />
          </TouchableOpacity>

          <TouchableOpacity 
            style={styles.menuItem} 
            onPress={() => navigation.navigate('BackupRestore')}
          >
            <View style={styles.menuItemLeft}>
              <View style={[styles.menuIcon, { backgroundColor: '#D1FAE5' }]}>
                <Ionicons name="cloud-upload" size={20} color="#10B981" />
              </View>
              <Text style={styles.menuItemText}>{t('backupRestore')}</Text>
            </View>
            <Ionicons name="chevron-forward" size={20} color="#94A3B8" />
          </TouchableOpacity>

          <TouchableOpacity 
            style={styles.menuItem} 
            onPress={() => navigation.navigate('NotificationSettings')}
          >
            <View style={styles.menuItemLeft}>
              <View style={[styles.menuIcon, { backgroundColor: '#DBEAFE' }]}>
                <Ionicons name="notifications" size={20} color="#2563EB" />
              </View>
              <Text style={styles.menuItemText}>{t('notificationSettings')}</Text>
            </View>
            <Ionicons name="chevron-forward" size={20} color="#94A3B8" />
          </TouchableOpacity>

          <TouchableOpacity 
            style={styles.menuItem}
            onPress={handleClearData}
          >
            <View style={styles.menuItemLeft}>
              <View style={[styles.menuIcon, { backgroundColor: '#FEE2E2' }]}>
                <Ionicons name="trash" size={20} color="#EF4444" />
              </View>
              <Text style={styles.menuItemText}>Clear All Data</Text>
            </View>
            <Ionicons name="chevron-forward" size={20} color="#94A3B8" />
          </TouchableOpacity>

        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Support</Text>
          
          <TouchableOpacity 
            style={styles.menuItem}
            onPress={handleRefreshSettings}
            disabled={isRefreshing}
          >
            <View style={styles.menuItemLeft}>
              <View style={[styles.menuIcon, { backgroundColor: '#DBEAFE' }]}>
                <Ionicons name={isRefreshing ? "sync" : "refresh"} size={20} color="#2563EB" />
              </View>
              <Text style={styles.menuItemText}>
                {isRefreshing ? 'Refreshing...' : 'Refresh App Settings'}
              </Text>
            </View>
            <Ionicons name="chevron-forward" size={20} color="#94A3B8" />
          </TouchableOpacity>

          <TouchableOpacity 
            style={styles.menuItem}
            onPress={handleHelpSupport}
          >
            <View style={styles.menuItemLeft}>
              <View style={[styles.menuIcon, { backgroundColor: '#FEE2E2' }]}>
                <Ionicons name="help-circle" size={20} color="#EF4444" />
              </View>
              <Text style={styles.menuItemText}>Help & Support</Text>
            </View>
            <Ionicons name="chevron-forward" size={20} color="#94A3B8" />
          </TouchableOpacity>

          <TouchableOpacity 
            style={styles.menuItem}
            onPress={handleAbout}
          >
            <View style={styles.menuItemLeft}>
              <View style={[styles.menuIcon, { backgroundColor: '#E0E7FF' }]}>
                <Ionicons name="information-circle" size={20} color="#6366F1" />
              </View>
              <Text style={styles.menuItemText}>About</Text>
            </View>
            <Ionicons name="chevron-forward" size={20} color="#94A3B8" />
          </TouchableOpacity>
        </View>

        <TouchableOpacity style={styles.logoutButton} onPress={handleLogout}>
          <Ionicons name="log-out-outline" size={20} color="#EF4444" />
          <Text style={styles.logoutButtonText}>Logout</Text>
        </TouchableOpacity>

        <View style={styles.version}>
          <Text style={styles.versionText}>Version 1.0.0</Text>
        </View>
      </ScrollView>

      {/* Change Password Modal */}
      <Modal
        visible={showPasswordModal}
        transparent={true}
        animationType="slide"
        onRequestClose={() => setShowPasswordModal(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.passwordModal}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Change Password</Text>
              <TouchableOpacity onPress={() => setShowPasswordModal(false)}>
                <Ionicons name="close" size={24} color="#64748B" />
              </TouchableOpacity>
            </View>

            <View style={styles.modalContent}>
              <View style={styles.inputGroup}>
                <Text style={styles.inputLabel}>Current Password</Text>
                <TextInput
                  style={styles.passwordInput}
                  value={currentPassword}
                  onChangeText={setCurrentPassword}
                  secureTextEntry
                  placeholder="Enter current password"
                  placeholderTextColor="#94A3B8"
                />
              </View>

              <View style={styles.inputGroup}>
                <Text style={styles.inputLabel}>New Password</Text>
                <TextInput
                  style={styles.passwordInput}
                  value={newPassword}
                  onChangeText={setNewPassword}
                  secureTextEntry
                  placeholder="Enter new password (min 6 characters)"
                  placeholderTextColor="#94A3B8"
                />
              </View>

              <View style={styles.inputGroup}>
                <Text style={styles.inputLabel}>Confirm New Password</Text>
                <TextInput
                  style={styles.passwordInput}
                  value={confirmPassword}
                  onChangeText={setConfirmPassword}
                  secureTextEntry
                  placeholder="Re-enter new password"
                  placeholderTextColor="#94A3B8"
                />
              </View>

              <TouchableOpacity 
                style={styles.savePasswordButton}
                onPress={handleSavePassword}
              >
                <LinearGradient
                  colors={['#2563EB', '#1E40AF']}
                  style={styles.savePasswordGradient}
                >
                  <Text style={styles.savePasswordText}>Change Password</Text>
                </LinearGradient>
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>

      {/* Change Language Modal */}
      <Modal
        visible={showLanguageModal}
        transparent={true}
        animationType="slide"
        onRequestClose={() => setShowLanguageModal(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.languageModal}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Change Language</Text>
              <TouchableOpacity onPress={() => setShowLanguageModal(false)}>
                <Ionicons name="close" size={24} color="#64748B" />
              </TouchableOpacity>
            </View>

            <View style={styles.modalContent}>
              <Text style={styles.languageSubtitle}>Select your preferred language</Text>

              <TouchableOpacity 
                style={[
                  styles.languageOption,
                  language === 'en' && styles.languageOptionActive
                ]}
                onPress={() => handleSelectLanguage('en')}
              >
                <View style={styles.languageOptionLeft}>
                  <View style={[styles.languageIcon, { backgroundColor: '#DBEAFE' }]}>
                    <Ionicons name="language" size={24} color="#2563EB" />
                  </View>
                  <View>
                    <Text style={styles.languageName}>English</Text>
                    <Text style={styles.languageNative}>English</Text>
                  </View>
                </View>
                {language === 'en' && (
                  <Ionicons name="checkmark-circle" size={24} color="#10B981" />
                )}
              </TouchableOpacity>

              <TouchableOpacity 
                style={[
                  styles.languageOption,
                  language === 'hi' && styles.languageOptionActive
                ]}
                onPress={() => handleSelectLanguage('hi')}
              >
                <View style={styles.languageOptionLeft}>
                  <View style={[styles.languageIcon, { backgroundColor: '#FEF3C7' }]}>
                    <Ionicons name="language" size={24} color="#F59E0B" />
                  </View>
                  <View>
                    <Text style={styles.languageName}>Hindi</Text>
                    <Text style={styles.languageNative}>हिंदी</Text>
                  </View>
                </View>
                {language === 'hi' && (
                  <Ionicons name="checkmark-circle" size={24} color="#10B981" />
                )}
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
    paddingVertical: 40,
    borderBottomLeftRadius: 24,
    borderBottomRightRadius: 24,
  },
  profileHeader: {
    alignItems: 'center',
  },
  avatar: {
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: 'rgba(255, 255, 255, 0.2)',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 16,
  },
  avatarText: {
    fontSize: 32,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  businessName: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 4,
  },
  email: {
    fontSize: 14,
    color: '#E0E7FF',
  },
  content: {
    flex: 1,
    padding: 20,
  },
  section: {
    marginBottom: 24,
  },
  sectionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#1E293B',
    marginBottom: 12,
  },
  infoCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    padding: 16,
  },
  infoRow: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: '#F1F5F9',
  },
  infoText: {
    fontSize: 14,
    color: '#1E293B',
    marginLeft: 12,
  },
  editForm: {
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    padding: 16,
  },
  inputGroup: {
    marginBottom: 16,
  },
  label: {
    fontSize: 14,
    fontWeight: '600',
    color: '#1E293B',
    marginBottom: 8,
  },
  input: {
    backgroundColor: '#F8FAFC',
    padding: 12,
    borderRadius: 8,
    fontSize: 14,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  saveButton: {
    backgroundColor: '#2563EB',
    padding: 14,
    borderRadius: 8,
    alignItems: 'center',
    marginTop: 8,
  },
  saveButtonText: {
    color: '#FFFFFF',
    fontSize: 14,
    fontWeight: 'bold',
  },
  menuItem: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    backgroundColor: '#FFFFFF',
    padding: 16,
    borderRadius: 12,
    marginBottom: 8,
  },
  menuItemLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    flex: 1,
  },
  menuIcon: {
    width: 40,
    height: 40,
    borderRadius: 20,
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 12,
  },
  menuItemText: {
    fontSize: 14,
    color: '#1E293B',
    fontWeight: '500',
  },
  menuItemRight: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  menuItemValue: {
    fontSize: 12,
    color: '#64748B',
    marginRight: 8,
  },
  logoutButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#FEE2E2',
    padding: 16,
    borderRadius: 12,
    marginTop: 20,
  },
  logoutButtonText: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#EF4444',
    marginLeft: 8,
  },
  version: {
    alignItems: 'center',
    marginTop: 20,
    marginBottom: 20,
  },
  versionText: {
    fontSize: 12,
    color: '#94A3B8',
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  passwordModal: {
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    width: '90%',
    maxWidth: 400,
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 20,
    borderBottomWidth: 1,
    borderBottomColor: '#E2E8F0',
  },
  modalTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#1E293B',
  },
  modalContent: {
    padding: 20,
  },
  inputGroup: {
    marginBottom: 16,
  },
  inputLabel: {
    fontSize: 14,
    fontWeight: '600',
    color: '#475569',
    marginBottom: 8,
  },
  passwordInput: {
    backgroundColor: '#F8FAFC',
    padding: 12,
    borderRadius: 8,
    fontSize: 14,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    color: '#1E293B',
  },
  savePasswordButton: {
    marginTop: 8,
    borderRadius: 8,
    overflow: 'hidden',
  },
  savePasswordGradient: {
    padding: 14,
    alignItems: 'center',
  },
  savePasswordText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: 'bold',
  },
  languageModal: {
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    width: '90%',
    maxWidth: 400,
  },
  languageSubtitle: {
    fontSize: 14,
    color: '#64748B',
    marginBottom: 20,
    textAlign: 'center',
  },
  languageOption: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    backgroundColor: '#F8FAFC',
    padding: 16,
    borderRadius: 12,
    marginBottom: 12,
    borderWidth: 2,
    borderColor: 'transparent',
  },
  languageOptionActive: {
    borderColor: '#10B981',
    backgroundColor: '#F0FDF4',
  },
  languageOptionLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  languageIcon: {
    width: 48,
    height: 48,
    borderRadius: 24,
    justifyContent: 'center',
    alignItems: 'center',
  },
  languageName: {
    fontSize: 16,
    fontWeight: '600',
    color: '#1E293B',
  },
  languageNative: {
    fontSize: 14,
    color: '#64748B',
    marginTop: 2,
  },
});

export default ProfileScreen;
