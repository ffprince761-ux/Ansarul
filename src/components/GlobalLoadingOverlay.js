import React, { useEffect, useRef } from 'react';
import { View, Animated, Easing, StyleSheet } from 'react-native';
import { useGlobalLoading } from '../contexts/GlobalLoadingContext';

const GlobalLoadingOverlay = () => {
  const { isLoading } = useGlobalLoading();
  const fadeAnim = useRef(new Animated.Value(0)).current;
  const spinAnim = useRef(new Animated.Value(0)).current;
  const scaleAnim = useRef(new Animated.Value(0.8)).current;
  const spinLoop = useRef(null);

  useEffect(() => {
    if (isLoading) {
      Animated.parallel([
        Animated.timing(fadeAnim, {
          toValue: 1,
          duration: 200,
          useNativeDriver: true,
        }),
        Animated.spring(scaleAnim, {
          toValue: 1,
          friction: 5,
          useNativeDriver: true,
        }),
      ]).start();

      spinLoop.current = Animated.loop(
        Animated.timing(spinAnim, {
          toValue: 1,
          duration: 900,
          easing: Easing.linear,
          useNativeDriver: true,
        })
      );
      spinLoop.current.start();
    } else {
      if (spinLoop.current) spinLoop.current.stop();
      Animated.timing(fadeAnim, {
        toValue: 0,
        duration: 180,
        useNativeDriver: true,
      }).start(() => {
        scaleAnim.setValue(0.8);
        spinAnim.setValue(0);
      });
    }
  }, [isLoading]);

  const rotate = spinAnim.interpolate({
    inputRange: [0, 1],
    outputRange: ['0deg', '360deg'],
  });

  return (
    <Animated.View style={[styles.overlay, { opacity: fadeAnim }]} pointerEvents={isLoading ? 'auto' : 'none'}>
      <Animated.View style={[styles.container, { transform: [{ scale: scaleAnim }] }]}>
        <View style={styles.spinnerWrapper}>
          <View style={styles.spinnerTrack} />
          <Animated.View style={[styles.spinnerArc, { transform: [{ rotate }] }]} />
        </View>
      </Animated.View>
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
    backgroundColor: 'rgba(0, 0, 0, 0.35)',
    justifyContent: 'center',
    alignItems: 'center',
    zIndex: 9999,
  },
  container: {
    backgroundColor: '#FFFFFF',
    width: 80,
    height: 80,
    borderRadius: 20,
    justifyContent: 'center',
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.15,
    shadowRadius: 10,
    elevation: 8,
  },
  spinnerWrapper: {
    width: 44,
    height: 44,
    justifyContent: 'center',
    alignItems: 'center',
  },
  spinnerTrack: {
    position: 'absolute',
    width: 44,
    height: 44,
    borderRadius: 22,
    borderWidth: 4,
    borderColor: '#E2E8F0',
  },
  spinnerArc: {
    position: 'absolute',
    width: 44,
    height: 44,
    borderRadius: 22,
    borderWidth: 4,
    borderColor: 'transparent',
    borderTopColor: '#2563EB',
    borderRightColor: '#2563EB',
  },
});

export default GlobalLoadingOverlay;
