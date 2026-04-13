import React, { useState, useContext } from 'react';
import { View, Text, StyleSheet, TextInput, TouchableOpacity, ScrollView, Alert } from 'react-native';
import { AppContext } from '../../context/AppContext';
import useTranslation from '../../i18n/useTranslation';

const AddExpenseScreen = ({ navigation }) => {
  const { addExpense } = useContext(AppContext);
  const { t } = useTranslation();
  const [category, setCategory] = useState('');
  const [amount, setAmount] = useState('');
  const [description, setDescription] = useState('');

  const categories = [
    'Rent', 'Utilities', 'Salaries', 'Inventory', 'Marketing', 
    'Transportation', 'Maintenance', 'Other'
  ];

  const handleSave = async () => {
    if (!category || !amount) {
      Alert.alert('Error', 'Please fill in category and amount');
      return;
    }

    const expense = {
      category,
      amount: parseFloat(amount),
      description,
      date: new Date().toISOString().split('T')[0], // Add today's date in YYYY-MM-DD format
    };

    await addExpense(expense);
    Alert.alert('Success', 'Expense added successfully', [
      { text: 'OK', onPress: () => navigation.goBack() }
    ]);
  };

  return (
    <ScrollView style={styles.container}>
      <View style={styles.content}>
        <View style={styles.inputGroup}>
          <Text style={styles.label}>{t('category')} *</Text>
          <View style={styles.categoryGrid}>
            {categories.map((cat) => (
              <TouchableOpacity
                key={cat}
                style={[
                  styles.categoryChip,
                  category === cat && styles.categoryChipSelected
                ]}
                onPress={() => setCategory(cat)}
              >
                <Text style={[
                  styles.categoryChipText,
                  category === cat && styles.categoryChipTextSelected
                ]}>
                  {cat}
                </Text>
              </TouchableOpacity>
            ))}
          </View>
        </View>

        <View style={styles.inputGroup}>
          <Text style={styles.label}>{t('amount')} (₹) *</Text>
          <TextInput
            style={styles.input}
            placeholder={t('amount')}
            value={amount}
            onChangeText={setAmount}
            keyboardType="numeric"
          />
        </View>

        <View style={styles.inputGroup}>
          <Text style={styles.label}>{t('description')}</Text>
          <TextInput
            style={[styles.input, styles.textArea]}
            placeholder={t('description')}
            value={description}
            onChangeText={setDescription}
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
  categoryGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    marginTop: 8,
  },
  categoryChip: {
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderRadius: 20,
    backgroundColor: '#F1F5F9',
    marginRight: 8,
    marginBottom: 8,
  },
  categoryChipSelected: {
    backgroundColor: '#F59E0B',
  },
  categoryChipText: {
    fontSize: 14,
    color: '#64748B',
  },
  categoryChipTextSelected: {
    color: '#FFFFFF',
    fontWeight: '600',
  },
  saveButton: {
    backgroundColor: '#F59E0B',
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

export default AddExpenseScreen;
