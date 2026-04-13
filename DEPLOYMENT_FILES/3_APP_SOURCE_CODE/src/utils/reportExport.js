import * as FileSystem from 'expo-file-system';
import * as Sharing from 'expo-sharing';
import { Alert } from 'react-native';

/**
 * Export report data to CSV format
 */
export const exportToCSV = async (data, filename = 'report') => {
  try {
    const csvContent = generateCSV(data);
    const fileUri = FileSystem.documentDirectory + `${filename}.csv`;
    
    await FileSystem.writeAsStringAsync(fileUri, csvContent, {
      encoding: 'utf8',
    });
    
    if (await Sharing.isAvailableAsync()) {
      await Sharing.shareAsync(fileUri);
      return { success: true, message: 'Report exported successfully' };
    } else {
      Alert.alert('Error', 'Sharing is not available on this device');
      return { success: false, error: 'Sharing not available' };
    }
  } catch (error) {
    // silent
    Alert.alert('Error', 'Failed to export report');
    return { success: false, error: error.message };
  }
};

/**
 * Export report data to HTML format (can be opened as document)
 */
export const exportToHTML = async (data, filename = 'report') => {
  try {
    const htmlContent = generateHTML(data);
    const fileUri = FileSystem.documentDirectory + `${filename}.html`;
    
    await FileSystem.writeAsStringAsync(fileUri, htmlContent, {
      encoding: 'utf8',
    });
    
    if (await Sharing.isAvailableAsync()) {
      await Sharing.shareAsync(fileUri);
      return { success: true, message: 'Report exported successfully' };
    } else {
      Alert.alert('Error', 'Sharing is not available on this device');
      return { success: false, error: 'Sharing not available' };
    }
  } catch (error) {
    // silent
    Alert.alert('Error', 'Failed to export report');
    return { success: false, error: error.message };
  }
};

/**
 * Generate CSV content from report data
 */
const generateCSV = (data) => {
  const { reportType, period, stats, bills, expenses, products } = data;
  
  let csv = `Binest Report - ${reportType}\n`;
  csv += `Period: ${period}\n`;
  csv += `Generated: ${new Date().toLocaleString()}\n\n`;
  
  // Summary Section
  csv += `SUMMARY\n`;
  csv += `Total Sales,₹${stats.totalSales.toFixed(2)}\n`;
  csv += `Total Expenses,₹${stats.totalExpenses.toFixed(2)}\n`;
  csv += `Net Profit,₹${stats.profit.toFixed(2)}\n\n`;
  
  // Bills Section
  if (bills && bills.length > 0) {
    csv += `SALES DETAILS\n`;
    csv += `Invoice Number,Customer Name,Date,Amount,Payment Mode\n`;
    bills.forEach(bill => {
      csv += `${bill.invoiceNumber || bill.id},${bill.customerName},${new Date(bill.date).toLocaleDateString()},₹${bill.grandTotal || bill.total},${bill.paymentMode || 'Cash'}\n`;
    });
    csv += `\n`;
  }
  
  // Expenses Section
  if (expenses && expenses.length > 0) {
    csv += `EXPENSES DETAILS\n`;
    csv += `Category,Description,Date,Amount\n`;
    expenses.forEach(expense => {
      csv += `${expense.category},${expense.description || 'N/A'},${new Date(expense.date).toLocaleDateString()},₹${expense.amount}\n`;
    });
    csv += `\n`;
  }
  
  // Products Section
  if (products && products.length > 0) {
    csv += `PRODUCTS INVENTORY\n`;
    csv += `Name,Category,Price,Stock,Value\n`;
    products.forEach(product => {
      const value = product.price * product.stock;
      csv += `${product.name},${product.category || 'N/A'},₹${product.price},${product.stock},₹${value.toFixed(2)}\n`;
    });
  }
  
  return csv;
};

/**
 * Generate HTML content from report data
 */
