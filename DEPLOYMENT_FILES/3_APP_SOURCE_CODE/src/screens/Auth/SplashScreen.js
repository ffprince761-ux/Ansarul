import React, { useState } from 'react';
import { View, Text, StyleSheet, Image } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import LoadingScreen from '../../components/LoadingScreen';

const SplashScreen = () => {
  const [isLoading, setIsLoading] = useState(true);

  return isLoading ? (
    <LoadingScreen message="Loading app data..." />
  ) : (
    <LinearGradient
      colors={['#2563EB', '#1E40AF']}
      style={styles.container}
    >
      <View style={styles.content}>
        <Image 
          source={require('../../../assets/icon.png')} 
          style={styles.logo}
          resizeMode="contain"
        />
        <Text style={styles.title}>BINEST</Text>
        <Text style={styles.subtitle}>Small Business Management</Text>
      </View>
    </LinearGradient>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  content: {
    alignItems: 'center',
  },
  logo: {
    width: 150,
    height: 150,
    marginBottom: 30,
  },
  title: {
    fontSize: 36,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 10,
  },
  subtitle: {
    fontSize: 16,
    color: '#E0E7FF',
  },
});

export default SplashScreen;
