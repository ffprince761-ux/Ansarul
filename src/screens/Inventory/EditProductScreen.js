import React, { useState, useContext } from 'react';
import { View, Text, StyleSheet, TextInput, TouchableOpacity, ScrollView, Alert } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { AppContext } from '../../context/AppContext';
import useTranslation from '../../i18n/useTranslation';

const EditProductScreen = ({ route, navigation }) => {
  const { product } = route.params;
  const { updateProduct } = useContext(AppContext);
  const { t } = useTranslation();
  
  const [name, setName] = useState(product.name);
  const [category, setCategory] = useState(product.category);
  const [price, setPrice] = useState(product.price.toString());
  const [stock, setStock] = useState(product.stock.toString());
  const [unit, setUnit] = useState(product.unit || 'Nos');
  const [description, setDescription] = useState(product.description || '');
  const [lowStockThreshold, setLowStockThreshold] = useState((product.lowStockThreshold || 10).toString());

  const defaultCategories = ['Shoes', 'Mobile Accessories', 'Groceries', 'Hardware', 'Other'];
  const unitTypes = ['Nos', 'Kg', 'Ltr', 'Pcs', 'Box', 'Mtr', 'Gm', 'Ml'];

  const handleSave = async () => {
    if (!name || !category || !price || !stock) {
      Alert.alert('Error', 'Please fill in all required fields');
      return;
    }

    const updatedProduct = {
      name,
      category,
      price: parseFloat(price),
      stock: parseInt(stock),
      unit,
      description,
      lowStockThreshold: parseInt(lowStockThreshold) || 10,
    };

    await updateProduct(product.id, updatedProduct);
    Alert.alert('Success', 'Product updated successfully', [
      { text: 'OK', onPress: () => navigation.goBack() }
    ]);
  };

  return (
    <ScrollView style={styles.container}>
      <View style={styles.content}>
        <View style={styles.inputGroup}>
          <Text style={styles.label}>{t('productName')} *</Text>
          <TextInput
            style={styles.input}
            placeholder={t('productName')}
            value={name}
            onChangeText={setName}
          />
        </View>

        <View style={styles.inputGroup}>
          <Text style={styles.label}>{t('category')} *</Text>
          <View style={styles.categoryGrid}>
            {defaultCategories.map((cat) => (
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
          <Text style={styles.label}>Unit Type *</Text>
          <ScrollView horizontal showsHorizontalScrollIndicator={false}>
            <View style={styles.unitGrid}>
              {unitTypes.map((unitType) => (
                <TouchableOpacity
                  key={unitType}
                  style={[
                    styles.unitChip,
                    unit === unitType && styles.unitChipSelected
                  ]}
                  onPress={() => setUnit(unitType)}
                >
                  <Text style={[
                    styles.unitChipText,
                    unit === unitType && styles.unitChipTextSelected
                  ]}>
                    {unitType}
                  </Text>
                </TouchableOpacity>
              ))}
            </View>
          </ScrollView>
        </View>

        <View style={styles.row}>
          <View style={[styles.inputGroup, { flex: 1, marginRight: 8 }]}>
            <Text style={styles.label}>{t('price')} (₹) *</Text>
            <TextInput
              style={styles.input}
              placeholder="0"
              value={price}
              onChangeText={setPrice}
              keyboardType="numeric"
            />
          </View>

          <View style={[styles.inputGroup, { flex: 1, marginLeft: 8 }]}>
            <Text style={styles.label}>{t('stock')} *</Text>
            <TextInput
              style={styles.input}
              placeholder="0"
              value={stock}
              onChangeText={setStock}
              keyboardType="numeric"
            />
          </View>
        </View>

        <View style={styles.inputGroup}>
          <Text style={styles.label}>{t('lowStockThreshold')}</Text>
          <TextInput
            style={styles.input}
            placeholder="10 (Default)"
            value={lowStockThreshold}
            onChangeText={setLowStockThreshold}
            keyboardType="numeric"
          />
          <Text style={styles.helperText}>
            Alert when stock falls below this quantity (Default: 10)
          </Text>
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
    borderWidth: 1,
    borderColor: '#E2E8F0',
    borderRadius: 12,
    padding: 16,
    fontSize: 16,
    color: '#1E293B',
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
    backgroundColor: '#2563EB',
  },
  categoryChipText: {
    fontSize: 14,
    color: '#64748B',
    fontWeight: '500',
  },
  categoryChipTextSelected: {
    color: '#FFFFFF',
  },
  unitGrid: {
    flexDirection: 'row',
    marginTop: 8,
    gap: 8,
  },
  unitChip: {
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderRadius: 20,
    backgroundColor: '#F1F5F9',
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  unitChipSelected: {
    backgroundColor: '#2563EB',
    borderColor: '#2563EB',
  },
  unitChipText: {
    fontSize: 14,
    color: '#64748B',
    fontWeight: '500',
  },
  unitChipTextSelected: {
    color: '#FFFFFF',
  },
  row: {
    flexDirection: 'row',
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
  helperText: {
    fontSize: 12,
    color: '#64748B',
    marginTop: 4,
  },
});

export default EditProductScreen;
