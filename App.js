import React, { useEffect, useState } from 'react';
import { View, ActivityIndicator, StyleSheet, Text } from 'react-native';
import { NavigationContainer } from '@react-navigation/native';
import { createStackNavigator } from '@react-navigation/stack';
import { Provider as PaperProvider } from 'react-native-paper';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { GlobalLoadingProvider } from './src/contexts/GlobalLoadingContext';
import GlobalLoadingOverlay from './src/components/GlobalLoadingOverlay';
import { AppProvider } from './src/context/AppContext';
import MainNavigator from './src/navigation/MainNavigator';
import SplashScreen from './src/screens/Auth/SplashScreen';
import LanguageSelectionScreen from './src/screens/Auth/LanguageSelectionScreen';
import LoginScreen from './src/screens/Auth/LoginScreen';
import RegisterScreen from './src/screens/Auth/RegisterScreen';
import ForgotPasswordScreen from './src/screens/Auth/ForgotPasswordScreen';
import OTPVerificationScreen from './src/screens/Auth/OTPVerificationScreen';
import ResetPasswordScreen from './src/screens/Auth/ResetPasswordScreen';
import TermsConditionsScreen from './src/screens/Auth/TermsConditionsScreen';
import PrivacyPolicyScreen from './src/screens/Auth/PrivacyPolicyScreen';

const Stack = createStackNavigator();

export default function App() {
  const [isLoading, setIsLoading] = useState(true);
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [hasSelectedLanguage, setHasSelectedLanguage] = useState(false);
  useEffect(() => {
    checkAuthStatus();
  }, []);

  const checkAuthStatus = async () => {
    try {
      const [userToken, language] = await Promise.all([
        AsyncStorage.getItem('userToken'),
        AsyncStorage.getItem('selectedLanguage')
      ]);

      setIsAuthenticated(!!userToken);
      setHasSelectedLanguage(!!language);

      setTimeout(() => {
        setIsLoading(false);
      }, 2000);
    } catch (error) {
      console.error('Error checking auth status:', error);
      setIsLoading(false);
    }
  };

  if (isLoading) {
    return <SplashScreen />;
  }

  const initialRouteName = !hasSelectedLanguage
    ? 'LanguageSelection'
    : !isAuthenticated
      ? 'Login'
      : 'Main';

  return (
    <GlobalLoadingProvider>
      <AppProvider>
        <PaperProvider>
          <NavigationContainer>
            <Stack.Navigator
              screenOptions={{ headerShown: false }}
              initialRouteName={initialRouteName}
            >
              <Stack.Screen name="LanguageSelection" component={LanguageSelectionScreen} />
              <Stack.Screen name="Login" component={LoginScreen} />
              <Stack.Screen name="Register" component={RegisterScreen} />
              <Stack.Screen name="ForgotPassword" component={ForgotPasswordScreen} />
              <Stack.Screen name="OTPVerification" component={OTPVerificationScreen} />
              <Stack.Screen name="ResetPassword" component={ResetPasswordScreen} />
              <Stack.Screen name="TermsConditions" component={TermsConditionsScreen} />
              <Stack.Screen name="PrivacyPolicy" component={PrivacyPolicyScreen} />
              <Stack.Screen name="Main" component={MainNavigator} />
            </Stack.Navigator>
          </NavigationContainer>
          <GlobalLoadingOverlay />
        </PaperProvider>
      </AppProvider>
    </GlobalLoadingProvider>
  );
}
