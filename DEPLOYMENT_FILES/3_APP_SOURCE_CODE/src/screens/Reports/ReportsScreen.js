import React, { useContext, useState } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, Dimensions, TextInput, Alert, Share, Modal } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { AppContext } from '../../context/AppContext';
import { useNavigation } from '@react-navigation/native';
import useTranslation from '../../i18n/useTranslation';
import * as FileSystem from 'expo-file-system/legacy';
import * as Sharing from 'expo-sharing';
import * as Print from 'expo-print';
import { exportToCSV, exportToHTML, getFilteredReportData } from '../../utils/reportExport';
import { exportSimpleReport } from '../../utils/simpleExport';

const ReportsScreen = () => {
  const { sales, expenses, products, bills } = useContext(AppContext);
  const navigation = useNavigation();
  const { t } = useTranslation();
  const [selectedPeriod, setSelectedPeriod] = useState('monthly');
  const [invoiceSearch, setInvoiceSearch] = useState('');
  const [reportType, setReportType] = useState('today'); // today, weekly, monthly, yearly
  const [showExportModal, setShowExportModal] = useState(false);
  const [selectedExportType, setSelectedExportType] = useState(null);
  const [showFormatModal, setShowFormatModal] = useState(false);

  const { totalSales, totalExpenses, profit } = React.useMemo(() => {
    const now = new Date();
    const safeSales = sales || [];
    const safeExpenses = expenses || [];

    const filteredSales = safeSales.filter(sale => {
      const saleDate = new Date(sale.date);
      if (selectedPeriod === 'daily') {
        return saleDate.toDateString() === now.toDateString();
      } else if (selectedPeriod === 'monthly') {
        return saleDate.getMonth() === now.getMonth() &&
          saleDate.getFullYear() === now.getFullYear();
      } else {
        return saleDate.getFullYear() === now.getFullYear();
      }
    });

    const filteredExpenses = safeExpenses.filter(expense => {
      const expenseDate = new Date(expense.date);
      if (selectedPeriod === 'daily') {
        return expenseDate.toDateString() === now.toDateString();
      } else if (selectedPeriod === 'monthly') {
        return expenseDate.getMonth() === now.getMonth() &&
          expenseDate.getFullYear() === now.getFullYear();
      } else {
        return expenseDate.getFullYear() === now.getFullYear();
      }
    });

    const totalSales = Math.round(filteredSales.reduce((sum, sale) => sum + (parseFloat(sale.amount) || 0), 0) * 100) / 100;
    const totalExpenses = Math.round(filteredExpenses.reduce((sum, expense) => sum + (parseFloat(expense.amount) || 0), 0) * 100) / 100;
    const profit = Math.round((totalSales - totalExpenses) * 100) / 100;

    return { totalSales, totalExpenses, profit };
  }, [sales, expenses, selectedPeriod]);

  const dueStats = React.useMemo(() => {
    const safeBills = bills || [];
    const dueBills = safeBills.filter(b => {
      const mode = (b.payment_mode || b.paymentMode || '').toLowerCase();
      return mode === 'due' && b.due_status !== 'paid';
    });
    const totalRemaining = dueBills.reduce((sum, b) => {
      const total = parseFloat(b.grand_total || b.grandTotal || b.total) || 0;
      const paid = parseFloat(b.paid_amount) || 0;
      return sum + (total - paid);
    }, 0);
    return { totalDue: Math.round(totalRemaining * 100) / 100, count: dueBills.length };
  }, [bills]);

  const chartData = React.useMemo(() => {
    const safeBills = bills || [];
    const today = new Date();
    let data = [];

    if (selectedPeriod === 'daily') {
      // Daily: Show last 7 days (weekly chart)
      const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

      for (let i = 6; i >= 0; i--) {
        const date = new Date(today);
        date.setDate(date.getDate() - i);
        const dateString = date.toDateString();

        const daySales = safeBills
          .filter(bill => new Date(bill.date).toDateString() === dateString)
          .reduce((sum, bill) => sum + (parseFloat(bill.grandTotal || bill.total || bill.grand_total) || 0), 0);

        data.push({
          label: days[date.getDay()],
          amount: daySales,
          date: dateString
        });
      }
    } else if (selectedPeriod === 'monthly') {
      // Monthly: Show date-wise for current month
      const year = today.getFullYear();
      const month = today.getMonth();
      const daysInMonth = new Date(year, month + 1, 0).getDate();

      for (let day = 1; day <= daysInMonth; day++) {
        const date = new Date(year, month, day);
        const dateString = date.toDateString();

        const daySales = safeBills
          .filter(bill => new Date(bill.date).toDateString() === dateString)
          .reduce((sum, bill) => sum + (parseFloat(bill.grandTotal || bill.total || bill.grand_total) || 0), 0);

        data.push({
          label: day.toString(),
          amount: daySales,
          date: dateString
        });
      }
    } else {
      // Yearly: Show month-wise for current year
      const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
      const year = today.getFullYear();

      for (let month = 0; month < 12; month++) {
        const monthSales = safeBills
          .filter(bill => {
            const billDate = new Date(bill.date);
            return billDate.getFullYear() === year && billDate.getMonth() === month;
          })
          .reduce((sum, bill) => sum + (parseFloat(bill.grandTotal || bill.total || bill.grand_total) || 0), 0);

        data.push({
          label: months[month],
          amount: monthSales,
          month: month
        });
      }
    }

    const maxAmount = Math.max(...data.map(d => d.amount), 1);

    return data.map(d => ({
      ...d,
      percentage: (d.amount / maxAmount) * 100
    }));
  }, [bills, selectedPeriod]);

  const searchInvoice = () => {
    if (!invoiceSearch.trim()) {
      Alert.alert('Error', 'Please enter invoice number');
      return;
    }

    const safeBills = bills || [];

    // Remove # symbol and trim/lowercase the search term
    const searchTerm = invoiceSearch.trim().replace('#', '').toLowerCase();

    const foundBill = safeBills.find(b => {
      // Remove # from invoice number and ID before comparing
      const invoiceNum = (b.invoice_number || b.invoiceNumber) ? String(b.invoice_number || b.invoiceNumber).replace('#', '').toLowerCase() : '';
      const billId = b.id ? String(b.id).replace('#', '').toLowerCase() : '';

      // Exact match only - no partial matches
      return invoiceNum === searchTerm || billId === searchTerm;
    });

    if (foundBill) {
      setInvoiceSearch('');
      navigation.navigate('Invoice', { bill: foundBill });
    } else {
      Alert.alert('Not Found', `Invoice not found with number: ${invoiceSearch}`);
    }
  };

  const handleExportTypeSelect = (type) => {
    setSelectedExportType(type);
    setShowExportModal(false);
    setShowFormatModal(true);
  };

  const handleExport = async (format) => {
    try {
      setShowFormatModal(false);

      let csvContent = '';
      const now = new Date();
      const periodLabel = selectedPeriod.charAt(0).toUpperCase() + selectedPeriod.slice(1);

      if (format === 'pdf') {
        // Generate PDF
        await handlePDFExport(selectedExportType);
        return;
      }

      // CSV Export
      if (selectedExportType === 'summary') {
        // Export Summary Report
        csvContent = `Business Report - ${periodLabel}\n`;
        csvContent += `Generated: ${now.toLocaleDateString()} ${now.toLocaleTimeString()}\n\n`;
        csvContent += `Financial Summary\n`;
        csvContent += `Total Sales,${totalSales.toFixed(2)}\n`;
        csvContent += `Total Expenses,${totalExpenses.toFixed(2)}\n`;
        csvContent += `Net Profit,${profit.toFixed(2)}\n\n`;

        csvContent += `Sales Chart Data\n`;
        csvContent += `Period,Amount\n`;
        (chartData || []).forEach(item => {
          csvContent += `${item.label},${item.amount.toFixed(2)}\n`;
        });
      } else if (selectedExportType === 'detailed') {
        // Export Detailed Bills Report
        csvContent = `Detailed Bills Report - ${periodLabel}\n`;
        csvContent += `Generated: ${now.toLocaleDateString()} ${now.toLocaleTimeString()}\n\n`;
        csvContent += `Invoice No,Customer,Date,Amount,Payment Method\n`;

        const safeBills = bills || [];
        const filteredBills = safeBills.filter(bill => {
          if (!bill || !bill.date) return false;
          const billDate = new Date(bill.date);
          if (selectedPeriod === 'daily') {
            return billDate.toDateString() === now.toDateString();
          } else if (selectedPeriod === 'monthly') {
            return billDate.getMonth() === now.getMonth() && billDate.getFullYear() === now.getFullYear();
          } else {
            return billDate.getFullYear() === now.getFullYear();
          }
        });

        filteredBills.forEach(bill => {
          const customerName = (bill.customer_name || bill.customerName || 'Customer').replace(/,/g, ' ');
          const amount = bill.grand_total || bill.grandTotal || bill.total || 0;
          const paymentMethod = (bill.payment_method || bill.paymentMethod || 'Cash').replace(/,/g, ' ');
          csvContent += `${bill.invoiceNumber || bill.id},${customerName},${new Date(bill.date).toLocaleDateString()},${parseFloat(amount).toFixed(2)},${paymentMethod}\n`;
        });

        csvContent += `\nTotal Bills,${filteredBills.length}\n`;
        csvContent += `Total Amount,${filteredBills.reduce((sum, bill) => sum + parseFloat(bill.grand_total || bill.grandTotal || bill.total || 0), 0).toFixed(2)}\n`;
      } else if (selectedExportType === 'expenses') {
        // Export Expenses Report
        csvContent = `Expenses Report - ${periodLabel}\n`;
        csvContent += `Generated: ${now.toLocaleDateString()} ${now.toLocaleTimeString()}\n\n`;
        csvContent += `Date,Category,Description,Amount\n`;

        const safeExpenses = expenses || [];
        const filteredExpenses = safeExpenses.filter(expense => {
          if (!expense || !expense.date) return false;
          const expenseDate = new Date(expense.date);
          if (selectedPeriod === 'daily') {
            return expenseDate.toDateString() === now.toDateString();
          } else if (selectedPeriod === 'monthly') {
            return expenseDate.getMonth() === now.getMonth() && expenseDate.getFullYear() === now.getFullYear();
          } else {
            return expenseDate.getFullYear() === now.getFullYear();
          }
        });

        filteredExpenses.forEach(expense => {
          const category = (expense.category || 'Other').replace(/,/g, ' ');
          const description = (expense.description || 'N/A').replace(/,/g, ' ');
          csvContent += `${new Date(expense.date).toLocaleDateString()},${category},${description},${parseFloat(expense.amount || 0).toFixed(2)}\n`;
        });

        csvContent += `\nTotal Expenses,${filteredExpenses.length}\n`;
        csvContent += `Total Amount,${totalExpenses.toFixed(2)}\n`;
      } else if (selectedExportType === 'due') {
        // Export Due/Udhari Report
        csvContent = `Due / Udhari Report\n`;
        csvContent += `Generated: ${now.toLocaleDateString()} ${now.toLocaleTimeString()}\n\n`;
        csvContent += `Customer,Phone,Invoice,Date,Total,Paid,Remaining,Status\n`;

        const safeBills = bills || [];
        const dueBills = safeBills.filter(b => {
          const mode = (b.payment_mode || b.paymentMode || '').toLowerCase();
          return mode === 'due';
        });

        let grandTotal = 0, grandPaid = 0, grandRemaining = 0;
        dueBills.forEach(bill => {
          const customer = (bill.customer_name || bill.customerName || 'Customer').replace(/,/g, ' ');
          const phone = bill.customer_mobile || bill.customerMobile || 'N/A';
          const invoice = bill.invoice_number || bill.invoiceNumber || bill.id;
          const total = parseFloat(bill.grand_total || bill.grandTotal || bill.total) || 0;
          const paid = parseFloat(bill.paid_amount) || 0;
          const remaining = total - paid;
          const status = bill.due_status === 'paid' ? 'Paid' : bill.due_status === 'partial' ? 'Partial' : 'Unpaid';
          grandTotal += total;
          grandPaid += paid;
          grandRemaining += remaining;
          csvContent += `${customer},${phone},${invoice},${new Date(bill.date).toLocaleDateString()},${total.toFixed(2)},${paid.toFixed(2)},${remaining.toFixed(2)},${status}\n`;
        });

        csvContent += `\nTotal Due Bills,${dueBills.length}\n`;
        csvContent += `Total Amount,${grandTotal.toFixed(2)}\n`;
        csvContent += `Total Paid,${grandPaid.toFixed(2)}\n`;
        csvContent += `Total Remaining,${grandRemaining.toFixed(2)}\n`;
        csvContent += `Pending Bills,${dueBills.filter(b => b.due_status !== 'paid').length}\n`;
      }

      // Save and share CSV file
      const fileName = `${selectedExportType}_report_${selectedPeriod}_${Date.now()}.csv`;
      const fileUri = FileSystem.documentDirectory + fileName;

      await FileSystem.writeAsStringAsync(fileUri, csvContent, {
        encoding: 'utf8',
      });

      if (await Sharing.isAvailableAsync()) {
        await Sharing.shareAsync(fileUri, {
          mimeType: 'text/csv',
          dialogTitle: 'Export Report',
        });
        Alert.alert('Success', 'Report exported successfully!');
      } else {
        Alert.alert('Error', 'Sharing is not available on this device');
      }
    } catch (error) {
      // silent
      Alert.alert('Error', `Export failed: ${error.message}`);
    }
  };

  const handlePDFExport = async (type) => {
    try {
      const now = new Date();
      const periodLabel = selectedPeriod.charAt(0).toUpperCase() + selectedPeriod.slice(1);
      let htmlContent = '';

      if (type === 'summary') {
        // Summary Report PDF
        const chartRows = chartData.map(item => `
          <tr>
            <td style="padding: 8px; border-bottom: 1px solid #E2E8F0;">${item.label}</td>
            <td style="padding: 8px; border-bottom: 1px solid #E2E8F0; text-align: right;">₹${item.amount.toFixed(2)}</td>
          </tr>
        `).join('');

        htmlContent = `
          <!DOCTYPE html>
          <html>
            <head>
              <meta name="viewport" content="width=device-width, initial-scale=1.0">
              <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .title { font-size: 24px; font-weight: bold; color: #2563EB; }
                .subtitle { font-size: 14px; color: #64748B; margin-top: 5px; }
                .section { margin: 20px 0; }
                .section-title { font-size: 18px; font-weight: bold; color: #1E293B; margin-bottom: 10px; }
                table { width: 100%; border-collapse: collapse; margin: 10px 0; }
                th { background-color: #2563EB; color: white; padding: 10px; text-align: left; }
                .summary-box { background-color: #F8FAFC; padding: 15px; border-radius: 8px; margin: 10px 0; }
                .summary-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #E2E8F0; }
                .summary-label { font-weight: 600; color: #64748B; }
                .summary-value { font-weight: bold; color: #1E293B; }
                .profit { color: #10B981; }
                .loss { color: #EF4444; }
              </style>
            </head>
            <body>
              <div class="header">
                <div class="title">Business Summary Report</div>
                <div class="subtitle">Period: ${periodLabel} | Generated: ${now.toLocaleDateString()} ${now.toLocaleTimeString()}</div>
              </div>

              <div class="section">
                <div class="section-title">Financial Summary</div>
                <div class="summary-box">
                  <div class="summary-row">
                    <span class="summary-label">Total Sales</span>
                    <span class="summary-value">₹${totalSales.toFixed(2)}</span>
                  </div>
                  <div class="summary-row">
                    <span class="summary-label">Total Expenses</span>
                    <span class="summary-value">₹${totalExpenses.toFixed(2)}</span>
                  </div>
                  <div class="summary-row">
                    <span class="summary-label">Net Profit/Loss</span>
                    <span class="summary-value ${profit >= 0 ? 'profit' : 'loss'}">₹${profit.toFixed(2)}</span>
                  </div>
                </div>
              </div>

              <div class="section">
                <div class="section-title">Sales Breakdown</div>
                <table>
                  <thead>
                    <tr>
                      <th>Period</th>
                      <th style="text-align: right;">Amount</th>
                    </tr>
                  </thead>
                  <tbody>
                    ${chartRows}
                  </tbody>
                </table>
              </div>
            </body>
          </html>
        `;
      } else if (type === 'detailed') {
        // Detailed Bills PDF
        const filteredBills = bills.filter(bill => {
          const billDate = new Date(bill.date);
          if (selectedPeriod === 'daily') {
            return billDate.toDateString() === now.toDateString();
          } else if (selectedPeriod === 'monthly') {
            return billDate.getMonth() === now.getMonth() && billDate.getFullYear() === now.getFullYear();
          } else {
            return billDate.getFullYear() === now.getFullYear();
          }
        });

        const billRows = filteredBills.map(bill => {
          const customerName = bill.customer_name || bill.customerName || 'Customer';
          const amount = bill.grand_total || bill.grandTotal || bill.total || 0;
          const paymentMethod = bill.payment_method || bill.paymentMethod || 'Cash';
          return `
            <tr>
              <td style="padding: 8px; border-bottom: 1px solid #E2E8F0;">${bill.invoiceNumber || bill.id}</td>
              <td style="padding: 8px; border-bottom: 1px solid #E2E8F0;">${customerName}</td>
              <td style="padding: 8px; border-bottom: 1px solid #E2E8F0;">${new Date(bill.date).toLocaleDateString()}</td>
              <td style="padding: 8px; border-bottom: 1px solid #E2E8F0; text-align: right;">₹${parseFloat(amount).toFixed(2)}</td>
              <td style="padding: 8px; border-bottom: 1px solid #E2E8F0;">${paymentMethod}</td>
            </tr>
          `;
        }).join('');

        const totalAmount = filteredBills.reduce((sum, bill) => sum + parseFloat(bill.grand_total || bill.grandTotal || bill.total || 0), 0);

        htmlContent = `
          <!DOCTYPE html>
          <html>
            <head>
              <meta name="viewport" content="width=device-width, initial-scale=1.0">
              <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .title { font-size: 24px; font-weight: bold; color: #2563EB; }
                .subtitle { font-size: 14px; color: #64748B; margin-top: 5px; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th { background-color: #2563EB; color: white; padding: 10px; text-align: left; }
                .total-row { background-color: #F8FAFC; font-weight: bold; }
              </style>
            </head>
            <body>
              <div class="header">
                <div class="title">Detailed Bills Report</div>
                <div class="subtitle">Period: ${periodLabel} | Generated: ${now.toLocaleDateString()} ${now.toLocaleTimeString()}</div>
              </div>

              <table>
                <thead>
                  <tr>
                    <th>Invoice No</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th style="text-align: right;">Amount</th>
                    <th>Payment</th>
                  </tr>
                </thead>
                <tbody>
                  ${billRows}
                  <tr class="total-row">
                    <td colspan="3" style="padding: 12px;">Total Bills: ${filteredBills.length}</td>
                    <td style="padding: 12px; text-align: right;">₹${totalAmount.toFixed(2)}</td>
                    <td style="padding: 12px;"></td>
                  </tr>
                </tbody>
              </table>
            </body>
          </html>
        `;
      } else if (type === 'expenses') {
        // Expenses Report PDF
        const filteredExpenses = expenses.filter(expense => {
          const expenseDate = new Date(expense.date);
          if (selectedPeriod === 'daily') {
            return expenseDate.toDateString() === now.toDateString();
          } else if (selectedPeriod === 'monthly') {
            return expenseDate.getMonth() === now.getMonth() && expenseDate.getFullYear() === now.getFullYear();
          } else {
            return expenseDate.getFullYear() === now.getFullYear();
          }
        });

        const expenseRows = filteredExpenses.map(expense => `
          <tr>
            <td style="padding: 8px; border-bottom: 1px solid #E2E8F0;">${new Date(expense.date).toLocaleDateString()}</td>
            <td style="padding: 8px; border-bottom: 1px solid #E2E8F0;">${expense.category || 'Other'}</td>
            <td style="padding: 8px; border-bottom: 1px solid #E2E8F0;">${expense.description || 'N/A'}</td>
            <td style="padding: 8px; border-bottom: 1px solid #E2E8F0; text-align: right;">₹${parseFloat(expense.amount || 0).toFixed(2)}</td>
          </tr>
        `).join('');

        htmlContent = `
          <!DOCTYPE html>
          <html>
            <head>
              <meta name="viewport" content="width=device-width, initial-scale=1.0">
              <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .title { font-size: 24px; font-weight: bold; color: #2563EB; }
                .subtitle { font-size: 14px; color: #64748B; margin-top: 5px; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th { background-color: #2563EB; color: white; padding: 10px; text-align: left; }
                .total-row { background-color: #F8FAFC; font-weight: bold; }
              </style>
            </head>
            <body>
              <div class="header">
                <div class="title">Expenses Report</div>
                <div class="subtitle">Period: ${periodLabel} | Generated: ${now.toLocaleDateString()} ${now.toLocaleTimeString()}</div>
              </div>

              <table>
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th style="text-align: right;">Amount</th>
                  </tr>
                </thead>
                <tbody>
                  ${expenseRows}
                  <tr class="total-row">
                    <td colspan="3" style="padding: 12px;">Total Expenses: ${filteredExpenses.length}</td>
                    <td style="padding: 12px; text-align: right;">₹${totalExpenses.toFixed(2)}</td>
                  </tr>
                </tbody>
              </table>
            </body>
          </html>
        `;
      } else if (type === 'due') {
        // Due/Udhari Report PDF
        const safeBills = bills || [];
        const dueBills = safeBills.filter(b => {
          const mode = (b.payment_mode || b.paymentMode || '').toLowerCase();
          return mode === 'due';
        });

        let grandTotal = 0, grandPaid = 0, grandRemaining = 0;
        const dueRows = dueBills.map(bill => {
          const customer = bill.customer_name || bill.customerName || 'Customer';
          const phone = bill.customer_mobile || bill.customerMobile || 'N/A';
          const invoice = bill.invoice_number || bill.invoiceNumber || bill.id;
          const total = parseFloat(bill.grand_total || bill.grandTotal || bill.total) || 0;
          const paid = parseFloat(bill.paid_amount) || 0;
          const remaining = total - paid;
          const status = bill.due_status === 'paid' ? 'Paid' : bill.due_status === 'partial' ? 'Partial' : 'Unpaid';
          const statusColor = status === 'Paid' ? '#10B981' : status === 'Partial' ? '#F59E0B' : '#DC2626';
          grandTotal += total;
          grandPaid += paid;
          grandRemaining += remaining;
          return `
            <tr>
              <td style="padding: 8px; border-bottom: 1px solid #E2E8F0;">${customer}</td>
              <td style="padding: 8px; border-bottom: 1px solid #E2E8F0;">${phone}</td>
              <td style="padding: 8px; border-bottom: 1px solid #E2E8F0;">#${invoice}</td>
              <td style="padding: 8px; border-bottom: 1px solid #E2E8F0;">${new Date(bill.date).toLocaleDateString()}</td>
              <td style="padding: 8px; border-bottom: 1px solid #E2E8F0; text-align: right;">₹${total.toFixed(2)}</td>
              <td style="padding: 8px; border-bottom: 1px solid #E2E8F0; text-align: right; color: #10B981;">₹${paid.toFixed(2)}</td>
              <td style="padding: 8px; border-bottom: 1px solid #E2E8F0; text-align: right; color: #DC2626; font-weight: bold;">₹${remaining.toFixed(2)}</td>
              <td style="padding: 8px; border-bottom: 1px solid #E2E8F0; text-align: center;"><span style="background: ${statusColor}20; color: ${statusColor}; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: bold;">${status}</span></td>
            </tr>
          `;
        }).join('');

        htmlContent = `
          <!DOCTYPE html>
          <html>
            <head>
              <meta name="viewport" content="width=device-width, initial-scale=1.0">
              <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .title { font-size: 24px; font-weight: bold; color: #DC2626; }
                .subtitle { font-size: 14px; color: #64748B; margin-top: 5px; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th { background-color: #DC2626; color: white; padding: 10px; text-align: left; }
                .total-row { background-color: #F8FAFC; font-weight: bold; }
                .summary-box { display: flex; justify-content: space-around; margin: 20px 0; }
                .summary-item { text-align: center; padding: 15px; background: #F8FAFC; border-radius: 8px; flex: 1; margin: 0 5px; }
                .summary-value { font-size: 20px; font-weight: bold; }
                .summary-label { font-size: 12px; color: #64748B; margin-top: 5px; }
              </style>
            </head>
            <body>
              <div class="header">
                <div class="title">Due / Udhari Report</div>
                <div class="subtitle">Generated: ${now.toLocaleDateString()} ${now.toLocaleTimeString()}</div>
              </div>

              <div class="summary-box">
                <div class="summary-item">
                  <div class="summary-value" style="color: #DC2626;">₹${grandRemaining.toFixed(2)}</div>
                  <div class="summary-label">Pending</div>
                </div>
                <div class="summary-item">
                  <div class="summary-value" style="color: #10B981;">₹${grandPaid.toFixed(2)}</div>
                  <div class="summary-label">Received</div>
                </div>
                <div class="summary-item">
                  <div class="summary-value">₹${grandTotal.toFixed(2)}</div>
                  <div class="summary-label">Total Due</div>
                </div>
              </div>

              <table>
                <thead>
                  <tr>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th>Invoice</th>
                    <th>Date</th>
                    <th style="text-align: right;">Total</th>
                    <th style="text-align: right;">Paid</th>
                    <th style="text-align: right;">Remaining</th>
                    <th style="text-align: center;">Status</th>
                  </tr>
                </thead>
                <tbody>
                  ${dueRows}
                  <tr class="total-row">
                    <td colspan="4" style="padding: 12px;">Total: ${dueBills.length} bills (${dueBills.filter(b => b.due_status !== 'paid').length} pending)</td>
                    <td style="padding: 12px; text-align: right;">₹${grandTotal.toFixed(2)}</td>
                    <td style="padding: 12px; text-align: right; color: #10B981;">₹${grandPaid.toFixed(2)}</td>
                    <td style="padding: 12px; text-align: right; color: #DC2626;">₹${grandRemaining.toFixed(2)}</td>
                    <td></td>
                  </tr>
                </tbody>
              </table>

              <div style="text-align: center; margin-top: 30px; color: #64748B; font-size: 12px;">Generated by BINEST App</div>
            </body>
          </html>
        `;
      }

      const { uri } = await Print.printToFileAsync({ html: htmlContent });

      if (await Sharing.isAvailableAsync()) {
        await Sharing.shareAsync(uri, {
          mimeType: 'application/pdf',
          dialogTitle: 'Export Report PDF',
        });
        Alert.alert('Success', 'PDF report exported successfully!');
      } else {
        Alert.alert('Error', 'Sharing is not available on this device');
      }
    } catch (error) {
      // silent
      Alert.alert('Error', `PDF export failed: ${error.message}`);
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      <LinearGradient
        colors={['#E0E7FF', '#F8FAFC']}
        style={styles.header}
      >
        <View style={styles.headerTop}>
          <View style={styles.headerLeft}>
            <Ionicons name="bar-chart" size={24} color="#1E293B" />
            <Text style={styles.headerTitle}>{t('reports')}</Text>
          </View>
          <TouchableOpacity onPress={() => setShowExportModal(true)} style={styles.exportHeaderButton}>
            <Ionicons name="download-outline" size={24} color="#2563EB" />
          </TouchableOpacity>
        </View>
      </LinearGradient>

      <View style={styles.periodSelector}>
        {['daily', 'monthly', 'yearly'].map((period) => (
          <TouchableOpacity
            key={period}
            style={[
              styles.periodButton,
              selectedPeriod === period && styles.periodButtonActive
            ]}
            onPress={() => setSelectedPeriod(period)}
          >
            <Text style={[
              styles.periodButtonText,
              selectedPeriod === period && styles.periodButtonTextActive
            ]}>
              {period.charAt(0).toUpperCase() + period.slice(1)}
            </Text>
          </TouchableOpacity>
        ))}
      </View>

      <View style={styles.searchSection}>
        <View style={styles.searchContainer}>
          <Ionicons name="search" size={20} color="#64748B" />
          <TextInput
            style={styles.searchInput}
            placeholder="Search Invoice by Number..."
            value={invoiceSearch}
            onChangeText={setInvoiceSearch}
            keyboardType="numeric"
          />
          {invoiceSearch.length > 0 && (
            <TouchableOpacity onPress={() => setInvoiceSearch('')}>
              <Ionicons name="close-circle" size={20} color="#94A3B8" />
            </TouchableOpacity>
          )}
        </View>
        <TouchableOpacity style={styles.searchButton} onPress={searchInvoice}>
          <Ionicons name="search" size={20} color="#FFFFFF" />
        </TouchableOpacity>
      </View>

      <ScrollView style={styles.content}>
        <View style={styles.salesOverview}>
          <View style={styles.overviewHeader}>
            <Text style={styles.chartTitle}>Sales Overview</Text>
            <Text style={styles.chartSubtitle}>
              {selectedPeriod === 'daily' ? 'Last 7 Days' :
                selectedPeriod === 'monthly' ? 'This Month (Date-wise)' :
                  'This Year (Month-wise)'}
            </Text>
          </View>
          <ScrollView horizontal showsHorizontalScrollIndicator={false}>
            <View style={styles.salesChart}>
              {chartData.map((data, index) => (
                <View key={index} style={styles.chartBar}>
                  <View style={styles.chartBarContainer}>
                    <View
                      style={[
                        styles.chartBarFill,
                        {
                          height: `${data.percentage}%`,
                          backgroundColor: data.amount > 0 ? '#2563EB' : '#E2E8F0'
                        }
                      ]}
                    />
                  </View>
                  <Text style={styles.barLabel}>{data.label}</Text>
                  {data.amount > 0 && (
                    <Text style={styles.barAmount}>
                      ₹{data.amount >= 1000 ? `${(data.amount / 1000).toFixed(1)}k` : data.amount.toFixed(0)}
                    </Text>
                  )}
                </View>
              ))}
            </View>
          </ScrollView>
        </View>

        <View style={styles.statsGrid}>
          <View style={styles.statCard}>
            <View style={styles.statHeader}>
              <Text style={styles.statLabel}>{t('totalSales')}</Text>
              <View style={[styles.statIcon, { backgroundColor: '#DBEAFE' }]}>
                <Ionicons name="trending-up" size={16} color="#2563EB" />
              </View>
            </View>
            <Text style={styles.statValue}>₹{(totalSales || 0).toLocaleString()}</Text>
          </View>

          <View style={styles.statCard}>
            <View style={styles.statHeader}>
              <Text style={styles.statLabel}>Total Expenses</Text>
              <View style={[styles.statIcon, { backgroundColor: '#FEF3C7' }]}>
                <Ionicons name="trending-down" size={16} color="#F59E0B" />
              </View>
            </View>
            <Text style={styles.statValue}>₹{(totalExpenses || 0).toLocaleString()}</Text>
          </View>

          <View style={styles.statCard}>
            <View style={styles.statHeader}>
              <Text style={styles.statLabel}>{t('profit')}</Text>
              <View style={[styles.statIcon, { backgroundColor: '#D1FAE5' }]}>
                <Ionicons name="cash" size={16} color="#10B981" />
              </View>
            </View>
            <Text style={[styles.statValue, { color: (profit || 0) >= 0 ? '#10B981' : '#EF4444' }]}>
              ₹{(profit || 0).toLocaleString()}
            </Text>
          </View>

          <View style={[styles.statCard, { borderLeftWidth: 3, borderLeftColor: '#DC2626' }]}>
            <View style={styles.statHeader}>
              <Text style={styles.statLabel}>Total Due</Text>
              <View style={[styles.statIcon, { backgroundColor: '#FEE2E2' }]}>
                <Ionicons name="time" size={16} color="#DC2626" />
              </View>
            </View>
            <Text style={[styles.statValue, { color: '#DC2626' }]}>
              ₹{(dueStats.totalDue || 0).toLocaleString()}
            </Text>
            <Text style={{ fontSize: 11, color: '#94A3B8', marginTop: 2 }}>{dueStats.count} pending</Text>
          </View>
        </View>

        <View style={styles.section}>
          <View style={styles.sectionHeader}>
            <Text style={styles.sectionTitle}>Top Products</Text>
            <TouchableOpacity>
              <Ionicons name="chevron-forward" size={20} color="#64748B" />
            </TouchableOpacity>
          </View>

          {products.length > 0 ? (
            <View style={styles.productList}>
              {products.slice(0, 5).map((product, index) => (
                <View key={product.id} style={styles.productCard}>
                  <View style={styles.productIcon}>
                    <View style={[styles.productIconCircle,
                    { backgroundColor: index % 3 === 0 ? '#DBEAFE' : index % 3 === 1 ? '#FEE2E2' : '#FEF3C7' }
                    ]}>
                      <Ionicons
                        name="cube"
                        size={20}
                        color={index % 3 === 0 ? '#2563EB' : index % 3 === 1 ? '#EF4444' : '#F59E0B'}
                      />
                    </View>
                  </View>
                  <View style={styles.productInfo}>
                    <Text style={styles.productName}>{product.name}</Text>
                    <Text style={styles.productCategory}>{product.category || 'Product'} • Stock: {product.stock}</Text>
                  </View>
                  <Text style={styles.productAmount}>₹{product.price.toLocaleString()}</Text>
                </View>
              ))}
            </View>
          ) : (
            <View style={styles.emptyState}>
              <Ionicons name="cube-outline" size={48} color="#CBD5E1" />
              <Text style={styles.emptyText}>No products yet</Text>
            </View>
          )}
        </View>

        <View style={styles.section}>
          <View style={styles.sectionHeader}>
            <Text style={styles.sectionTitle}>Recent Bills</Text>
          </View>

          {bills.length > 0 ? (
            <View style={styles.customerList}>
              {bills.slice(0, 5).map((bill, index) => (
                <View key={bill.id || index} style={styles.customerCard}>
                  <View style={styles.customerAvatar}>
                    <Text style={styles.customerInitial}>{(bill.customer_name || bill.customerName || 'C').charAt(0)}</Text>
                  </View>
                  <View style={styles.customerInfo}>
                    <Text style={styles.customerName}>{bill.customer_name || bill.customerName || 'Customer'}</Text>
                    <Text style={styles.customerCompany}>Invoice #{bill.invoice_number || bill.invoiceNumber || (bill.id ? String(bill.id).slice(-6) : 'N/A')} • {new Date(bill.date).toLocaleDateString()}</Text>
                  </View>
                  <Text style={styles.customerAmount}>₹{(parseFloat(bill.grand_total || bill.grandTotal || bill.total || 0) || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</Text>
                </View>
              ))}
            </View>
          ) : (
            <View style={styles.emptyState}>
              <Ionicons name="receipt-outline" size={48} color="#CBD5E1" />
              <Text style={styles.emptyText}>No bills yet</Text>
            </View>
          )}
        </View>
      </ScrollView>

      {/* Export Options Modal */}
      <Modal
        visible={showExportModal}
        transparent={true}
        animationType="fade"
        onRequestClose={() => setShowExportModal(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.exportModal}>
            <View style={styles.exportModalHeader}>
              <Text style={styles.exportModalTitle}>Export Report</Text>
              <TouchableOpacity onPress={() => setShowExportModal(false)}>
                <Ionicons name="close" size={24} color="#64748B" />
              </TouchableOpacity>
            </View>

            <Text style={styles.exportModalSubtitle}>
              Select report type to export
            </Text>

            <View style={styles.exportOptions}>
              <TouchableOpacity
                style={styles.exportOptionCard}
                onPress={() => handleExportTypeSelect('summary')}
              >
                <View style={[styles.exportOptionIcon, { backgroundColor: '#DBEAFE' }]}>
                  <Ionicons name="stats-chart" size={24} color="#2563EB" />
                </View>
                <View style={styles.exportOptionContent}>
                  <Text style={styles.exportOptionTitle}>Summary Report</Text>
                  <Text style={styles.exportOptionDesc}>Sales, expenses & profit summary</Text>
                </View>
                <Ionicons name="chevron-forward" size={20} color="#94A3B8" />
              </TouchableOpacity>

              <TouchableOpacity
                style={styles.exportOptionCard}
                onPress={() => handleExportTypeSelect('detailed')}
              >
                <View style={[styles.exportOptionIcon, { backgroundColor: '#D1FAE5' }]}>
                  <Ionicons name="receipt" size={24} color="#10B981" />
                </View>
                <View style={styles.exportOptionContent}>
                  <Text style={styles.exportOptionTitle}>Detailed Bills</Text>
                  <Text style={styles.exportOptionDesc}>All bills with customer details</Text>
                </View>
                <Ionicons name="chevron-forward" size={20} color="#94A3B8" />
              </TouchableOpacity>

              <TouchableOpacity
                style={styles.exportOptionCard}
                onPress={() => handleExportTypeSelect('expenses')}
              >
                <View style={[styles.exportOptionIcon, { backgroundColor: '#FEF3C7' }]}>
                  <Ionicons name="wallet" size={24} color="#F59E0B" />
                </View>
                <View style={styles.exportOptionContent}>
                  <Text style={styles.exportOptionTitle}>Expenses Report</Text>
                  <Text style={styles.exportOptionDesc}>All expenses by category</Text>
                </View>
                <Ionicons name="chevron-forward" size={20} color="#94A3B8" />
              </TouchableOpacity>

              <TouchableOpacity
                style={styles.exportOptionCard}
                onPress={() => handleExportTypeSelect('due')}
              >
                <View style={[styles.exportOptionIcon, { backgroundColor: '#FEE2E2' }]}>
                  <Ionicons name="time" size={24} color="#DC2626" />
                </View>
                <View style={styles.exportOptionContent}>
                  <Text style={styles.exportOptionTitle}>Due / Udhari Report</Text>
                  <Text style={styles.exportOptionDesc}>All pending dues by customer</Text>
                </View>
                <Ionicons name="chevron-forward" size={20} color="#94A3B8" />
              </TouchableOpacity>
            </View>

            <Text style={styles.exportModalNote}>
              Period: {selectedPeriod.charAt(0).toUpperCase() + selectedPeriod.slice(1)}
            </Text>
          </View>
        </View>
      </Modal>

      {/* Format Selection Modal */}
      <Modal
        visible={showFormatModal}
        transparent={true}
        animationType="fade"
        onRequestClose={() => setShowFormatModal(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.exportModal}>
            <View style={styles.exportModalHeader}>
              <Text style={styles.exportModalTitle}>Select Format</Text>
              <TouchableOpacity onPress={() => setShowFormatModal(false)}>
                <Ionicons name="close" size={24} color="#64748B" />
              </TouchableOpacity>
            </View>

            <Text style={styles.exportModalSubtitle}>
              Choose export format
            </Text>

            <View style={styles.exportOptions}>
              <TouchableOpacity
                style={styles.exportOptionCard}
                onPress={() => handleExport('csv')}
              >
                <View style={[styles.exportOptionIcon, { backgroundColor: '#D1FAE5' }]}>
                  <Ionicons name="document-text" size={24} color="#10B981" />
                </View>
                <View style={styles.exportOptionContent}>
                  <Text style={styles.exportOptionTitle}>CSV Format</Text>
                  <Text style={styles.exportOptionDesc}>Excel/Sheets compatible</Text>
                </View>
                <Ionicons name="chevron-forward" size={20} color="#94A3B8" />
              </TouchableOpacity>

              <TouchableOpacity
                style={styles.exportOptionCard}
                onPress={() => handleExport('pdf')}
              >
                <View style={[styles.exportOptionIcon, { backgroundColor: '#FEE2E2' }]}>
                  <Ionicons name="document" size={24} color="#EF4444" />
                </View>
                <View style={styles.exportOptionContent}>
                  <Text style={styles.exportOptionTitle}>PDF Format</Text>
                  <Text style={styles.exportOptionDesc}>Professional document</Text>
                </View>
                <Ionicons name="chevron-forward" size={20} color="#94A3B8" />
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
    paddingHorizontal: 20,
    paddingVertical: 16,
  },
  headerTop: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  headerLeft: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  headerTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#1E293B',
    marginLeft: 12,
  },
  periodSelector: {
    flexDirection: 'row',
    padding: 20,
    justifyContent: 'space-around',
  },
  periodButton: {
    paddingHorizontal: 24,
    paddingVertical: 10,
    borderRadius: 20,
    backgroundColor: '#F1F5F9',
  },
  periodButtonActive: {
    backgroundColor: '#2563EB',
  },
  periodButtonText: {
    fontSize: 14,
    color: '#64748B',
    fontWeight: '600',
  },
  periodButtonTextActive: {
    color: '#FFFFFF',
  },
  content: {
    flex: 1,
    padding: 20,
  },
  salesOverview: {
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    padding: 20,
    marginBottom: 20,
  },
  overviewHeader: {
    marginBottom: 16,
  },
  chartCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    padding: 20,
    marginBottom: 20,
  },
  chartHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 20,
  },
  chartTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#1E293B',
  },
  chartSubtitle: {
    fontSize: 12,
    color: '#64748B',
  },
  salesChart: {
    flexDirection: 'row',
    justifyContent: 'space-around',
    alignItems: 'flex-end',
    height: 180,
    paddingTop: 20,
    minWidth: '100%',
  },
  chartBar: {
    flex: 1,
    minWidth: 40,
    alignItems: 'center',
    marginHorizontal: 6,
  },
  chartBarContainer: {
    width: '100%',
    height: 120,
    justifyContent: 'flex-end',
    alignItems: 'center',
  },
  chartBarFill: {
    width: '50%',
    borderRadius: 6,
    minHeight: 4,
  },
  barLabel: {
    fontSize: 11,
    color: '#64748B',
    marginTop: 8,
    fontWeight: '500',
  },
  barAmount: {
    fontSize: 10,
    color: '#2563EB',
    fontWeight: 'bold',
    marginTop: 4,
  },
  chartLegend: {
    flexDirection: 'row',
  },
  legendItem: {
    flexDirection: 'row',
    alignItems: 'center',
    marginLeft: 16,
  },
  legendDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    marginRight: 6,
  },
  legendText: {
    fontSize: 12,
    color: '#64748B',
  },
  chart: {
    height: 200,
    position: 'relative',
  },
  barContainer: {
    flexDirection: 'row',
    justifyContent: 'space-around',
    alignItems: 'flex-end',
    height: '100%',
    paddingBottom: 20,
  },
  bar: {
    width: 40,
    height: '100%',
    justifyContent: 'flex-end',
  },
  barFill: {
    width: '100%',
    borderRadius: 8,
  },
  trendLine: {
    position: 'absolute',
    right: 20,
    top: 20,
  },
  statsGrid: {
    marginBottom: 20,
  },
  statCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    padding: 16,
    marginBottom: 12,
  },
  statHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  statLabel: {
    fontSize: 14,
    color: '#64748B',
  },
  statIcon: {
    width: 32,
    height: 32,
    borderRadius: 16,
    justifyContent: 'center',
    alignItems: 'center',
  },
  statValue: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#1E293B',
  },
  section: {
    marginBottom: 20,
  },
  sectionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#1E293B',
  },
  exportButtons: {
    flexDirection: 'row',
  },
  exportButton: {
    backgroundColor: '#F1F5F9',
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 6,
    marginLeft: 8,
  },
  exportButtonText: {
    fontSize: 12,
    color: '#64748B',
    fontWeight: '600',
  },
  productList: {
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    padding: 16,
  },
  productCard: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: '#F1F5F9',
  },
  productIcon: {
    marginRight: 12,
  },
  productIconCircle: {
    width: 40,
    height: 40,
    borderRadius: 20,
    justifyContent: 'center',
    alignItems: 'center',
  },
  productInfo: {
    flex: 1,
  },
  productName: {
    fontSize: 14,
    fontWeight: '600',
    color: '#1E293B',
    marginBottom: 2,
  },
  productCategory: {
    fontSize: 12,
    color: '#64748B',
  },
  productAmount: {
    fontSize: 14,
    fontWeight: 'bold',
    color: '#1E293B',
  },
  customerList: {
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    padding: 16,
  },
  customerCard: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 12,
  },
  customerAvatar: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: '#2563EB',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 12,
  },
  customerInitial: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  customerInfo: {
    flex: 1,
  },
  customerName: {
    fontSize: 14,
    fontWeight: '600',
    color: '#1E293B',
    marginBottom: 2,
  },
  customerCompany: {
    fontSize: 12,
    color: '#64748B',
  },
  customerAmount: {
    fontSize: 14,
    fontWeight: 'bold',
    color: '#10B981',
  },
  emptyState: {
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    padding: 40,
    alignItems: 'center',
  },
  emptyText: {
    fontSize: 14,
    color: '#94A3B8',
    marginTop: 12,
  },
  searchSection: {
    flexDirection: 'row',
    paddingHorizontal: 20,
    paddingVertical: 12,
    alignItems: 'center',
  },
  searchContainer: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    paddingHorizontal: 12,
    paddingVertical: 10,
    marginRight: 8,
  },
  searchInput: {
    flex: 1,
    marginLeft: 8,
    fontSize: 14,
    color: '#1E293B',
  },
  searchButton: {
    backgroundColor: '#2563EB',
    width: 44,
    height: 44,
    borderRadius: 22,
    justifyContent: 'center',
    alignItems: 'center',
  },
  reportTypeSection: {
    paddingHorizontal: 20,
    paddingVertical: 12,
  },
  sectionLabel: {
    fontSize: 14,
    fontWeight: '600',
    color: '#475569',
    marginBottom: 12,
  },
  reportTypeSelector: {
    flexDirection: 'row',
    gap: 8,
  },
  reportTypeButton: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#FFFFFF',
    paddingVertical: 12,
    paddingHorizontal: 8,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    gap: 6,
  },
  reportTypeButtonActive: {
    backgroundColor: '#2563EB',
    borderColor: '#2563EB',
  },
  reportTypeButtonText: {
    fontSize: 12,
    fontWeight: '600',
    color: '#64748B',
  },
  reportTypeButtonTextActive: {
    color: '#FFFFFF',
  },
  exportSection: {
    paddingHorizontal: 20,
    paddingVertical: 12,
  },
  exportButtons: {
    flexDirection: 'row',
    gap: 12,
  },
  exportButton: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#FFFFFF',
    paddingVertical: 14,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    gap: 8,
  },
  exportButtonText: {
    fontSize: 14,
    fontWeight: '600',
    color: '#1E293B',
  },
  exportHeaderButton: {
    padding: 8,
    backgroundColor: '#FFFFFF',
    borderRadius: 8,
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
  },
  exportModal: {
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    padding: 24,
    width: '100%',
    maxWidth: 400,
  },
  exportModalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  exportModalTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#1E293B',
  },
  exportModalSubtitle: {
    fontSize: 14,
    color: '#64748B',
    marginBottom: 20,
  },
  exportOptions: {
    gap: 12,
  },
  exportOptionCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#F8FAFC',
    padding: 16,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  exportOptionIcon: {
    width: 48,
    height: 48,
    borderRadius: 12,
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 12,
  },
  exportOptionContent: {
    flex: 1,
  },
  exportOptionTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#1E293B',
    marginBottom: 4,
  },
  exportOptionDesc: {
    fontSize: 13,
    color: '#64748B',
  },
  exportModalNote: {
    fontSize: 12,
    color: '#64748B',
    textAlign: 'center',
    marginTop: 16,
    fontStyle: 'italic',
  },
});

export default ReportsScreen;
