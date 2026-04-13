import React, { useState, useContext } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, Alert, ScrollView, ActivityIndicator, Image } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { LinearGradient } from 'expo-linear-gradient';
import { AppContext } from '../../context/AppContext';
import { sendOTP } from '../../services/api';

const RegisterScreen = ({ navigation }) => {
  const [name, setName] = useState('');
  const [businessName, setBusinessName] = useState('');
  const [mobile, setMobile] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [agreedToTerms, setAgreedToTerms] = useState(false);
  const { register } = useContext(AppContext);

  const handleRegister = async () => {
    if (!name || !businessName || !mobile || !email || !password || !confirmPassword) {
      Alert.alert('Error', 'Please fill in all fields');
      return;
    }

    if (!agreedToTerms) {
      Alert.alert('Terms & Conditions', 'Please agree to Terms & Conditions and Privacy Policy to continue.');
      return;
    }

    if (password !== confirmPassword) {
      Alert.alert('Error', 'Passwords do not match');
      return;
    }

    if (mobile.length !== 10) {
      Alert.alert('Error', 'Please enter a valid 10-digit mobile number');
      return;
    }

    setLoading(true);
    try {
      const userData = {
        name,
        businessName,
        mobile,
        email,
        password,
      };

      const result = await sendOTP(email, 'registration');

      if (result.success) {
        Alert.alert('Success', `Verification code sent to ${email}`, [
          { text: 'OK', onPress: () => navigation.navigate('OTPVerification', { email, purpose: 'registration', userData }) }
        ]);
      } else {
        Alert.alert('Error', result.error || 'Failed to send OTP');
      }
    } catch (error) {
      // silent
      Alert.alert('Error', 'Failed to initiate registration. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <LinearGradient
      colors={['#2563EB', '#1E40AF']}
      style={styles.container}
    >
      <SafeAreaView style={styles.safeArea}>
        <ScrollView contentContainerStyle={styles.scrollContent}>
          <View style={styles.content}>
            <View style={styles.header}>
              <Image
                source={require('../../../assets/icon.png')}
                style={styles.logo}
                resizeMode="contain"
              />
              <Text style={styles.appName}>BINEST</Text>
              <Text style={styles.title}>Create Account</Text>
              <Text style={styles.subtitle}>Start managing your business</Text>
            </View>

            <View style={styles.form}>
              <View style={styles.inputContainer}>
                <Text style={styles.label}>Your Name</Text>
                <TextInput
                  style={styles.input}
                  placeholder="Enter your name"
                  placeholderTextColor="#94A3B8"
                  value={name}
                  onChangeText={setName}
                />
              </View>

              <View style={styles.inputContainer}>
                <Text style={styles.label}>Business Name</Text>
                <TextInput
                  style={styles.input}
                  placeholder="Enter business name"
                  placeholderTextColor="#94A3B8"
                  value={businessName}
                  onChangeText={setBusinessName}
                />
              </View>

              <View style={styles.inputContainer}>
                <Text style={styles.label}>Mobile Number</Text>
                <TextInput
                  style={styles.input}
                  placeholder="Enter 10-digit mobile number"
                  placeholderTextColor="#94A3B8"
                  value={mobile}
                  onChangeText={setMobile}
                  keyboardType="phone-pad"
                  maxLength={10}
                />
              </View>

              <View style={styles.inputContainer}>
                <Text style={styles.label}>Email</Text>
                <TextInput
                  style={styles.input}
                  placeholder="Enter your email"
                  placeholderTextColor="#94A3B8"
                  value={email}
                  onChangeText={setEmail}
                  keyboardType="email-address"
                  autoCapitalize="none"
                />
              </View>

              <View style={styles.inputContainer}>
                <Text style={styles.label}>Password</Text>
                <TextInput
                  style={styles.input}
                  placeholder="Create password"
                  placeholderTextColor="#94A3B8"
                  value={password}
                  onChangeText={setPassword}
                  secureTextEntry
                />
              </View>

              <View style={styles.inputContainer}>
                <Text style={styles.label}>Confirm Password</Text>
                <TextInput
                  style={styles.input}
                  placeholder="Confirm password"
                  placeholderTextColor="#94A3B8"
                  value={confirmPassword}
                  onChangeText={setConfirmPassword}
                  secureTextEntry
                />
              </View>

              <View style={styles.termsContainer}>
                <TouchableOpacity style={styles.checkbox} onPress={() => setAgreedToTerms(!agreedToTerms)}>
                  <View style={[styles.checkboxBox, agreedToTerms && styles.checkboxChecked]}>
                    {agreedToTerms && <Text style={styles.checkMark}>✓</Text>}
                  </View>
                </TouchableOpacity>
                <View style={styles.termsTextWrap}>
                  <Text style={styles.termsText}>I agree to the </Text>
                  <TouchableOpacity onPress={() => navigation.navigate('TermsConditions')}>
                    <Text style={styles.termsLink}>Terms & Conditions</Text>
                  </TouchableOpacity>
                  <Text style={styles.termsText}> and </Text>
                  <TouchableOpacity onPress={() => navigation.navigate('PrivacyPolicy')}>
                    <Text style={styles.termsLink}>Privacy Policy</Text>
                  </TouchableOpacity>
                </View>
              </View>

              <TouchableOpacity
                style={[styles.registerButton, (loading || !agreedToTerms) && styles.registerButtonDisabled]}
                onPress={handleRegister}
                disabled={loading || !agreedToTerms}
              >
                {loading ? (
                  <ActivityIndicator color="#FFFFFF" />
                ) : (
                  <Text style={styles.registerButtonText}>Create Account</Text>
                )}
              </TouchableOpacity>

              <View style={styles.loginContainer}>
                <Text style={styles.loginText}>Already have an account? </Text>
                <TouchableOpacity onPress={() => navigation.navigate('Login')}>
                  <Text style={styles.loginLink}>Login</Text>
                </TouchableOpacity>
              </View>
            </View>
          </View>
        </ScrollView>
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
  scrollContent: {
    flexGrow: 1,
  },
  content: {
    flex: 1,
    padding: 20,
    justifyContent: 'center',
  },
  header: {
    alignItems: 'center',
    marginBottom: 30,
    marginTop: 20,
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
    marginBottom: 8,
  },
  subtitle: {
    fontSize: 14,
    color: '#E0E7FF',
  },
  form: {
    backgroundColor: 'rgba(255, 255, 255, 0.1)',
    padding: 20,
    borderRadius: 16,
  },
  inputContainer: {
    marginBottom: 16,
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
    fontSize: 16,
    color: '#1E293B',
  },
  registerButton: {
    backgroundColor: '#FFFFFF',
    padding: 18,
    borderRadius: 10,
    alignItems: 'center',
    marginTop: 10,
  },
  registerButtonDisabled: {
    opacity: 0.7,
  },
  registerButtonText: {
    color: '#2563EB',
    fontSize: 18,
    fontWeight: 'bold',
  },
  loginContainer: {
    flexDirection: 'row',
    justifyContent: 'center',
    marginTop: 20,
  },
  loginText: {
    color: '#E0E7FF',
    fontSize: 14,
  },
  loginLink: {
    color: '#FFFFFF',
    fontSize: 14,
    fontWeight: 'bold',
  },
  termsContainer: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    marginTop: 12,
    marginBottom: 4,
  },
  checkbox: {
    paddingTop: 2,
    marginRight: 10,
  },
  checkboxBox: {
    width: 22,
    height: 22,
    borderRadius: 4,
    borderWidth: 2,
    borderColor: '#FFFFFF',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'transparent',
  },
  checkboxChecked: {
    backgroundColor: '#FFFFFF',
    borderColor: '#FFFFFF',
  },
  checkMark: {
    color: '#2563EB',
    fontSize: 14,
    fontWeight: 'bold',
  },
  termsTextWrap: {
    flex: 1,
    flexDirection: 'row',
    flexWrap: 'wrap',
    alignItems: 'center',
  },
  termsText: {
    color: '#E0E7FF',
    fontSize: 13,
  },
  termsLink: {
    color: '#FFFFFF',
    fontSize: 13,
    fontWeight: 'bold',
    textDecorationLine: 'underline',
  },
});

export default RegisterScreen;
