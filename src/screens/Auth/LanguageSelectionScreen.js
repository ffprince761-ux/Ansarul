import React, { useState } from 'react';
import { View, Text, StyleSheet, TouchableOpacity } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { LinearGradient } from 'expo-linear-gradient';

const LanguageSelectionScreen = ({ navigation }) => {
  const [selectedLanguage, setSelectedLanguage] = useState('en');

  const languages = [
    { code: 'en', name: 'English', flag: '🇬🇧' },
    { code: 'hi', name: 'हिंदी', flag: '🇮🇳' },
    { code: 'mr', name: 'मराठी', flag: '🇮🇳' },
    { code: 'gu', name: 'ગુજરાતી', flag: '🇮🇳' },
  ];

  const handleContinue = async () => {
    try {
      await AsyncStorage.setItem('selectedLanguage', selectedLanguage);

      if (navigation && navigation.replace) {
        navigation.replace('Login');
      } else {
        // silent
      }
    } catch (error) {
      // silent
      alert('Error: ' + error.message);
    }
  };

  return (
    <LinearGradient
      colors={['#2563EB', '#1E40AF']}
      style={styles.container}
    >
      <SafeAreaView style={styles.safeArea}>
        <View style={styles.content}>
          <Text style={styles.title}>Select Language</Text>
          <Text style={styles.subtitle}>Choose your preferred language</Text>

          <View style={styles.languageContainer}>
            {languages.map((lang) => (
              <TouchableOpacity
                key={lang.code}
                style={[
                  styles.languageCard,
                  selectedLanguage === lang.code && styles.selectedCard,
                ]}
                onPress={() => setSelectedLanguage(lang.code)}
              >
                <Text style={styles.flag}>{lang.flag}</Text>
                <Text style={styles.languageName}>{lang.name}</Text>
                {selectedLanguage === lang.code && (
                  <View style={styles.checkmark}>
                    <Text style={styles.checkmarkText}>✓</Text>
                  </View>
                )}
              </TouchableOpacity>
            ))}
          </View>

          <TouchableOpacity style={styles.continueButton} onPress={handleContinue}>
            <Text style={styles.continueButtonText}>Continue</Text>
          </TouchableOpacity>
        </View>
      </SafeAreaView>
    </LinearGradient>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  safeArea: {
    flex: 1,
  },
  content: {
    flex: 1,
    padding: 20,
    justifyContent: 'center',
  },
  title: {
    fontSize: 32,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 10,
    textAlign: 'center',
  },
  subtitle: {
    fontSize: 16,
    color: '#E0E7FF',
    marginBottom: 40,
    textAlign: 'center',
  },
  languageContainer: {
    marginBottom: 40,
  },
  languageCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(255, 255, 255, 0.1)',
    padding: 20,
    borderRadius: 12,
    marginBottom: 15,
    borderWidth: 2,
    borderColor: 'transparent',
  },
  selectedCard: {
    backgroundColor: 'rgba(255, 255, 255, 0.2)',
    borderColor: '#FFFFFF',
  },
  flag: {
    fontSize: 32,
    marginRight: 15,
  },
  languageName: {
    fontSize: 18,
    color: '#FFFFFF',
    fontWeight: '600',
    flex: 1,
  },
  checkmark: {
    width: 24,
    height: 24,
    borderRadius: 12,
    backgroundColor: '#10B981',
    justifyContent: 'center',
    alignItems: 'center',
  },
  checkmarkText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: 'bold',
  },
  continueButton: {
    backgroundColor: '#FFFFFF',
    padding: 18,
    borderRadius: 12,
    alignItems: 'center',
  },
  continueButtonText: {
    color: '#2563EB',
    fontSize: 18,
    fontWeight: 'bold',
  },
});

export default LanguageSelectionScreen;
