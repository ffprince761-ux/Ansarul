import React, { useState, useEffect, useContext } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, Alert, ActivityIndicator, Image } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { sendOTP, resendOTP, verifyAndRegister, resetPassword, API_URL } from '../../services/api';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { AppContext } from '../../context/AppContext';

const OTPVerificationScreen = ({ route, navigation }) => {
    const { email, purpose, userData, newPassword } = route.params;
    const [otp, setOtp] = useState('');
    const [loading, setLoading] = useState(false);
    const [timer, setTimer] = useState(600); // 10 minutes
    const [canResend, setCanResend] = useState(false);

    // Use saveUser from AppContext to update global state
    const { saveUser } = useContext(AppContext);

    useEffect(() => {
        const interval = setInterval(() => {
            setTimer((prevTimer) => {
                if (prevTimer <= 1) {
                    clearInterval(interval);
                    setCanResend(true);
                    return 0;
                }
                return prevTimer - 1;
            });
        }, 1000);

        return () => clearInterval(interval);
    }, []);

    const formatTime = (seconds) => {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        return `${minutes}:${remainingSeconds < 10 ? '0' : ''}${remainingSeconds}`;
    };

    const handleVerify = async () => {
        if (otp.length !== 6) {
            Alert.alert('Error', 'Please enter a valid 6-digit OTP');
            return;
        }

        setLoading(true);
        try {
            if (purpose === 'registration') {
                const result = await verifyAndRegister(userData, otp);
                if (result.success) {
                    // Login user locally first
                    await AsyncStorage.setItem('userToken', 'logged_in');

                    // Save user to context (this updates local storage AND context state)
                    if (result.user && saveUser) {
                        await saveUser(result.user);
                    }

                    Alert.alert('Success', 'Account verified and created successfully!', [
                        {
                            text: 'OK', onPress: () => {
                                // Navigate to Main screen explicitly
                                navigation.replace('Main');
                            }
                        }
                    ]);
                } else {
                    Alert.alert('Error', result.error || 'Verification failed');
                }
            } else if (purpose === 'password_reset') {
                const response = await fetch(`${API_URL}/otp.php?action=verify`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, otp, purpose: 'password_reset' })
                });
                const result = await response.json();

                if (result.success) {
                    navigation.navigate('ResetPassword', { email, otp });
                } else {
                    Alert.alert('Error', result.error || 'Invalid OTP');
                }
            }
        } catch (error) {
            // silent
            Alert.alert('Error', 'An error occurred. Please try again.');
        } finally {
            setLoading(false);
        }
    };

    const handleResend = async () => {
        setLoading(true);
        try {
            const result = await resendOTP(email, purpose);
            if (result.success) {
                Alert.alert('Success', 'OTP resent successfully');
                setTimer(600);
                setCanResend(false);
            } else {
                Alert.alert('Error', result.error || 'Failed to resend OTP');
            }
        } catch (error) {
            Alert.alert('Error', 'Failed to resend OTP');
        } finally {
            setLoading(false);
        }
    };

    return (
        <LinearGradient colors={['#2563EB', '#1E40AF']} style={styles.container}>
            <SafeAreaView style={styles.safeArea}>
                <View style={styles.content}>
                    <Text style={styles.title}>Verification</Text>
                    <Text style={styles.subtitle}>
                        Enter the 6-digit code sent to {email}
                    </Text>

                    <View style={styles.otpContainer}>
                        <TextInput
                            style={styles.otpInput}
                            value={otp}
                            onChangeText={setOtp}
                            keyboardType="number-pad"
                            maxLength={6}
                            placeholder="000000"
                            placeholderTextColor="#9CA3AF"
                        />
                    </View>

                    <TouchableOpacity
                        style={[styles.verifyButton, loading && styles.disabledButton]}
                        onPress={handleVerify}
                        disabled={loading}
                    >
                        {loading ? (
                            <ActivityIndicator color="white" />
                        ) : (
                            <Text style={styles.verifyButtonText}>Verify</Text>
                        )}
                    </TouchableOpacity>

                    <View style={styles.resendContainer}>
                        <Text style={styles.timerText}>
                            Expires in: {formatTime(timer)}
                        </Text>
                        {canResend && (
                            <TouchableOpacity onPress={handleResend}>
                                <Text style={styles.resendLink}>Resend OTP</Text>
                            </TouchableOpacity>
                        )}
                    </View>
                </View>
            </SafeAreaView>
        </LinearGradient>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    safeArea: { flex: 1 },
    content: { padding: 20, flex: 1, justifyContent: 'center', alignItems: 'center' },
    title: { fontSize: 28, fontWeight: 'bold', color: 'white', marginBottom: 10 },
    subtitle: { fontSize: 16, color: '#E0E7FF', textAlign: 'center', marginBottom: 30 },
    otpContainer: {
        width: '100%',
        marginBottom: 20,
        alignItems: 'center'
    },
    otpInput: {
        backgroundColor: 'white',
        width: '80%',
        height: 60,
        borderRadius: 10,
        fontSize: 32,
        textAlign: 'center',
        color: '#1E40AF',
        letterSpacing: 10,
        fontWeight: 'bold',
    },
    verifyButton: {
        backgroundColor: '#10B981',
        width: '100%',
        padding: 15,
        borderRadius: 10,
        alignItems: 'center',
        marginBottom: 20,
    },
    disabledButton: { opacity: 0.7 },
    verifyButtonText: { color: 'white', fontSize: 18, fontWeight: 'bold' },
    resendContainer: { alignItems: 'center' },
    timerText: { color: '#E0E7FF', marginBottom: 10 },
    resendLink: { color: 'white', fontWeight: 'bold', fontSize: 16, textDecorationLine: 'underline' },
});

export default OTPVerificationScreen;
