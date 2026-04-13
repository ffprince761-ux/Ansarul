import React, { createContext, useState, useEffect } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';

export const SimpleAppContext = createContext();

export const SimpleAppProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    initializeApp();
  }, []);

  const initializeApp = async () => {
    try {
      // Load user data from storage
      const userData = await AsyncStorage.getItem('user');
      if (userData) {
        setUser(JSON.parse(userData));
      }
    } catch (error) {
      // silent
    } finally {
      setIsLoading(false);
    }
  };

  const login = async (userData) => {
    try {
      await AsyncStorage.setItem('user', JSON.stringify(userData));
      setUser(userData);
    } catch (error) {
      // silent
    }
  };

  const logout = async () => {
    try {
      await AsyncStorage.removeItem('user');
      setUser(null);
    } catch (error) {
      // silent
    }
  };

  return (
    <SimpleAppContext.Provider value={{
      user,
      login,
      logout,
      isLoading,
      // Mock data for dashboard
      sales: [],
      expenses: [],
      products: [],
      bills: [],
      customers: []
    }}>
      {children}
    </SimpleAppContext.Provider>
  );
};
