import React, { useState, useEffect } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, Alert, ActivityIndicator, Image } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';

const VerifyOTPScreen = ({ navigation, route }) => {
  const { email, purpose } = route.params;
  const [otp, setOtp] = useState('');
  const [loading, setLoading] = useState(false);
  const [resendTimer, setResendTimer] = useState(60);
  const [canResend, setCanResend] = useState(false);

  useEffect(() => {
    if (resendTimer > 0) {
      const timer = setTimeout(() => setResendTimer(resendTimer - 1), 1000);
      return () => clearTimeout(timer);
    } else {
      setCanResend(true);
    }
  }, [resendTimer]);

  const handleVerifyOTP = async () => {
    if (!otp || otp.length !== 6) {
      Alert.alert('Error', 'Please enter a valid 6-digit OTP');
      return;
    }

    setLoading(true);

    // Simulate OTP verification (will integrate with backend later)
    setTimeout(() => {
      setLoading(false);
      
      // For demo purposes, accept any 6-digit OTP
      if (otp.length === 6) {
        Alert.alert(
          'Success',
          'OTP verified successfully!',
          [
            {
              text: 'OK',
              onPress: () => navigation.navigate('ResetPassword', { email: email })
            }
          ]
        );
      } else {
        Alert.alert('Error', 'Invalid OTP. Please try again.');
      }
    }, 1500);
  };

  const handleResendOTP = () => {
    if (!canResend) return;

    setCanResend(false);
    setResendTimer(60);
    
    Alert.alert('Success', 'OTP has been resent to your email');
  };

  return (
    <LinearGradient
      colors={['#2563EB', '#1E40AF']}
      style={styles.container}
    >
      <SafeAreaView style={styles.safeArea}>
        <View style={styles.content}>
          <View style={styles.header}>
            <Image 
              source={require('../../../assets/icon.png')} 
              style={styles.logo}
              resizeMode="contain"
            />
            <Text style={styles.appName}>BINEST</Text>
            <Text style={styles.title}>Verify OTP</Text>
            <Text style={styles.subtitle}>
              Enter the 6-digit code sent to{'\n'}
              <Text style={styles.email}>{email}</Text>
            </Text>
          </View>

          <View style={styles.form}>
            <View style={styles.inputContainer}>
              <Text style={styles.label}>OTP Code</Text>
              <TextInput
                style={styles.input}
                placeholder="Enter 6-digit OTP"
                placeholderTextColor="#94A3B8"
                value={otp}
                onChangeText={setOtp}
                keyboardType="number-pad"
                maxLength={6}
                autoFocus
              />
            </View>

            <TouchableOpacity 
              style={[styles.verifyButton, loading && styles.verifyButtonDisabled]} 
              onPress={handleVerifyOTP}
              disabled={loading}
            >
              {loading ? (
                <ActivityIndicator color="#2563EB" />
              ) : (
                <Text style={styles.verifyButtonText}>Verify OTP</Text>
              )}
            </TouchableOpacity>

            <View style={styles.resendContainer}>
              {canResend ? (
                <TouchableOpacity onPress={handleResendOTP}>
                  <Text style={styles.resendLink}>Resend OTP</Text>
                </TouchableOpacity>
              ) : (
                <Text style={styles.resendText}>
                  Resend OTP in {resendTimer} seconds
                </Text>
              )}
            </View>

            <View style={styles.backContainer}>
              <TouchableOpacity onPress={() => navigation.goBack()}>
                <Text style={styles.backLink}>← Change Email</Text>
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </SafeAreaView>
    </LinearGradient>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  safeArea: {
    flex: 1,
  },
  content: {
    flex: 1,
    padding: 20,
    justifyContent: 'center',
  },
  header: {
    alignItems: 'center',
    marginBottom: 40,
  },
  logo: {
    width: 70,
    height: 70,
    marginBottom: 12,
  },
  appName: {
    fontSize: 32,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 8,
    letterSpacing: 3,
  },
  title: {
    fontSize: 28,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 10,
  },
  subtitle: {
    fontSize: 14,
    color: '#E0E7FF',
    textAlign: 'center',
  },
  email: {
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  form: {
    backgroundColor: 'rgba(255, 255, 255, 0.1)',
    padding: 20,
    borderRadius: 16,
  },
  inputContainer: {
    marginBottom: 20,
  },
  label: {
    fontSize: 14,
    color: '#FFFFFF',
    marginBottom: 8,
    fontWeight: '600',
  },
  input: {
    backgroundColor: '#FFFFFF',
    padding: 15,
    borderRadius: 10,
    fontSize: 24,
    color: '#1E293B',
    textAlign: 'center',
    letterSpacing: 8,
    fontWeight: 'bold',
  },
  verifyButton: {
    backgroundColor: '#FFFFFF',
    padding: 18,
    borderRadius: 10,
    alignItems: 'center',
    marginTop: 10,
  },
  verifyButtonDisabled: {
    opacity: 0.7,
  },
  verifyButtonText: {
    color: '#2563EB',
    fontSize: 18,
    fontWeight: 'bold',
  },
  resendContainer: {
    alignItems: 'center',
    marginTop: 20,
  },
  resendText: {
    color: '#E0E7FF',
    fontSize: 14,
  },
  resendLink: {
    color: '#FFFFFF',
    fontSize: 14,
    fontWeight: 'bold',
    textDecorationLine: 'underline',
  },
  backContainer: {
    alignItems: 'center',
    marginTop: 15,
  },
  backLink: {
    color: '#FFFFFF',
    fontSize: 14,
    fontWeight: '600',
  },
});

export default VerifyOTPScreen;
