import React from 'react';
import { View, Text, ActivityIndicator, StyleSheet } from 'react-native';
import { useSimpleLoading } from '../contexts/SimpleLoadingContext';

const SimpleLoadingOverlay = () => {
  const { isLoading, message } = useSimpleLoading();

  if (!isLoading) return null;

  return (
    <View style={styles.overlay}>
      <View style={styles.container}>
        <ActivityIndicator size="large" color="#2563EB" />
        <Text style={styles.message}>{message}</Text>
      </View>
    </View>
  );
};

const styles = StyleSheet.create({
  overlay: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'center',
    alignItems: 'center',
    zIndex: 1000,
  },
  container: {
    backgroundColor: '#FFFFFF',
    padding: 30,
    borderRadius: 16,
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.25,
    shadowRadius: 4,
    elevation: 5,
  },
  message: {
    marginTop: 15,
    fontSize: 16,
    color: '#1E293B',
    fontWeight: '600',
  },
});

export default SimpleLoadingOverlay;
