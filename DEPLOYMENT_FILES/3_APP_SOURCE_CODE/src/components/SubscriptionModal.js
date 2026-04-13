import React from 'react';
import { View, Text, TouchableOpacity, Modal } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';

const SubscriptionModal = ({ visible, onClose }) => {
  if (!visible) return null;

  return (
    <Modal visible={visible} transparent animationType="fade">
      <View style={{ flex: 1, backgroundColor: 'rgba(0,0,0,0.6)', justifyContent: 'center', alignItems: 'center', padding: 24 }}>
        <View style={{ backgroundColor: '#FFF', borderRadius: 20, width: '100%', maxWidth: 340, overflow: 'hidden' }}>
          <LinearGradient colors={['#F59E0B', '#EF4444']} style={{ paddingVertical: 28, alignItems: 'center' }}>
            <View style={{ width: 64, height: 64, borderRadius: 32, backgroundColor: 'rgba(255,255,255,0.2)', justifyContent: 'center', alignItems: 'center', marginBottom: 12 }}>
              <Ionicons name="lock-closed" size={32} color="#FFF" />
            </View>
            <Text style={{ fontSize: 20, fontWeight: '800', color: '#FFF' }}>Limit Over!</Text>
          </LinearGradient>
          <View style={{ padding: 24, alignItems: 'center' }}>
            <Text style={{ fontSize: 14, color: '#64748B', textAlign: 'center', lineHeight: 22, marginBottom: 6 }}>
              Aapka free plan ka limit khatam ho gaya hai.
            </Text>
            <Text style={{ fontSize: 14, color: '#1E293B', textAlign: 'center', lineHeight: 22, fontWeight: '600', marginBottom: 20 }}>
              Naya data create karne ke liye subscription lein!
            </Text>
            <View style={{ flexDirection: 'row', alignItems: 'center', backgroundColor: '#FEF3C7', paddingHorizontal: 16, paddingVertical: 10, borderRadius: 10, marginBottom: 20, width: '100%' }}>
              <Ionicons name="star" size={18} color="#F59E0B" />
              <Text style={{ fontSize: 12, color: '#92400E', marginLeft: 8, flex: 1 }}>Paid plan mein unlimited access milega!</Text>
            </View>
            <TouchableOpacity
              onPress={onClose}
              style={{ width: '100%', marginBottom: 10 }}
            >
              <LinearGradient colors={['#2563EB', '#1E40AF']} style={{ width: '100%', paddingVertical: 14, borderRadius: 12, alignItems: 'center' }}>
                <View style={{ flexDirection: 'row', alignItems: 'center' }}>
                  <Ionicons name="diamond" size={18} color="#FFF" />
                  <Text style={{ color: '#FFF', fontSize: 15, fontWeight: '700', marginLeft: 8 }}>Upgrade Now</Text>
                </View>
              </LinearGradient>
            </TouchableOpacity>
            <TouchableOpacity onPress={onClose} style={{ paddingVertical: 8 }}>
              <Text style={{ fontSize: 13, color: '#94A3B8' }}>Baad mein</Text>
            </TouchableOpacity>
          </View>
        </View>
      </View>
    </Modal>
  );
};

export default SubscriptionModal;
