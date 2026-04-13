import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, Alert, ActivityIndicator, Image, KeyboardAvoidingView, Platform, ScrollView } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { sendOTP } from '../../services/api';

const ForgotPasswordScreen = ({ navigation }) => {
  const [email, setEmail] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSendOTP = async () => {
    if (!email) {
      Alert.alert('Error', 'Please enter your email address');
      return;
    }

    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      Alert.alert('Error', 'Please enter a valid email address');
      return;
    }

    setLoading(true);

    setLoading(true);
    try {
      const result = await sendOTP(email, 'password_reset');

      if (result.success) {
        Alert.alert(
          'OTP Sent!',
          'A 6-digit OTP has been sent to your email address.',
          [
            {
              text: 'OK',
              onPress: () => navigation.navigate('OTPVerification', { // Changed from VerifyOTP
                email: email,
                purpose: 'password_reset'
              })
            }
          ]
        );
      } else {
        Alert.alert('Error', result.error || 'Failed to send OTP');
      }
    } catch (error) {
      // silent
      Alert.alert('Error', 'An error occurred. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <LinearGradient colors={['#1E40AF', '#2563EB', '#3B82F6']} style={styles.container}>
      <SafeAreaView style={styles.safeArea}>
        <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : 'height'} style={{ flex: 1 }}>
          <ScrollView contentContainerStyle={styles.scrollContent} showsVerticalScrollIndicator={false}>

            {/* Top Branding */}
            <View style={styles.topSection}>
              <View style={styles.logoWrapper}>
                <Image source={require('../../../assets/icon.png')} style={styles.logo} resizeMode="cover" />
              </View>
              <Text style={styles.appName}>Binest</Text>
              <Text style={styles.tagline}>Business Management App</Text>
            </View>

            {/* White Card */}
            <View style={styles.card}>

              {/* Lock Icon */}
              <View style={styles.iconCircle}>
                <Ionicons name="lock-open-outline" size={36} color="#2563EB" />
              </View>

              <Text style={styles.cardTitle}>Forgot Password?</Text>
              <Text style={styles.cardSubtitle}>Enter your email to receive a verification OTP</Text>

              {/* Email Input */}
              <View style={styles.inputWrapper}>
                <Ionicons name="mail-outline" size={20} color="#64748B" style={styles.inputIcon} />
                <TextInput
                  style={styles.input}
                  placeholder="Email address"
                  placeholderTextColor="#94A3B8"
                  value={email}
                  onChangeText={setEmail}
                  keyboardType="email-address"
                  autoCapitalize="none"
                  autoComplete="email"
                />
              </View>

              {/* Send OTP Button */}
              <TouchableOpacity
                style={[styles.sendButton, loading && styles.sendButtonDisabled]}
                onPress={handleSendOTP}
                disabled={loading}
              >
                <LinearGradient colors={['#2563EB', '#1E40AF']} style={styles.sendButtonGradient}>
                  {loading ? (
                    <ActivityIndicator color="#FFFFFF" />
                  ) : (
                    <>
                      <Ionicons name="send-outline" size={18} color="#FFFFFF" />
                      <Text style={styles.sendButtonText}>Send OTP</Text>
                    </>
                  )}
                </LinearGradient>
              </TouchableOpacity>

              {/* Back to Login */}
              <TouchableOpacity style={styles.backContainer} onPress={() => navigation.goBack()}>
                <Ionicons name="arrow-back" size={16} color="#2563EB" />
                <Text style={styles.backLink}>  Back to Login</Text>
              </TouchableOpacity>

            </View>

          </ScrollView>
        </KeyboardAvoidingView>
      </SafeAreaView>
    </LinearGradient>
  );
};

const styles = StyleSheet.create({
  container: { flex: 1 },
  safeArea: { flex: 1 },
  scrollContent: { flexGrow: 1 },
  topSection: {
    alignItems: 'center',
    paddingTop: 60,
    paddingBottom: 36,
  },
  logoWrapper: {
    width: 110,
    height: 110,
    borderRadius: 28,
    overflow: 'hidden',
    marginBottom: 16,
  },
  logo: {
    width: 110,
    height: 110,
  },
  appName: {
    fontSize: 52,
    fontWeight: '900',
    color: '#FFFFFF',
    letterSpacing: 2,
    marginBottom: 6,
  },
  tagline: {
    fontSize: 14,
    color: '#BFDBFE',
    letterSpacing: 1,
  },
  card: {
    backgroundColor: '#FFFFFF',
    borderTopLeftRadius: 36,
    borderTopRightRadius: 36,
    padding: 28,
    paddingTop: 32,
    paddingBottom: 56,
    elevation: 12,
    alignItems: 'center',
  },
  iconCircle: {
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: '#EFF6FF',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 20,
    marginTop: 8,
  },
  cardTitle: {
    fontSize: 24,
    fontWeight: '800',
    color: '#1E293B',
    marginBottom: 8,
    textAlign: 'center',
  },
  cardSubtitle: {
    fontSize: 13,
    color: '#64748B',
    marginBottom: 28,
    textAlign: 'center',
    lineHeight: 20,
    paddingHorizontal: 10,
  },
  inputWrapper: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#F8FAFC',
    borderRadius: 14,
    borderWidth: 1.5,
    borderColor: '#E2E8F0',
    marginBottom: 20,
    paddingHorizontal: 14,
    width: '100%',
  },
  inputIcon: { marginRight: 10 },
  input: {
    flex: 1,
    fontSize: 15,
    color: '#1E293B',
    paddingVertical: 15,
  },
  sendButton: {
    borderRadius: 14,
    overflow: 'hidden',
    width: '100%',
    marginBottom: 20,
  },
  sendButtonDisabled: { opacity: 0.7 },
  sendButtonGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 10,
    paddingVertical: 17,
  },
  sendButtonText: {
    color: '#FFFFFF',
    fontSize: 17,
    fontWeight: '700',
  },
  backContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
  },
  backLink: {
    color: '#2563EB',
    fontSize: 14,
    fontWeight: '700',
  },
});

export default ForgotPasswordScreen;
