import React, { useState, useContext } from 'react';
import { View, Text, StyleSheet, TextInput, TouchableOpacity, ScrollView, Alert } from 'react-native';
import { AppContext } from '../../context/AppContext';
import useTranslation from '../../i18n/useTranslation';

const AddCustomerScreen = ({ navigation }) => {
  const { addCustomer } = useContext(AppContext);
  const { t } = useTranslation();
  const [name, setName] = useState('');
  const [mobile, setMobile] = useState('');
  const [email, setEmail] = useState('');
  const [address, setAddress] = useState('');

  const handleSave = async () => {
    if (!name) {
      Alert.alert('Error', 'Please enter customer name');
      return;
    }

    if (mobile && mobile.length > 0 && mobile.length !== 10) {
      Alert.alert('Error', 'Mobile number should be 10 digits');
      return;
    }

    const customer = {
      name,
      mobile,
      email,
      address,
      purchases: [],
      createdAt: new Date().toISOString(),
    };

    const result = await addCustomer(customer);
    if (result && result.success) {
      Alert.alert('Success', 'Customer added successfully', [
        { text: 'OK', onPress: () => navigation.goBack() }
      ]);
    }
  };

  return (
    <ScrollView style={styles.container}>
      <View style={styles.content}>
        <View style={styles.inputGroup}>
          <Text style={styles.label}>{t('name')} *</Text>
          <TextInput
            style={styles.input}
            placeholder={t('name')}
            value={name}
            onChangeText={setName}
          />
        </View>

        <View style={styles.inputGroup}>
          <Text style={styles.label}>{t('mobile')}</Text>
          <TextInput
            style={styles.input}
            placeholder={t('mobile')}
            value={mobile}
            onChangeText={setMobile}
            keyboardType="phone-pad"
            maxLength={10}
          />
        </View>

        <View style={styles.inputGroup}>
          <Text style={styles.label}>{t('email')}</Text>
          <TextInput
            style={styles.input}
            placeholder={t('email')}
            value={email}
            onChangeText={setEmail}
            keyboardType="email-address"
            autoCapitalize="none"
          />
        </View>

        <View style={styles.inputGroup}>
          <Text style={styles.label}>{t('address')}</Text>
          <TextInput
            style={[styles.input, styles.textArea]}
            placeholder={t('address')}
            value={address}
            onChangeText={setAddress}
            multiline
            numberOfLines={4}
          />
        </View>

        <TouchableOpacity style={styles.saveButton} onPress={handleSave}>
          <Text style={styles.saveButtonText}>{t('save')}</Text>
        </TouchableOpacity>
      </View>
    </ScrollView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F8FAFC',
  },
  content: {
    padding: 20,
  },
  inputGroup: {
    marginBottom: 20,
  },
  label: {
    fontSize: 14,
    fontWeight: '600',
    color: '#1E293B',
    marginBottom: 8,
  },
  input: {
    backgroundColor: '#FFFFFF',
    padding: 15,
    borderRadius: 10,
    fontSize: 16,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  textArea: {
    height: 100,
    textAlignVertical: 'top',
  },
  saveButton: {
    backgroundColor: '#2563EB',
    padding: 18,
    borderRadius: 12,
    alignItems: 'center',
    marginTop: 20,
  },
  saveButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: 'bold',
  },
});

export default AddCustomerScreen;
