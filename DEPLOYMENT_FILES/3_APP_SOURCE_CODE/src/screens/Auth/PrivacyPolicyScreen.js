import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import AsyncStorage from '@react-native-async-storage/async-storage';

const PrivacyPolicyScreen = ({ navigation }) => {
  const [settings, setSettings] = useState({ support_email: 'admin@biswamart.com', support_phone: '+91 7608081767' });
  useEffect(() => {
    (async () => { try { const c = await AsyncStorage.getItem('app_settings'); if (c) setSettings(JSON.parse(c)); } catch(e){} })();
  }, []);
  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
          <Ionicons name="arrow-back" size={24} color="#1E293B" />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Privacy Policy</Text>
        <View style={{ width: 24 }} />
      </View>

      <ScrollView style={styles.content} showsVerticalScrollIndicator={false}>
        <Text style={styles.lastUpdated}>Last Updated: February 13, 2026</Text>

        <Text style={styles.text}>
          BINEST ("Company", "we", "us", or "our") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our BINEST mobile application ("App"). Please read this policy carefully.
        </Text>

        <Text style={styles.sectionTitle}>1. Information We Collect</Text>
        <Text style={styles.subTitle}>1.1 Personal Information</Text>
        <Text style={styles.text}>When you create an account, we collect:</Text>
        <Text style={styles.bullet}>• Full name</Text>
        <Text style={styles.bullet}>• Business name</Text>
        <Text style={styles.bullet}>• Mobile number</Text>
        <Text style={styles.bullet}>• Email address</Text>
        <Text style={styles.bullet}>• Password (encrypted)</Text>

        <Text style={styles.subTitle}>1.2 Business Data</Text>
        <Text style={styles.text}>While using the App, you may provide:</Text>
        <Text style={styles.bullet}>• Customer information (name, contact details, address)</Text>
        <Text style={styles.bullet}>• Product and inventory details</Text>
        <Text style={styles.bullet}>• Bills, invoices, and transaction records</Text>
        <Text style={styles.bullet}>• Expense records</Text>
        <Text style={styles.bullet}>• Due/Udhari payment records</Text>
        <Text style={styles.bullet}>• Business reports and analytics data</Text>

        <Text style={styles.subTitle}>1.3 Automatically Collected Information</Text>
        <Text style={styles.text}>We may automatically collect:</Text>
        <Text style={styles.bullet}>• Device type and model</Text>
        <Text style={styles.bullet}>• Operating system version</Text>
        <Text style={styles.bullet}>• App version</Text>
        <Text style={styles.bullet}>• Usage patterns and app interaction data</Text>
        <Text style={styles.bullet}>• Error logs for debugging purposes</Text>

        <Text style={styles.sectionTitle}>2. How We Use Your Information</Text>
        <Text style={styles.text}>We use the collected information for the following purposes:</Text>
        <Text style={styles.bullet}>• To create and manage your account</Text>
        <Text style={styles.bullet}>• To provide billing, inventory, and business management services</Text>
        <Text style={styles.bullet}>• To generate business reports and analytics</Text>
        <Text style={styles.bullet}>• To send notifications and reminders (due dates, daily reports, store timings)</Text>
        <Text style={styles.bullet}>• To enable data backup and restore functionality</Text>
        <Text style={styles.bullet}>• To improve and optimize the App</Text>
        <Text style={styles.bullet}>• To provide customer support</Text>
        <Text style={styles.bullet}>• To send important updates about the App</Text>
        <Text style={styles.bullet}>• To verify your identity via OTP verification</Text>

        <Text style={styles.sectionTitle}>3. Data Storage and Security</Text>
        <Text style={styles.text}>
          3.1. Your data is stored on our secure servers hosted in India.{'\n\n'}
          3.2. We implement industry-standard security measures including:{'\n'}
          - Password encryption using secure hashing algorithms{'\n'}
          - HTTPS encryption for all data transmission{'\n'}
          - Regular security audits and updates{'\n'}
          - Access controls to limit data access to authorized personnel{'\n\n'}
          3.3. While we strive to protect your data, no method of electronic storage is 100% secure. We cannot guarantee absolute security.{'\n\n'}
          3.4. Your business data is stored in our database and is accessible only through your authenticated account.
        </Text>

        <Text style={styles.sectionTitle}>4. Data Sharing and Disclosure</Text>
        <Text style={styles.text}>
          4.1. We do NOT sell your personal or business data to any third party.{'\n\n'}
          4.2. We do NOT share your data with advertisers or marketing agencies.{'\n\n'}
          4.3. We may disclose your information only in the following circumstances:{'\n'}
          - When required by law or legal process{'\n'}
          - To protect our rights, privacy, safety, or property{'\n'}
          - To enforce our Terms & Conditions{'\n'}
          - With your explicit consent{'\n\n'}
          4.4. In case of a business transfer, merger, or acquisition, your data may be transferred to the new entity with prior notification.
        </Text>

        <Text style={styles.sectionTitle}>5. Data Retention</Text>
        <Text style={styles.text}>
          5.1. We retain your data as long as your account is active.{'\n\n'}
          5.2. Upon account deletion, your personal data will be removed from our servers within 30 days.{'\n\n'}
          5.3. Anonymized or aggregated data may be retained for analytics and service improvement purposes.{'\n\n'}
          5.4. We may retain certain data as required by applicable laws and regulations.
        </Text>

        <Text style={styles.sectionTitle}>6. Your Rights</Text>
        <Text style={styles.text}>You have the following rights regarding your data:</Text>
        <Text style={styles.bullet}>• Access: You can view all your data within the App</Text>
        <Text style={styles.bullet}>• Export: You can export your data via the backup feature</Text>
        <Text style={styles.bullet}>• Correction: You can update your personal information in Profile settings</Text>
        <Text style={styles.bullet}>• Deletion: You can request account and data deletion</Text>
        <Text style={styles.bullet}>• Portability: You can download your data in standard formats (CSV, PDF)</Text>
        <Text style={styles.bullet}>• Objection: You can opt-out of non-essential notifications</Text>

        <Text style={styles.sectionTitle}>7. Notifications</Text>
        <Text style={styles.text}>
          7.1. The App may send local notifications for:{'\n'}
          - Due date payment reminders{'\n'}
          - Daily business report summaries{'\n'}
          - Store open/close time reminders{'\n'}
          - Low stock alerts{'\n\n'}
          7.2. You can manage notification preferences within the App.{'\n\n'}
          7.3. Essential account-related notifications (security, account updates) cannot be disabled.
        </Text>

        <Text style={styles.sectionTitle}>8. Children's Privacy</Text>
        <Text style={styles.text}>
          The App is not intended for use by individuals under 18 years of age. We do not knowingly collect personal information from children. If we learn that we have collected data from a child under 18, we will take steps to delete such information promptly.
        </Text>

        <Text style={styles.sectionTitle}>9. Third-Party Services</Text>
        <Text style={styles.text}>
          9.1. The App may use the following third-party services:{'\n'}
          - Expo (App framework and notifications){'\n'}
          - AsyncStorage (Local data caching){'\n\n'}
          9.2. These third-party services have their own privacy policies. We encourage you to review them.{'\n\n'}
          9.3. We are not responsible for the privacy practices of third-party services.
        </Text>

        <Text style={styles.sectionTitle}>10. Changes to Privacy Policy</Text>
        <Text style={styles.text}>
          10.1. We may update this Privacy Policy from time to time.{'\n\n'}
          10.2. We will notify you of significant changes through in-app notifications or email.{'\n\n'}
          10.3. Your continued use of the App after changes constitutes acceptance of the updated Privacy Policy.{'\n\n'}
          10.4. We recommend reviewing this policy periodically.
        </Text>

        <Text style={styles.sectionTitle}>11. Contact Us</Text>
        <Text style={styles.text}>
          If you have any questions, concerns, or requests regarding this Privacy Policy or your data, please contact us:{'\n\n'}
          Email: {settings.support_email}{'\n'}
          Phone: {settings.support_phone}{'\n'}
          App: BINEST Business Manager
        </Text>

        <View style={styles.footer}>
          <Text style={styles.footerText}>By using BINEST, you acknowledge that you have read and understood this Privacy Policy.</Text>
        </View>

        <View style={{ height: 40 }} />
      </ScrollView>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#FFFFFF' },
  header: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingHorizontal: 16, paddingVertical: 14, borderBottomWidth: 1, borderBottomColor: '#E2E8F0' },
  backBtn: { padding: 4 },
  headerTitle: { fontSize: 18, fontWeight: 'bold', color: '#1E293B' },
  content: { flex: 1, paddingHorizontal: 20, paddingTop: 16 },
  lastUpdated: { fontSize: 12, color: '#94A3B8', marginBottom: 20, fontStyle: 'italic' },
  sectionTitle: { fontSize: 16, fontWeight: 'bold', color: '#1E293B', marginTop: 20, marginBottom: 10 },
  subTitle: { fontSize: 14, fontWeight: 'bold', color: '#334155', marginTop: 12, marginBottom: 6 },
  text: { fontSize: 14, color: '#475569', lineHeight: 22 },
  bullet: { fontSize: 14, color: '#475569', lineHeight: 24, paddingLeft: 12 },
  footer: { marginTop: 24, padding: 16, backgroundColor: '#F1F5F9', borderRadius: 10 },
  footerText: { fontSize: 13, color: '#64748B', textAlign: 'center', fontStyle: 'italic' },
});

export default PrivacyPolicyScreen;