const generateHTML = (data) => {
  const { reportType, period, stats, bills, expenses, products } = data;
  
  return `
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Binest Report - ${reportType}</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      padding: 20px;
      max-width: 1200px;
      margin: 0 auto;
      background: #f5f5f5;
    }
    .header {
      background: linear-gradient(135deg, #2563EB, #1E40AF);
      color: white;
      padding: 30px;
      border-radius: 10px;
      margin-bottom: 20px;
    }
    .header h1 {
      margin: 0 0 10px 0;
      font-size: 28px;
    }
    .header p {
      margin: 5px 0;
      opacity: 0.9;
    }
    .summary {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    .summary-card {
      background: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .summary-card h3 {
      margin: 0 0 10px 0;
      color: #64748B;
      font-size: 14px;
      font-weight: normal;
    }
    .summary-card .amount {
      font-size: 28px;
      font-weight: bold;
      color: #1E293B;
    }
    .summary-card.profit .amount {
      color: ${stats.profit >= 0 ? '#10B981' : '#EF4444'};
    }
    .section {
      background: white;
      padding: 20px;
      border-radius: 10px;
      margin-bottom: 20px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .section h2 {
      margin: 0 0 20px 0;
      color: #1E293B;
      font-size: 20px;
      border-bottom: 2px solid #E2E8F0;
      padding-bottom: 10px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
    }
    th {
      background: #F1F5F9;
      padding: 12px;
      text-align: left;
      font-weight: 600;
      color: #475569;
      border-bottom: 2px solid #E2E8F0;
    }
    td {
      padding: 12px;
      border-bottom: 1px solid #E2E8F0;
      color: #1E293B;
    }
    tr:hover {
      background: #F8FAFC;
    }
    .footer {
      text-align: center;
      color: #64748B;
      margin-top: 30px;
      padding-top: 20px;
      border-top: 1px solid #E2E8F0;
    }
    @media print {
      body {
        background: white;
      }
      .header {
        background: #2563EB;
      }
    }
  </style>
</head>
<body>
  <div class="header">
    <h1>📊 Binest Report</h1>
    <p><strong>Report Type:</strong> ${reportType}</p>
    <p><strong>Period:</strong> ${period}</p>
    <p><strong>Generated:</strong> ${new Date().toLocaleString()}</p>
  </div>

  <div class="summary">
    <div class="summary-card">
      <h3>Total Sales</h3>
      <div class="amount">₹${stats.totalSales.toFixed(2)}</div>
    </div>
    <div class="summary-card">
      <h3>Total Expenses</h3>
      <div class="amount">₹${stats.totalExpenses.toFixed(2)}</div>
    </div>
    <div class="summary-card profit">
      <h3>Net Profit</h3>
      <div class="amount">₹${stats.profit.toFixed(2)}</div>
    </div>
  </div>

  ${bills && bills.length > 0 ? `
  <div class="section">
    <h2>Sales Details (${bills.length} invoices)</h2>
    <table>
      <thead>
        <tr>
          <th>Invoice #</th>
          <th>Customer</th>
          <th>Date</th>
          <th>Amount</th>
          <th>Payment</th>
        </tr>
      </thead>
      <tbody>
        ${bills.map(bill => `
          <tr>
            <td>${bill.invoiceNumber || bill.id}</td>
            <td>${bill.customerName}</td>
            <td>${new Date(bill.date).toLocaleDateString()}</td>
            <td>₹${(bill.grandTotal || bill.total).toFixed(2)}</td>
            <td>${bill.paymentMode || 'Cash'}</td>
          </tr>
        `).join('')}
      </tbody>
    </table>
  </div>
  ` : ''}

  ${expenses && expenses.length > 0 ? `
  <div class="section">
    <h2>Expenses Details (${expenses.length} items)</h2>
    <table>
      <thead>
        <tr>
          <th>Category</th>
          <th>Description</th>
          <th>Date</th>
          <th>Amount</th>
        </tr>
      </thead>
      <tbody>
        ${expenses.map(expense => `
          <tr>
            <td>${expense.category}</td>
            <td>${expense.description || 'N/A'}</td>
            <td>${new Date(expense.date).toLocaleDateString()}</td>
            <td>₹${expense.amount.toFixed(2)}</td>
          </tr>
        `).join('')}
      </tbody>
    </table>
  </div>
  ` : ''}

  ${products && products.length > 0 ? `
  <div class="section">
    <h2>Products Inventory (${products.length} items)</h2>
    <table>
      <thead>
        <tr>
          <th>Product Name</th>
          <th>Category</th>
          <th>Price</th>
          <th>Stock</th>
          <th>Total Value</th>
        </tr>
      </thead>
      <tbody>
        ${products.map(product => `
          <tr>
            <td>${product.name}</td>
            <td>${product.category || 'N/A'}</td>
            <td>₹${product.price.toFixed(2)}</td>
            <td>${product.stock}</td>
            <td>₹${(product.price * product.stock).toFixed(2)}</td>
          </tr>
        `).join('')}
      </tbody>
    </table>
  </div>
  ` : ''}

  <div class="footer">
    <p>Generated by Binest - Business Management App</p>
    <p>© ${new Date().getFullYear()} All rights reserved</p>
  </div>
</body>
</html>
  `;
};

/**
 * Get filtered data based on report type
 */
export const getFilteredReportData = (reportType, bills, expenses, products) => {
  const now = new Date();
  let startDate, endDate, period;
  
  switch (reportType) {
    case 'today':
      startDate = new Date(now.setHours(0, 0, 0, 0));
      endDate = new Date(now.setHours(23, 59, 59, 999));
      period = `Today - ${startDate.toLocaleDateString()}`;
      break;
      
    case 'weekly':
      const weekStart = new Date(now);
      weekStart.setDate(now.getDate() - 7);
      startDate = weekStart;
      endDate = now;
      period = `Last 7 Days (${weekStart.toLocaleDateString()} - ${now.toLocaleDateString()})`;
      break;
      
    case 'monthly':
      startDate = new Date(now.getFullYear(), now.getMonth(), 1);
      endDate = new Date(now.getFullYear(), now.getMonth() + 1, 0);
      period = `${startDate.toLocaleString('default', { month: 'long', year: 'numeric' })}`;
      break;
      
    case 'yearly':
      startDate = new Date(now.getFullYear(), 0, 1);
      endDate = new Date(now.getFullYear(), 11, 31);
      period = `Year ${now.getFullYear()}`;
      break;
      
    default:
      startDate = new Date(0);
      endDate = now;
      period = 'All Time';
  }
  
  const filteredBills = bills.filter(bill => {
    const billDate = new Date(bill.date);
    return billDate >= startDate && billDate <= endDate;
  });
  
  const filteredExpenses = expenses.filter(expense => {
    const expenseDate = new Date(expense.date);
    return expenseDate >= startDate && expenseDate <= endDate;
  });
  
  const totalSales = filteredBills.reduce((sum, bill) => sum + (bill.grandTotal || bill.total || 0), 0);
  const totalExpenses = filteredExpenses.reduce((sum, expense) => sum + (expense.amount || 0), 0);
  const profit = totalSales - totalExpenses;
  
  return {
    reportType: reportType.charAt(0).toUpperCase() + reportType.slice(1),
    period,
    stats: { totalSales, totalExpenses, profit },
    bills: filteredBills,
    expenses: filteredExpenses,
    products: products || []
  };
};
