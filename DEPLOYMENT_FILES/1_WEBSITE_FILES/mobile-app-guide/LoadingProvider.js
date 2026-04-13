import React, { createContext, useContext, useState, useCallback } from 'react';
import { View, ActivityIndicator, StyleSheet, Text } from 'react-native';

const LoadingContext = createContext();

export const LoadingProvider = ({ children }) => {
  const [loading, setLoading] = useState(false);
  const [loadingText, setLoadingText] = useState('Loading...');
  const [loadingCount, setLoadingCount] = useState(0);

  const showLoading = useCallback((text = 'Loading...') => {
    setLoadingText(text);
    setLoadingCount(prev => {
      const newCount = prev + 1;
      setLoading(true);
      return newCount;
    });
  }, []);

  const hideLoading = useCallback(() => {
    setLoadingCount(prev => {
      const newCount = Math.max(0, prev - 1);
      if (newCount === 0) {
        setLoading(false);
      }
      return newCount;
    });
  }, []);

  return (
    <LoadingContext.Provider value={{ showLoading, hideLoading }}>
      {children}
      {loading && (
        <View style={styles.overlay}>
          <View style={styles.container}>
            <ActivityIndicator size="large" color="#4F46E5" />
            <Text style={styles.text}>{loadingText}</Text>
          </View>
        </View>
      )}
    </LoadingContext.Provider>
  );
};

export const useLoading = () => {
  const context = useContext(LoadingContext);
  if (!context) {
    throw new Error('useLoading must be used within LoadingProvider');
  }
  return context;
};

const styles = StyleSheet.create({
  overlay: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    backgroundColor: 'rgba(255, 255, 255, 0.9)',
    justifyContent: 'center',
    alignItems: 'center',
    zIndex: 9999,
  },
  container: {
    backgroundColor: 'white',
    padding: 30,
    borderRadius: 16,
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.1,
    shadowRadius: 20,
    elevation: 10,
  },
  text: {
    marginTop: 16,
    fontSize: 14,
    color: '#374151',
    fontWeight: '600',
  },
});
