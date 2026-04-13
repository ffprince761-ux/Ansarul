import React, { useContext, useState } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, TextInput, Alert, FlatList } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { AppContext } from '../../context/AppContext';
import useTranslation from '../../i18n/useTranslation';

const InventoryScreen = ({ navigation }) => {
  const { products, categories, deleteProduct } = useContext(AppContext);
  const { t } = useTranslation();
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('all');

  const safeProducts = products || [];
  const safeCategories = categories || [];

  const filteredProducts = safeProducts.filter(product => {
    const matchesSearch = product.name.toLowerCase().includes(searchQuery.toLowerCase());
    const matchesCategory = selectedCategory === 'all' || product.category === selectedCategory;
    return matchesSearch && matchesCategory;
  });

  const lowStockCount = safeProducts.filter(p => p.stock < (p.lowStockThreshold || 10)).length;
  const totalProducts = safeProducts.length;

  const defaultCategories = [
    { id: '1', name: 'Shoes', icon: '👟', color: '#F59E0B' },
    { id: '2', name: 'Mobile Accessories', icon: '📱', color: '#2563EB' },
    { id: '3', name: 'Groceries', icon: '🛒', color: '#10B981' },
    { id: '4', name: 'Hardware', icon: '🔧', color: '#EF4444' },
    { id: '5', name: 'Other', icon: '📦', color: '#8B5CF6' },
  ];

  const allCategories = [...defaultCategories, ...safeCategories];

  const handleDeleteProduct = (product) => {
    Alert.alert(
      'Delete Product',
      `Are you sure you want to delete "${product.name}"?`,
      [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Delete',
          style: 'destructive',
          onPress: async () => {
            await deleteProduct(product.id);
            Alert.alert('Success', 'Product deleted successfully');
          }
        }
      ]
    );
  };

  return (
    <SafeAreaView style={styles.container}>
      <LinearGradient
        colors={['#2563EB', '#1E40AF']}
        style={styles.header}
      >
        <View style={styles.headerTop}>
          <View style={styles.headerLeft}>
            <Ionicons name="cube" size={24} color="#FFFFFF" />
            <Text style={styles.headerTitle}>{t('inventory')}</Text>
          </View>
          <View style={styles.headerRight}>
            <TouchableOpacity
              style={styles.headerButton}
              onPress={() => navigation.navigate('AddProduct')}
            >
              <Ionicons name="add" size={24} color="#FFFFFF" />
            </TouchableOpacity>
            <TouchableOpacity style={styles.headerButton}>
              <Ionicons name="home" size={24} color="#FFFFFF" />
            </TouchableOpacity>
          </View>
        </View>

        <View style={styles.statsRow}>
          <View style={styles.statBox}>
            <View style={styles.statIconContainer}>
              <Ionicons name="cube-outline" size={16} color="#2563EB" />
            </View>
            <View style={styles.statTextContainer}>
              <Text style={styles.statLabel} numberOfLines={1}>{t('totalProducts')}</Text>
              <Text style={styles.statValue}>{totalProducts}</Text>
            </View>
          </View>

          <View style={styles.statBox}>
            <View style={[styles.statIconContainer, { backgroundColor: '#FEF3C7' }]}>
              <Ionicons name="alert-circle-outline" size={16} color="#F59E0B" />
            </View>
            <View style={styles.statTextContainer}>
              <Text style={styles.statLabel} numberOfLines={1}>{t('lowStock')}</Text>
              <Text style={styles.statValue}>{lowStockCount}</Text>
            </View>
          </View>

          <View style={styles.statBox}>
            <View style={[styles.statIconContainer, { backgroundColor: '#D1FAE5' }]}>
              <Ionicons name="checkmark-circle-outline" size={16} color="#10B981" />
            </View>
            <View style={styles.statTextContainer}>
              <Text style={styles.statLabel} numberOfLines={1}>{t('categories')}</Text>
              <Text style={styles.statValue}>{allCategories.length}</Text>
            </View>
          </View>
        </View>
      </LinearGradient>

      <ScrollView style={styles.content}>
        <TouchableOpacity
          style={styles.addButton}
          onPress={() => navigation.navigate('AddProduct')}
        >
          <LinearGradient
            colors={['#2563EB', '#1E40AF']}
            style={styles.addButtonGradient}
          >
            <Ionicons name="add" size={20} color="#FFFFFF" />
            <Text style={styles.addButtonText}>{t('addProduct')}</Text>
            <Ionicons name="arrow-forward" size={16} color="#FFFFFF" />
          </LinearGradient>
          <TouchableOpacity style={styles.addIconButton}>
            <Ionicons name="add" size={24} color="#2563EB" />
          </TouchableOpacity>
        </TouchableOpacity>

        <View style={styles.searchBar}>
          <Ionicons name="search" size={20} color="#64748B" />
          <TextInput
            style={styles.searchInput}
            placeholder={t('searchProducts2')}
            value={searchQuery}
            onChangeText={setSearchQuery}
          />
          {searchQuery.length > 0 && (
            <TouchableOpacity onPress={() => setSearchQuery('')}>
              <Ionicons name="close-circle" size={20} color="#94A3B8" />
            </TouchableOpacity>
          )}
        </View>

        <View style={styles.section}>
          <View style={styles.sectionHeader}>
            <Text style={styles.sectionTitle}>{t('categories')}</Text>
            <TouchableOpacity>
              <Ionicons name="chevron-forward" size={20} color="#64748B" />
            </TouchableOpacity>
          </View>

          <ScrollView
            horizontal
            showsHorizontalScrollIndicator={false}
            style={styles.categoriesScroll}
          >
            <TouchableOpacity
              style={[
                styles.categoryCard,
                { backgroundColor: selectedCategory === 'all' ? '#2563EB' : '#94A3B8' }
              ]}
              onPress={() => setSelectedCategory('all')}
            >
              <Text style={styles.categoryIcon}>📦</Text>
              <Text style={styles.categoryName}>All</Text>
            </TouchableOpacity>
            {allCategories.map((category) => (
              <TouchableOpacity
                key={category.id}
                style={[
                  styles.categoryCard,
                  { backgroundColor: selectedCategory === category.name ? category.color : '#E2E8F0' }
                ]}
                onPress={() => setSelectedCategory(category.name)}
              >
                <Text style={styles.categoryIcon}>{category.icon}</Text>
                <Text style={[
                  styles.categoryName,
                  selectedCategory === category.name ? { color: '#FFFFFF' } : { color: '#64748B' }
                ]}>
                  {category.name}
                </Text>
              </TouchableOpacity>
            ))}
          </ScrollView>
        </View>

        {lowStockCount > 0 && (
          <View style={styles.section}>
            <View style={styles.sectionHeader}>
              <Text style={styles.sectionTitle}>{t('lowStockAlert')}</Text>
              <View style={styles.alertBadge}>
                <Text style={styles.alertBadgeText}>{lowStockCount}</Text>
              </View>
            </View>

            <ScrollView style={styles.productList}>
              {products.filter(p => p.stock < (p.lowStockThreshold || 10)).slice(0, 3).map((product) => (
                <View key={product.id} style={styles.productCard}>
                  <View style={styles.productIcon}>
                    <Ionicons name="cube" size={24} color="#2563EB" />
                  </View>
                  <View style={styles.productInfo}>
                    <Text style={styles.productName}>{product.name}</Text>
                    <Text style={styles.productStock}>Stock: {product.stock} {product.unit || 'Nos'}</Text>
                  </View>
                  <View style={styles.productRight}>
                    <Text style={styles.productPrice}>₹{(parseFloat(product.price) || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</Text>
                    <Ionicons name="chevron-forward" size={20} color="#94A3B8" />
                  </View>
                </View>
              ))}
            </ScrollView>
          </View>
        )}

        <View style={styles.section}>
          <View style={styles.sectionHeader}>
            <Text style={styles.sectionTitle}>All Products</Text>
            <Text style={styles.productCount}>{filteredProducts.length} items</Text>
          </View>

          {filteredProducts.length === 0 ? (
            <View style={styles.emptyState}>
              <Ionicons name="cube-outline" size={48} color="#CBD5E1" />
              <Text style={styles.emptyText}>{t('noProductsFound')}</Text>
              <Text style={styles.emptySubtext}>Add your first product to get started</Text>
            </View>
          ) : (
            <View style={styles.productList}>
              {filteredProducts.map((product) => (
                <TouchableOpacity
                  key={product.id}
                  style={styles.productCard}
                  onPress={() => navigation.navigate('ProductDetails', { product })}
                  activeOpacity={0.7}
                >
                  <View style={styles.productIcon}>
                    <Ionicons name="cube" size={24} color="#2563EB" />
                  </View>
                  <View style={styles.productInfo}>
                    <Text style={styles.productName}>{product.name}</Text>
                    <Text style={styles.productCategory}>{product.category}</Text>
                    <Text style={[styles.productStock, product.stock <= (product.low_stock_threshold || 5) && { color: '#EF4444' }]}>
                      Stock: {product.stock} {product.unit || 'Nos'}
                    </Text>
                  </View>
                  <View style={styles.productRight}>
                    <Text style={styles.productPrice}>₹{(parseFloat(product.price) || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</Text>
                    <Ionicons name="chevron-forward" size={20} color="#94A3B8" />
                  </View>
                </TouchableOpacity>
              ))}
            </View>
          )}
        </View>
      </ScrollView>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F8FAFC',
  },
  header: {
    paddingHorizontal: 20,
    paddingTop: 20,
    paddingBottom: 20,
    borderBottomLeftRadius: 24,
    borderBottomRightRadius: 24,
  },
  headerTop: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 20,
  },
  headerLeft: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  headerTitle: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginLeft: 12,
  },
  headerRight: {
    flexDirection: 'row',
  },
  headerButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: 'rgba(255, 255, 255, 0.2)',
    justifyContent: 'center',
    alignItems: 'center',
    marginLeft: 8,
  },
  statsRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  statBox: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(255, 255, 255, 0.95)',
    padding: 10,
    borderRadius: 12,
    flex: 1,
    marginHorizontal: 3,
  },
  statIconContainer: {
    width: 28,
    height: 28,
    borderRadius: 14,
    backgroundColor: '#DBEAFE',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 6,
  },
  statTextContainer: {
    flex: 1,
    minWidth: 0,
  },
  statLabel: {
    fontSize: 9,
    color: '#64748B',
    flexWrap: 'wrap',
  },
  statValue: {
    fontSize: 13,
    fontWeight: 'bold',
    color: '#1E293B',
    marginTop: 2,
  },
  content: {
    flex: 1,
    padding: 20,
  },
  addButton: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 20,
  },
  addButtonGradient: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    padding: 16,
    borderRadius: 12,
    marginRight: 12,
  },
  addButtonText: {
    flex: 1,
    fontSize: 16,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginLeft: 12,
  },
  addIconButton: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: '#FFFFFF',
    justifyContent: 'center',
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  section: {
    marginBottom: 16,
  },
  sectionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#1E293B',
  },
  alertBadge: {
    backgroundColor: '#FEE2E2',
    paddingHorizontal: 12,
    paddingVertical: 4,
    borderRadius: 12,
  },
  alertBadgeText: {
    color: '#EF4444',
    fontSize: 12,
    fontWeight: 'bold',
  },
  categoriesScroll: {
    marginBottom: 8,
  },
  categoryCard: {
    width: 100,
    padding: 16,
    borderRadius: 12,
    marginRight: 12,
    alignItems: 'center',
  },
  categoryIcon: {
    fontSize: 32,
    marginBottom: 8,
  },
  categoryName: {
    fontSize: 12,
    fontWeight: '600',
    color: '#FFFFFF',
    textAlign: 'center',
  },
  productList: {
    marginBottom: 20,
  },
  productCard: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFFFFF',
    padding: 16,
    borderRadius: 12,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 2,
    elevation: 2,
  },
  productIcon: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: '#EFF6FF',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 12,
  },
  productInfo: {
    flex: 1,
  },
  productName: {
    fontSize: 16,
    fontWeight: '600',
    color: '#1E293B',
    marginBottom: 4,
  },
  productCategory: {
    fontSize: 12,
    color: '#94A3B8',
    marginBottom: 4,
  },
  productStock: {
    fontSize: 14,
    color: '#64748B',
  },
  productRight: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  productPrice: {
    fontSize: 14,
    fontWeight: 'bold',
    color: '#1E293B',
    marginRight: 8,
  },
  productCount: {
    fontSize: 14,
    color: '#64748B',
    fontWeight: '600',
  },
  emptyState: {
    alignItems: 'center',
    justifyContent: 'center',
    padding: 40,
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
  },
  emptyText: {
    fontSize: 16,
    fontWeight: '600',
    color: '#64748B',
    marginTop: 16,
  },
  emptySubtext: {
    fontSize: 12,
    color: '#CBD5E1',
    marginTop: 4,
  },
  searchBar: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    padding: 12,
    marginBottom: 20,
  },
  searchInput: {
    flex: 1,
    marginLeft: 8,
    fontSize: 14,
    color: '#1E293B',
  },
  productCardContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 12,
  },
  productActions: {
    flexDirection: 'row',
    marginLeft: 8,
  },
  editButton: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: '#DBEAFE',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 8,
  },
  deleteButton: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: '#FEE2E2',
    justifyContent: 'center',
    alignItems: 'center',
  },
});

export default InventoryScreen;
