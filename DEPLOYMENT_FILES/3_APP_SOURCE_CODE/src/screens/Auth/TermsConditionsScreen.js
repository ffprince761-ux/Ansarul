import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import AsyncStorage from '@react-native-async-storage/async-storage';

const TermsConditionsScreen = ({ navigation }) => {
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
        <Text style={styles.headerTitle}>Terms & Conditions</Text>
        <View style={{ width: 24 }} />
      </View>

      <ScrollView style={styles.content} showsVerticalScrollIndicator={false}>
        <Text style={styles.lastUpdated}>Last Updated: February 13, 2026</Text>

        <Text style={styles.sectionTitle}>1. Acceptance of Terms</Text>
        <Text style={styles.text}>
          By downloading, installing, or using the BINEST application ("App"), you agree to be bound by these Terms and Conditions ("Terms"). If you do not agree to these Terms, please do not use the App. These Terms constitute a legally binding agreement between you ("User") and BINEST ("Company", "we", "us", or "our").
        </Text>

        <Text style={styles.sectionTitle}>2. Description of Service</Text>
        <Text style={styles.text}>
          BINEST is a comprehensive business management application designed for small and medium businesses. The App provides the following services:
        </Text>
        <Text style={styles.bullet}>• Billing and invoicing system</Text>
        <Text style={styles.bullet}>• Inventory and stock management</Text>
        <Text style={styles.bullet}>• Customer relationship management (CRM)</Text>
        <Text style={styles.bullet}>• Expense tracking and reporting</Text>
        <Text style={styles.bullet}>• Due/Udhari payment tracking</Text>
        <Text style={styles.bullet}>• Business analytics and reports</Text>
        <Text style={styles.bullet}>• Notification and reminder system</Text>
        <Text style={styles.bullet}>• Data backup and restore functionality</Text>

        <Text style={styles.sectionTitle}>3. User Account</Text>
        <Text style={styles.text}>
          3.1. To use the App, you must create an account by providing accurate and complete information including your name, business name, mobile number, and email address.{'\n\n'}
          3.2. You are responsible for maintaining the confidentiality of your account credentials. Any activity that occurs under your account is your responsibility.{'\n\n'}
          3.3. You must be at least 18 years of age to create an account and use the App.{'\n\n'}
          3.4. You agree to provide truthful and accurate information during registration. Providing false information may result in account suspension or termination.{'\n\n'}
          3.5. One account per user/business is permitted. Multiple accounts for the same business are not allowed.
        </Text>

        <Text style={styles.sectionTitle}>4. User Responsibilities</Text>
        <Text style={styles.text}>
          4.1. You agree to use the App only for lawful business purposes.{'\n\n'}
          4.2. You shall not use the App to:{'\n'}
          - Engage in any fraudulent or illegal activities{'\n'}
          - Transmit any harmful, threatening, or abusive content{'\n'}
          - Attempt to gain unauthorized access to other users' data{'\n'}
          - Reverse engineer, decompile, or disassemble the App{'\n'}
          - Use the App for any purpose that violates applicable laws{'\n\n'}
          4.3. You are solely responsible for the accuracy of all business data, invoices, and financial records entered into the App.{'\n\n'}
          4.4. You agree to maintain regular backups of your data. While we provide backup functionality, we are not liable for data loss.
        </Text>

        <Text style={styles.sectionTitle}>5. Data and Privacy</Text>
        <Text style={styles.text}>
          5.1. Your use of the App is also governed by our Privacy Policy, which is incorporated into these Terms by reference.{'\n\n'}
          5.2. We collect and store your business data on our secure servers to provide our services.{'\n\n'}
          5.3. We do not sell, share, or distribute your personal or business data to any third parties without your explicit consent.{'\n\n'}
          5.4. You retain ownership of all business data you enter into the App. We are granted a limited license to use this data solely for providing and improving our services.
        </Text>

        <Text style={styles.sectionTitle}>6. Intellectual Property</Text>
        <Text style={styles.text}>
          6.1. The App, including its design, code, features, logos, and content, is the intellectual property of BINEST and is protected by copyright and trademark laws.{'\n\n'}
          6.2. You are granted a limited, non-exclusive, non-transferable license to use the App for your business purposes.{'\n\n'}
          6.3. You may not copy, modify, distribute, sell, or lease any part of the App without our prior written consent.
        </Text>

        <Text style={styles.sectionTitle}>7. Service Availability</Text>
        <Text style={styles.text}>
          7.1. We strive to ensure the App is available at all times. However, we do not guarantee uninterrupted or error-free service.{'\n\n'}
          7.2. We may temporarily suspend the App for maintenance, updates, or improvements without prior notice.{'\n\n'}
          7.3. We reserve the right to modify, update, or discontinue any features of the App at any time.
        </Text>

        <Text style={styles.sectionTitle}>8. Limitation of Liability</Text>
        <Text style={styles.text}>
          8.1. The App is provided "as is" and "as available" without warranties of any kind, either express or implied.{'\n\n'}
          8.2. We shall not be liable for any direct, indirect, incidental, special, consequential, or punitive damages arising from your use of the App.{'\n\n'}
          8.3. We are not responsible for any financial losses, business decisions, or accounting errors resulting from the use of the App.{'\n\n'}
          8.4. Our total liability shall not exceed the amount paid by you (if any) for the App in the 12 months preceding the claim.
        </Text>

        <Text style={styles.sectionTitle}>9. Account Termination</Text>
        <Text style={styles.text}>
          9.1. You may delete your account at any time through the App settings.{'\n\n'}
          9.2. We reserve the right to suspend or terminate your account if you violate these Terms or engage in any unauthorized activities.{'\n\n'}
          9.3. Upon termination, your data may be deleted from our servers after a reasonable retention period.
        </Text>

        <Text style={styles.sectionTitle}>10. Changes to Terms</Text>
        <Text style={styles.text}>
          10.1. We reserve the right to modify these Terms at any time. Updated Terms will be posted within the App.{'\n\n'}
          10.2. Your continued use of the App after changes constitutes acceptance of the modified Terms.{'\n\n'}
          10.3. We will notify users of significant changes via in-app notifications or email.
        </Text>

        <Text style={styles.sectionTitle}>11. Governing Law</Text>
        <Text style={styles.text}>
          These Terms shall be governed by and construed in accordance with the laws of India. Any disputes arising under these Terms shall be subject to the exclusive jurisdiction of the courts in India.
        </Text>

        <Text style={styles.sectionTitle}>12. Contact Us</Text>
        <Text style={styles.text}>
          If you have any questions about these Terms & Conditions, please contact us at:{'\n\n'}
          Email: {settings.support_email}{'\n'}
          Phone: {settings.support_phone}{'\n'}
          App: BINEST Business Manager
        </Text>

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
  text: { fontSize: 14, color: '#475569', lineHeight: 22 },
  bullet: { fontSize: 14, color: '#475569', lineHeight: 24, paddingLeft: 12 },
});

export default TermsConditionsScreen;
