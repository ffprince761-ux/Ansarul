import React from 'react';
import { View, Text, ActivityIndicator, StyleSheet } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';

export default function LoadingScreen({ message = 'Loading data...' }) {
  return (
    <LinearGradient
      colors={['#2563EB', '#1E40AF']}
      style={styles.container}
    >
      <View style={styles.content}>
        <ActivityIndicator size="large" color="#FFFFFF" />
        <Text style={styles.text}>{message}</Text>
      </View>
    </LinearGradient>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  content: {
    alignItems: 'center',
  },
  text: {
    color: '#FFFFFF',
    fontSize: 18,
    marginTop: 20,
  }
});
