import React, { useEffect, useRef } from 'react';
import { View, Animated, Easing, StyleSheet } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';

export default function LoadingScreen() {
  const spinAnim = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    Animated.loop(
      Animated.timing(spinAnim, {
        toValue: 1,
        duration: 900,
        easing: Easing.linear,
        useNativeDriver: true,
      })
    ).start();
  }, []);

  const rotate = spinAnim.interpolate({
    inputRange: [0, 1],
    outputRange: ['0deg', '360deg'],
  });

  return (
    <LinearGradient
      colors={['#2563EB', '#1E40AF']}
      style={styles.container}
    >
      <View style={styles.spinnerWrapper}>
        <View style={styles.spinnerTrack} />
        <Animated.View style={[styles.spinnerArc, { transform: [{ rotate }] }]} />
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
  spinnerWrapper: {
    width: 56,
    height: 56,
    justifyContent: 'center',
    alignItems: 'center',
  },
  spinnerTrack: {
    position: 'absolute',
    width: 56,
    height: 56,
    borderRadius: 28,
    borderWidth: 5,
    borderColor: 'rgba(255,255,255,0.3)',
  },
  spinnerArc: {
    position: 'absolute',
    width: 56,
    height: 56,
    borderRadius: 28,
    borderWidth: 5,
    borderColor: 'transparent',
    borderTopColor: '#FFFFFF',
    borderRightColor: '#FFFFFF',
  },
});
