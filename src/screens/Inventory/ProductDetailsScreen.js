import React, { useContext, useMemo, useState } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, Alert, Modal, TextInput } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import * as Print from 'expo-print';
import * as Sharing from 'expo-sharing';
import { AppContext } from '../../context/AppContext';
import { API_URL } from '../../services/api';

const ProductDetailsScreen = ({ route, navigation }) => {
  const { product: routeProduct } = route.params;
  const { bills, deleteProduct, updateProduct, user, products } = useContext(AppContext);
  const [showUpdateModal, setShowUpdateModal] = useState(false);
  const [updateQuantity, setUpdateQuantity] = useState('');
  const [stockAdjustments, setStockAdjustments] = useState([]);

  // Get current product from context to show updated stock
  const product = products.find(p => p.id === routeProduct.id) || routeProduct;
  
  // Load stock adjustments from database
  React.useEffect(() => {
    const loadStockAdjustments = async () => {
      if (!user || !user.id || !product || !product.id) {
        return;
      }

      try {
        const response = await fetch(`${API_URL}/stock_adjustments.php?user_id=${user.id}&product_id=${product.id}`);
        const data = await response.json();
        
        if (data.success && data.adjustments) {
          setStockAdjustments(data.adjustments);
        } else {
          setStockAdjustments([]);
        }
      } catch (error) {
        // silent
        setStockAdjustments([]);
      }
    };

    loadStockAdjustments();
  }, [user, product]);

  // Calculate transaction history from bills and stock adjustments
  const transactions = useMemo(() => {
    const safeBills = bills || [];
    const productTransactions = [];

    // Add sales from bills
    safeBills.forEach(bill => {
      let billItems = bill.items;
      
      // Parse items if it's a JSON string
      if (typeof billItems === 'string') {
        try {
          billItems = JSON.parse(billItems);
        } catch (e) {
          billItems = [];
        }
      }

      if (Array.isArray(billItems)) {
        billItems.forEach(item => {
          if (item.productId === product.id) {
            productTransactions.push({
              id: bill.id,
              type: 'sale',
              quantity: item.quantity,
              amount: item.price * item.quantity,
              date: bill.date,
              invoiceNumber: bill.invoice_number || bill.invoiceNumber,
              customerName: bill.customer_name || bill.customerName
            });
          }
        });
      }
    });

    // Add stock adjustments
    stockAdjustments.forEach((adjustment, index) => {
      productTransactions.push({
        id: `adj_${index}`,
        type: 'stock_added',
        quantity: adjustment.quantity,
        amount: 0,
        date: adjustment.date,
        note: adjustment.note || 'Stock Added'
      });
    });

    return productTransactions.sort((a, b) => new Date(b.date) - new Date(a.date));
  }, [bills, product.id, stockAdjustments]);

  const totalSold = transactions.filter(t => t.type === 'sale').reduce((sum, t) => sum + t.quantity, 0);
  const totalRevenue = transactions.filter(t => t.type === 'sale').reduce((sum, t) => sum + t.amount, 0);

  const handleEdit = () => {
    navigation.navigate('EditProduct', { product });
  };

  const handleUpdateStock = async () => {
    if (!updateQuantity || parseInt(updateQuantity) <= 0) {
      Alert.alert('Error', 'Please enter a valid quantity');
      return;
    }

    if (!user || !user.id) {
      Alert.alert('Error', 'User information not available');
      return;
    }

    const qty = parseInt(updateQuantity);
    const newStock = product.stock + qty;

    try {
      // Update product stock in database
      await updateProduct(product.id, { stock: newStock });

      // Save stock adjustment to database
      const adjustmentData = {
        product_id: product.id,
        user_id: user.id,
        quantity: qty,
        date: new Date().toISOString().split('T')[0],
        note: 'Stock Added'
      };

      const response = await fetch(`${API_URL}/stock_adjustments.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(adjustmentData),
      });

      const data = await response.json();

      if (data.success) {
        // Add to local state with database ID
        const newAdjustment = {
          id: data.id,
          product_id: product.id,
          quantity: qty,
          date: new Date().toISOString().split('T')[0],
          note: 'Stock Added'
        };
        setStockAdjustments([newAdjustment, ...stockAdjustments]);

        // Update product object
        product.stock = newStock;

        setShowUpdateModal(false);
        setUpdateQuantity('');
        Alert.alert('Success', `Added ${qty} units to stock`);
      } else {
        Alert.alert('Error', 'Failed to save stock adjustment');
      }
    } catch (error) {
      // silent
      Alert.alert('Error', 'Failed to update stock');
    }
  };

  const generatePDF = () => {
    const transactionsHTML = transactions.map(t => `
      <tr style="border-bottom: 1px solid #E2E8F0;">
        <td style="padding: 12px; text-align: left;">
          ${new Date(t.date).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })}
        </td>
        <td style="padding: 12px; text-align: left;">
          ${t.type === 'sale' ? 'Sale' : 'Stock Added'}
        </td>
        <td style="padding: 12px; text-align: left;">
          ${t.type === 'sale' ? `${t.customerName || 'N/A'} • Invoice #${t.invoiceNumber}` : t.note || 'Stock Adjustment'}
        </td>
        <td style="padding: 12px; text-align: center; color: ${t.type === 'sale' ? '#EF4444' : '#10B981'}; font-weight: bold;">
          ${t.type === 'sale' ? '-' : '+'}${t.quantity}
        </td>
        <td style="padding: 12px; text-align: right;">
          ₹${t.amount.toLocaleString()}
        </td>
      </tr>
    `).join('');

    return `
      <!DOCTYPE html>
      <html>
        <head>
          <meta name="viewport" content="width=device-width, initial-scale=1.0">
          <style>
            body { font-family: Arial, sans-serif; padding: 20px; }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #10B981; padding-bottom: 20px; }
            .company-name { font-size: 24px; font-weight: bold; color: #10B981; }
            .report-title { font-size: 18px; margin-top: 10px; color: #1E293B; }
            .product-info { background-color: #F8FAFC; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
            .info-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
            .info-label { font-weight: bold; color: #64748B; }
            .info-value { color: #1E293B; }
            .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 30px; }
            .stat-box { background-color: #F8FAFC; padding: 15px; border-radius: 8px; text-align: center; }
            .stat-value { font-size: 24px; font-weight: bold; color: #10B981; }
            .stat-label { font-size: 12px; color: #64748B; margin-top: 5px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th { background-color: #10B981; color: white; padding: 12px; text-align: left; }
            .footer { text-align: center; margin-top: 40px; color: #64748B; font-size: 12px; }
          </style>
        </head>
        <body>
          <div class="header">
            <div class="company-name">BINEST</div>
            <div class="report-title">Product Transaction History</div>
            <div style="font-size: 12px; color: #64748B; margin-top: 5px;">
              Generated on ${new Date().toLocaleDateString('en-IN', { day: 'numeric', month: 'long', year: 'numeric' })}
            </div>
          </div>

          <div class="product-info">
            <h3 style="margin-top: 0; color: #1E293B;">Product Information</h3>
            <div class="info-row">
              <span class="info-label">Product Name:</span>
              <span class="info-value">${product.name}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Category:</span>
              <span class="info-value">${product.category}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Current Stock:</span>
              <span class="info-value">${product.stock} ${product.unit || 'Nos'}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Price:</span>
              <span class="info-value">₹${parseFloat(product.price).toLocaleString()}</span>
            </div>
          </div>

          <div class="stats-grid">
            <div class="stat-box">
              <div class="stat-value">${totalSold}</div>
              <div class="stat-label">Total Sold</div>
            </div>
            <div class="stat-box">
              <div class="stat-value">₹${totalRevenue.toLocaleString()}</div>
              <div class="stat-label">Total Revenue</div>
            </div>
            <div class="stat-box">
              <div class="stat-value">${transactions.length}</div>
              <div class="stat-label">Total Transactions</div>
            </div>
          </div>

          <h3 style="color: #1E293B;">Transaction History</h3>
          <table>
            <thead>
              <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Details</th>
                <th style="text-align: center;">Quantity</th>
                <th style="text-align: right;">Amount</th>
              </tr>
            </thead>
            <tbody>
              ${transactionsHTML}
            </tbody>
          </table>

          <div class="footer">
            <div>This is a computer-generated report from BINEST App</div>
            <div style="margin-top: 5px;">Product: ${product.name} | Total Transactions: ${transactions.length}</div>
          </div>
        </body>
      </html>
    `;
  };

  const downloadPDF = async () => {
    try {
      const html = generatePDF();
      const { uri } = await Print.printToFileAsync({ html });
      
      Alert.alert('Success', 'PDF generated successfully!', [
        {
          text: 'Share',
          onPress: async () => {
            if (await Sharing.isAvailableAsync()) {
              await Sharing.shareAsync(uri);
            }
          }
        },
        { text: 'OK' }
      ]);
    } catch (error) {
      // silent
      Alert.alert('Error', 'Failed to generate PDF');
    }
  };

  const printHistory = async () => {
    try {
      const html = generatePDF();
      await Print.printAsync({ html });
    } catch (error) {
      // silent
      Alert.alert('Error', 'Failed to print');
    }
  };

  const handleDelete = () => {
    Alert.alert(
      'Delete Product',
      `Are you sure you want to delete "${product.name}"?\n\nThis action cannot be undone.`,
      [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Delete',
          style: 'destructive',
          onPress: async () => {
            await deleteProduct(product.id);
            navigation.goBack();
          }
        }
      ]
    );
  };

  return (
    <SafeAreaView style={styles.container}>
      <LinearGradient
        colors={['#10B981', '#059669']}
        style={styles.header}
      >
        <View style={styles.headerTop}>
          <TouchableOpacity onPress={() => navigation.goBack()}>
            <Ionicons name="arrow-back" size={24} color="#FFFFFF" />
          </TouchableOpacity>
          <Text style={styles.headerTitle}>Product Details</Text>
          <View style={styles.headerActions}>
            <TouchableOpacity onPress={printHistory} style={styles.headerButton}>
              <Ionicons name="print-outline" size={20} color="#FFFFFF" />
            </TouchableOpacity>
            <TouchableOpacity onPress={downloadPDF} style={styles.headerButton}>
              <Ionicons name="download-outline" size={20} color="#FFFFFF" />
            </TouchableOpacity>
          </View>
        </View>

        <View style={styles.productHeader}>
          <View style={styles.productIcon}>
            <Ionicons name="cube" size={32} color="#10B981" />
          </View>
          <Text style={styles.productName}>{product.name}</Text>
          <Text style={styles.productCategory}>{product.category}</Text>
        </View>
      </LinearGradient>

      <ScrollView style={styles.content}>
        {/* Product Info Card */}
        <View style={styles.card}>
          <Text style={styles.cardTitle}>Product Information</Text>
          
          <View style={styles.infoRow}>
            <Text style={styles.infoLabel}>Price</Text>
            <Text style={styles.infoValue}>₹{parseFloat(product.price).toLocaleString()}</Text>
          </View>

          <View style={styles.infoRow}>
            <Text style={styles.infoLabel}>Current Stock</Text>
            <Text style={[styles.infoValue, product.stock <= (product.low_stock_threshold || 5) && styles.lowStock]}>
              {product.stock} {product.unit || 'Nos'}
            </Text>
          </View>

          <View style={styles.infoRow}>
            <Text style={styles.infoLabel}>Low Stock Alert</Text>
            <Text style={styles.infoValue}>{product.low_stock_threshold || 5} units</Text>
          </View>

          {product.description && (
            <View style={styles.infoRow}>
              <Text style={styles.infoLabel}>Description</Text>
              <Text style={styles.infoDescription}>{product.description}</Text>
            </View>
          )}
        </View>

        {/* Statistics Card */}
        <View style={styles.card}>
          <Text style={styles.cardTitle}>Sales Statistics</Text>
          
          <View style={styles.statsGrid}>
            <View style={styles.statBox}>
              <Ionicons name="trending-down" size={24} color="#EF4444" />
              <Text style={styles.statValue}>{totalSold}</Text>
              <Text style={styles.statLabel}>Total Sold</Text>
            </View>

            <View style={styles.statBox}>
              <Ionicons name="cash" size={24} color="#10B981" />
              <Text style={styles.statValue}>₹{totalRevenue.toLocaleString()}</Text>
              <Text style={styles.statLabel}>Revenue</Text>
            </View>

            <View style={styles.statBox}>
              <Ionicons name="receipt" size={24} color="#3B82F6" />
              <Text style={styles.statValue}>{transactions.length}</Text>
              <Text style={styles.statLabel}>Transactions</Text>
            </View>
          </View>
        </View>

        {/* Transaction History */}
        <View style={styles.card}>
          <Text style={styles.cardTitle}>Transaction History</Text>
          
          {transactions.length > 0 ? (
            transactions.map((transaction, index) => (
              <View key={index} style={styles.transactionItem}>
                <View style={styles.transactionIcon}>
                  <Ionicons 
                    name={transaction.type === 'sale' ? 'arrow-down' : 'arrow-up'} 
                    size={20} 
                    color={transaction.type === 'sale' ? '#EF4444' : '#10B981'} 
                  />
                </View>
                <View style={styles.transactionInfo}>
                  <Text style={styles.transactionTitle}>
                    {transaction.type === 'sale' ? 'Sale' : 'Stock Added'}
                  </Text>
                  <Text style={styles.transactionSubtitle}>
                    {transaction.type === 'sale' 
                      ? `${transaction.customerName || 'N/A'} • Invoice #${transaction.invoiceNumber}`
                      : transaction.note || 'Stock Adjustment'
                    }
                  </Text>
                  <Text style={styles.transactionDate}>
                    {new Date(transaction.date).toLocaleDateString('en-IN', {
                      day: 'numeric',
                      month: 'short',
                      year: 'numeric'
                    })}
                  </Text>
                </View>
                <View style={styles.transactionAmount}>
                  <Text style={[styles.transactionQty, transaction.type === 'sale' && styles.saleQty]}>
                    {transaction.type === 'sale' ? '-' : '+'}{transaction.quantity}
                  </Text>
                  <Text style={styles.transactionValue}>₹{transaction.amount.toLocaleString()}</Text>
                </View>
              </View>
            ))
          ) : (
            <View style={styles.emptyState}>
              <Ionicons name="receipt-outline" size={48} color="#CBD5E1" />
              <Text style={styles.emptyText}>No transactions yet</Text>
            </View>
          )}
        </View>

        {/* Action Buttons */}
        <TouchableOpacity style={styles.updateStockButton} onPress={() => setShowUpdateModal(true)}>
          <Ionicons name="add-circle" size={24} color="#FFFFFF" />
          <Text style={styles.updateStockButtonText}>Update Stock</Text>
        </TouchableOpacity>

        <View style={styles.actionButtons}>
          <TouchableOpacity style={styles.editButton} onPress={handleEdit}>
            <Ionicons name="create-outline" size={18} color="#3B82F6" />
            <Text style={styles.editButtonText}>Edit</Text>
          </TouchableOpacity>

          <TouchableOpacity style={styles.deleteButton} onPress={handleDelete}>
            <Ionicons name="trash-outline" size={18} color="#EF4444" />
            <Text style={styles.deleteButtonText}>Delete</Text>
          </TouchableOpacity>
        </View>
      </ScrollView>

      {/* Update Stock Modal */}
      <Modal
        visible={showUpdateModal}
        transparent={true}
        animationType="slide"
        onRequestClose={() => setShowUpdateModal(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Update Stock</Text>
              <TouchableOpacity onPress={() => setShowUpdateModal(false)}>
                <Ionicons name="close" size={24} color="#64748B" />
              </TouchableOpacity>
            </View>

            <Text style={styles.modalLabel}>Add Quantity</Text>
            <TextInput
              style={styles.modalInput}
              placeholder="Enter quantity to add"
              value={updateQuantity}
              onChangeText={setUpdateQuantity}
              keyboardType="numeric"
            />

            <Text style={styles.modalInfo}>
              Current Stock: {product.stock} {product.unit || 'Nos'}
            </Text>
            <Text style={styles.modalInfo}>
              New Stock: {product.stock + (parseInt(updateQuantity) || 0)} {product.unit || 'Nos'}
            </Text>

            <View style={styles.modalButtons}>
              <TouchableOpacity 
                style={styles.modalCancelButton} 
                onPress={() => {
                  setShowUpdateModal(false);
                  setUpdateQuantity('');
                }}
              >
                <Text style={styles.modalCancelText}>Cancel</Text>
              </TouchableOpacity>

              <TouchableOpacity 
                style={styles.modalSaveButton} 
                onPress={handleUpdateStock}
              >
                <Text style={styles.modalSaveText}>Add Stock</Text>
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F8FAFC',
  },
  header: {
    paddingTop: 20,
    paddingBottom: 30,
    paddingHorizontal: 20,
  },
  headerTop: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 20,
  },
  headerTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  headerActions: {
    flexDirection: 'row',
    gap: 8,
  },
  headerButton: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: 'rgba(255, 255, 255, 0.2)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  productHeader: {
    alignItems: 'center',
  },
  productIcon: {
    width: 64,
    height: 64,
    borderRadius: 32,
    backgroundColor: '#FFFFFF',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 12,
  },
  productName: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 4,
  },
  productCategory: {
    fontSize: 14,
    color: '#D1FAE5',
  },
  content: {
    flex: 1,
    padding: 16,
  },
  card: {
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    padding: 16,
    marginBottom: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  cardTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#1E293B',
    marginBottom: 16,
  },
  infoRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: '#F1F5F9',
  },
  infoLabel: {
    fontSize: 14,
    color: '#64748B',
  },
  infoValue: {
    fontSize: 14,
    fontWeight: '600',
    color: '#1E293B',
  },
  lowStock: {
    color: '#EF4444',
  },
  infoDescription: {
    fontSize: 14,
    color: '#1E293B',
    flex: 1,
    marginLeft: 16,
    textAlign: 'right',
  },
  statsGrid: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  statBox: {
    flex: 1,
    alignItems: 'center',
    padding: 12,
    backgroundColor: '#F8FAFC',
    borderRadius: 8,
    marginHorizontal: 4,
  },
  statValue: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#1E293B',
    marginTop: 8,
  },
  statLabel: {
    fontSize: 12,
    color: '#64748B',
    marginTop: 4,
  },
  transactionItem: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: '#F1F5F9',
  },
  transactionIcon: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: '#F8FAFC',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  transactionInfo: {
    flex: 1,
  },
  transactionTitle: {
    fontSize: 14,
    fontWeight: '600',
    color: '#1E293B',
  },
  transactionSubtitle: {
    fontSize: 12,
    color: '#64748B',
    marginTop: 2,
  },
  transactionDate: {
    fontSize: 11,
    color: '#94A3B8',
    marginTop: 2,
  },
  transactionAmount: {
    alignItems: 'flex-end',
  },
  transactionQty: {
    fontSize: 14,
    fontWeight: 'bold',
    color: '#10B981',
  },
  saleQty: {
    color: '#EF4444',
  },
  transactionValue: {
    fontSize: 12,
    color: '#64748B',
    marginTop: 2,
  },
  emptyState: {
    alignItems: 'center',
    paddingVertical: 32,
  },
  emptyText: {
    fontSize: 14,
    color: '#94A3B8',
    marginTop: 12,
  },
  updateStockButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#10B981',
    padding: 16,
    borderRadius: 12,
    marginBottom: 12,
    gap: 8,
  },
  updateStockButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: 'bold',
  },
  actionButtons: {
    flexDirection: 'row',
    gap: 12,
    marginBottom: 20,
  },
  editButton: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#EFF6FF',
    padding: 12,
    borderRadius: 8,
    gap: 6,
  },
  editButtonText: {
    color: '#3B82F6',
    fontSize: 14,
    fontWeight: '600',
  },
  deleteButton: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#FEE2E2',
    padding: 12,
    borderRadius: 8,
    gap: 6,
  },
  deleteButtonText: {
    color: '#EF4444',
    fontSize: 14,
    fontWeight: '600',
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
  },
  modalContent: {
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    padding: 20,
    width: '100%',
    maxWidth: 400,
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 20,
  },
  modalTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#1E293B',
  },
  modalLabel: {
    fontSize: 14,
    fontWeight: '600',
    color: '#1E293B',
    marginBottom: 8,
  },
  modalInput: {
    backgroundColor: '#F8FAFC',
    borderWidth: 1,
    borderColor: '#E2E8F0',
    borderRadius: 8,
    padding: 12,
    fontSize: 16,
    marginBottom: 16,
  },
  modalInfo: {
    fontSize: 14,
    color: '#64748B',
    marginBottom: 8,
  },
  modalButtons: {
    flexDirection: 'row',
    gap: 12,
    marginTop: 20,
  },
  modalCancelButton: {
    flex: 1,
    padding: 14,
    borderRadius: 8,
    backgroundColor: '#F1F5F9',
    alignItems: 'center',
  },
  modalCancelText: {
    color: '#64748B',
    fontSize: 16,
    fontWeight: '600',
  },
  modalSaveButton: {
    flex: 1,
    padding: 14,
    borderRadius: 8,
    backgroundColor: '#10B981',
    alignItems: 'center',
  },
  modalSaveText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: 'bold',
  },
});

export default ProductDetailsScreen;
