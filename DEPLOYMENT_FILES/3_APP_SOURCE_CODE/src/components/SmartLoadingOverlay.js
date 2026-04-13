import React from 'react';
import { View, Text, ActivityIndicator, StyleSheet, Animated } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { useSmartLoading } from '../contexts/SmartLoadingContext';

const SmartLoadingOverlay = () => {
  const { globalLoading, loadingStates } = useSmartLoading();
  const [fadeAnim] = React.useState(new Animated.Value(0));
  const [isVisible, setIsVisible] = React.useState(false);

  React.useEffect(() => {
    if (globalLoading) {
      setIsVisible(true);
      Animated.timing(fadeAnim, {
        toValue: 1,
        duration: 300,
        useNativeDriver: true,
      }).start();
    } else {
      Animated.timing(fadeAnim, {
        toValue: 0,
        duration: 300,
        useNativeDriver: true,
      }).start(() => {
        setIsVisible(false);
      });
    }
  }, [globalLoading]);

  if (!isVisible) return null;

  const getActiveLoadingMessage = () => {
    const activeLoadings = Object.entries(loadingStates);
    if (activeLoadings.length > 0) {
      return activeLoadings[0][1].message;
    }
    return 'Loading...';
  };

  return (
    <Animated.View style={[styles.overlay, { opacity: fadeAnim }]}>
      <LinearGradient
        colors={['#2563EB', '#1E40AF']}
        style={styles.container}
      >
        <View style={styles.content}>
          <ActivityIndicator size="large" color="#FFFFFF" />
          <Text style={styles.message}>{getActiveLoadingMessage()}</Text>
          
          {Object.keys(loadingStates).length > 1 && (
            <Text style={styles.subMessage}>
              {Object.keys(loadingStates).length} operations in progress...
            </Text>
          )}
        </View>
      </LinearGradient>
    </Animated.View>
  );
};

const styles = StyleSheet.create({
  overlay: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    zIndex: 1000,
  },
  container: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  content: {
    alignItems: 'center',
  },
  message: {
    color: '#FFFFFF',
    fontSize: 18,
    marginTop: 20,
    fontWeight: '600',
  },
  subMessage: {
    color: '#FFFFFF',
    fontSize: 14,
    marginTop: 8,
    opacity: 0.8,
  },
});

export default SmartLoadingOverlay;
