import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, Linking } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import AsyncStorage from '@react-native-async-storage/async-storage';

const HelpSupportScreen = ({ navigation }) => {
  const [settings, setSettings] = useState({
    support_email: 'admin@biswamart.com',
    support_phone: '+91 7608081767',
    app_version: '1.0.1',
  });

  useEffect(() => {
    loadSettings();
  }, []);

  const loadSettings = async () => {
    try {
      const cached = await AsyncStorage.getItem('app_settings');
      if (cached) setSettings(JSON.parse(cached));
    } catch (e) {}
  };

  const openEmail = () => {
    Linking.openURL(`mailto:${settings.support_email}?subject=BINEST App Support`).catch(() => {});
  };

  const openPhone = () => {
    Linking.openURL(`tel:${settings.support_phone}`).catch(() => {});
  };

  const openWhatsApp = () => {
    const phone = settings.support_phone.replace(/[^0-9]/g, '');
    Linking.openURL(`https://wa.me/${phone}?text=Hi, I need help with BINEST App`).catch(() => {});
  };

  return (
    <SafeAreaView style={styles.container}>
      <LinearGradient colors={['#2563EB', '#1E40AF']} style={styles.header}>
        <View style={styles.headerTop}>
          <TouchableOpacity onPress={() => navigation.goBack()}>
            <Ionicons name="arrow-back" size={24} color="#FFFFFF" />
          </TouchableOpacity>
          <Text style={styles.headerTitle}>Help & Support</Text>
          <View style={{ width: 24 }} />
        </View>
        <Text style={styles.headerSub}>We're here to help you 24/7</Text>
      </LinearGradient>

      <ScrollView style={styles.content}>

        {/* Contact Cards */}
        <Text style={styles.sectionTitle}>Contact Us</Text>

        <TouchableOpacity style={styles.contactCard} onPress={openPhone}>
          <View style={[styles.contactIcon, { backgroundColor: '#D1FAE5' }]}>
            <Ionicons name="call" size={24} color="#10B981" />
          </View>
          <View style={styles.contactInfo}>
            <Text style={styles.contactLabel}>Phone Support</Text>
            <Text style={styles.contactValue}>{settings.support_phone}</Text>
            <Text style={styles.contactHint}>Tap to call</Text>
          </View>
          <Ionicons name="chevron-forward" size={20} color="#94A3B8" />
        </TouchableOpacity>

        <TouchableOpacity style={styles.contactCard} onPress={openEmail}>
          <View style={[styles.contactIcon, { backgroundColor: '#DBEAFE' }]}>
            <Ionicons name="mail" size={24} color="#2563EB" />
          </View>
          <View style={styles.contactInfo}>
            <Text style={styles.contactLabel}>Email Support</Text>
            <Text style={styles.contactValue}>{settings.support_email}</Text>
            <Text style={styles.contactHint}>Tap to send email</Text>
          </View>
          <Ionicons name="chevron-forward" size={20} color="#94A3B8" />
        </TouchableOpacity>

        <TouchableOpacity style={styles.contactCard} onPress={openWhatsApp}>
          <View style={[styles.contactIcon, { backgroundColor: '#D1FAE5' }]}>
            <Ionicons name="logo-whatsapp" size={24} color="#25D366" />
          </View>
          <View style={styles.contactInfo}>
            <Text style={styles.contactLabel}>WhatsApp</Text>
            <Text style={styles.contactValue}>{settings.support_phone}</Text>
            <Text style={styles.contactHint}>Tap to chat on WhatsApp</Text>
          </View>
          <Ionicons name="chevron-forward" size={20} color="#94A3B8" />
        </TouchableOpacity>

        {/* FAQ Section */}
        <Text style={styles.sectionTitle}>Frequently Asked Questions</Text>

        <View style={styles.faqCard}>
          <Text style={styles.faqQ}>Q: Mera data safe hai?</Text>
          <Text style={styles.faqA}>A: Haan, aapka data hamari secure servers pe encrypted store hota hai. Aap backup bhi le sakte hain.</Text>
        </View>

        <View style={styles.faqCard}>
          <Text style={styles.faqQ}>Q: Bill print kaise karein?</Text>
          <Text style={styles.faqA}>A: Bill banane ke baad Invoice screen pe "Print" button dabayein. PDF bhi share kar sakte hain.</Text>
        </View>

        <View style={styles.faqCard}>
          <Text style={styles.faqQ}>Q: Due payment kaise track karein?</Text>
          <Text style={styles.faqA}>A: Bill banate waqt payment mode "Due" select karein aur due date set karein. Dashboard se Due section me sab dikh jayega.</Text>
        </View>

        <View style={styles.faqCard}>
          <Text style={styles.faqQ}>Q: Data backup kaise lein?</Text>
          <Text style={styles.faqA}>A: Profile &gt; Backup & Restore me jaake backup le sakte hain aur restore bhi kar sakte hain.</Text>
        </View>

        <View style={styles.faqCard}>
          <Text style={styles.faqQ}>Q: Stock kam hone pe alert aayega?</Text>
          <Text style={styles.faqA}>A: Haan, jab stock low hoga to Dashboard ke notification bell me alert dikhega.</Text>
        </View>

        {/* Legal */}
        <Text style={styles.sectionTitle}>Legal</Text>

        <TouchableOpacity style={styles.legalItem} onPress={() => navigation.navigate('TermsConditions')}>
          <Ionicons name="document-text" size={20} color="#2563EB" />
          <Text style={styles.legalText}>Terms & Conditions</Text>
          <Ionicons name="chevron-forward" size={18} color="#94A3B8" />
        </TouchableOpacity>

        <TouchableOpacity style={styles.legalItem} onPress={() => navigation.navigate('PrivacyPolicy')}>
          <Ionicons name="shield-checkmark" size={20} color="#10B981" />
          <Text style={styles.legalText}>Privacy Policy</Text>
          <Ionicons name="chevron-forward" size={18} color="#94A3B8" />
        </TouchableOpacity>

        {/* App Info */}
        <View style={styles.appInfo}>
          <Text style={styles.appInfoTitle}>BINEST</Text>
          <Text style={styles.appInfoVersion}>Version {settings.app_version}</Text>
          <Text style={styles.appInfoCopy}>© 2026 Binest. All rights reserved.</Text>
        </View>

        <View style={{ height: 30 }} />
      </ScrollView>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F8FAFC' },
  header: { paddingTop: 20, paddingBottom: 20, paddingHorizontal: 20 },
  headerTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  headerTitle: { fontSize: 20, fontWeight: 'bold', color: '#FFFFFF' },
  headerSub: { color: '#E0E7FF', fontSize: 13, marginTop: 8 },
  content: { flex: 1, padding: 16 },
  sectionTitle: { fontSize: 16, fontWeight: 'bold', color: '#1E293B', marginTop: 16, marginBottom: 12 },
  contactCard: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#FFF', padding: 16, borderRadius: 14, marginBottom: 10, elevation: 2 },
  contactIcon: { width: 48, height: 48, borderRadius: 24, alignItems: 'center', justifyContent: 'center', marginRight: 14 },
  contactInfo: { flex: 1 },
  contactLabel: { fontSize: 14, fontWeight: 'bold', color: '#1E293B' },
  contactValue: { fontSize: 15, color: '#2563EB', fontWeight: '600', marginTop: 2 },
  contactHint: { fontSize: 11, color: '#94A3B8', marginTop: 2 },
  faqCard: { backgroundColor: '#FFF', padding: 14, borderRadius: 12, marginBottom: 8, elevation: 1 },
  faqQ: { fontSize: 14, fontWeight: 'bold', color: '#1E293B', marginBottom: 6 },
  faqA: { fontSize: 13, color: '#64748B', lineHeight: 20 },
  legalItem: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#FFF', padding: 14, borderRadius: 12, marginBottom: 8, elevation: 1 },
  legalText: { flex: 1, fontSize: 14, fontWeight: '600', color: '#1E293B', marginLeft: 12 },
  appInfo: { alignItems: 'center', paddingVertical: 24, marginTop: 10 },
  appInfoTitle: { fontSize: 22, fontWeight: 'bold', color: '#2563EB', letterSpacing: 2 },
  appInfoVersion: { fontSize: 13, color: '#94A3B8', marginTop: 4 },
  appInfoCopy: { fontSize: 12, color: '#CBD5E1', marginTop: 4 },
});

export default HelpSupportScreen;
