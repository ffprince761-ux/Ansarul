import React from 'react';
import { View, Text, ActivityIndicator, StyleSheet } from 'react-native';
import { useLoading } from '../contexts/LoadingContext';

const LoadingOverlay = () => {
  const { loading, message } = useLoading();

  if (!loading) return null;

  return (
    <View style={styles.overlay}>
      <View style={styles.container}>
        <ActivityIndicator size="large" color="#FFFFFF" />
        <Text style={styles.text}>{message}</Text>
      </View>
    </View>
  );
};

const styles = StyleSheet.create({
  overlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(0,0,0,0.7)',
    justifyContent: 'center',
    alignItems: 'center',
    zIndex: 1000,
  },
  container: {
    backgroundColor: '#2563EB',
    padding: 20,
    borderRadius: 10,
    alignItems: 'center',
  },
  text: {
    color: '#FFFFFF',
    marginTop: 10,
    fontSize: 16,
  }
});

export default LoadingOverlay;
