// App.js - SETUP INSTRUCTIONS

import React, { useEffect, useState } from 'react';
import { NavigationContainer } from '@react-navigation/native';
import { createStackNavigator } from '@react-navigation/stack';
import { LoadingProvider } from './LoadingProvider';
import { useApi, useAppLoader } from './useApi';

// Your screens
import HomeScreen from './screens/HomeScreen';
import LoginScreen from './screens/LoginScreen';
import DashboardScreen from './screens/DashboardScreen';

const Stack = createStackNavigator();

// ============================================
// STEP 1: Wrap your entire app with LoadingProvider
// ============================================
export default function App() {
  return (
    <LoadingProvider>
      <NavigationContainer>
        <AppNavigator />
      </NavigationContainer>
    </LoadingProvider>
  );
}

// ============================================
// STEP 2: Use loading in your screens
// ============================================
function AppNavigator() {
  const { loadInitialData } = useAppLoader();
  const [isReady, setIsReady] = useState(false);

  useEffect(() => {
    // App start hote waqt data load karo
    const initialize = async () => {
      const result = await loadInitialData();
      
      if (result.success) {
        console.log('App loaded successfully');
        setIsReady(true);
      } else {
        console.error('Failed to load:', result.error);
        // Handle error - maybe show error screen
      }
    };

    initialize();
  }, [loadInitialData]);

  return (
    <Stack.Navigator>
      <Stack.Screen name="Home" component={HomeScreen} />
      <Stack.Screen name="Login" component={LoginScreen} />
      <Stack.Screen name="Dashboard" component={DashboardScreen} />
    </Stack.Navigator>
  );
}

// ============================================
// STEP 3: Example Screen Usage
// ============================================
function ExampleScreen() {
  const { get, post } = useApi();

  const handleLogin = async () => {
    // Automatic loading dikhega
    const result = await post('auth/login', { 
      username: 'test', 
      password: '123456' 
    });

    if (result.success) {
      console.log('Login success:', result.data);
    } else {
      alert(result.error);
    }
    // Loading apne aap hat jayega
  };

  const handleFetchProducts = async () => {
    // Loader ke bina data fetch (agar zaroori ho)
    const result = await get('products', { showLoader: false });
    
    if (result.success) {
      setProducts(result.data);
    }
  };

  const handleSaveWithCustomText = async () => {
    // Custom loading text ke saath
    const result = await post('save', data, {
      loaderText: 'Saving data...'
    });
    
    if (result.success) {
      alert('Saved!');
    }
  };

  return (
    <View style={{ flex: 1, padding: 20 }}>
      <Button title="Login (With Loading)" onPress={handleLogin} />
      <Button title="Save (Custom Text)" onPress={handleSaveWithCustomText} />
      <Button title="Silent Fetch (No Loading)" onPress={handleFetchProducts} />
    </View>
  );
}
