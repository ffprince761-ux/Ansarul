import { Share, Alert, Platform } from 'react-native';

/**
 * Simple export function using native Share API
 */
export const exportSimpleReport = async (reportType, bills, expenses, products) => {
  try {
    // Create simple text report
    const now = new Date();
    let reportText = `BINEST REPORT\n`;
    reportText += `=================\n\n`;
    reportText += `Report Type: ${reportType}\n`;
    reportText += `Date: ${now.toLocaleDateString()}\n`;
    reportText += `Time: ${now.toLocaleTimeString()}\n\n`;
    
    // Calculate totals
    const totalSales = bills.reduce((sum, bill) => sum + (bill.grandTotal || bill.total || 0), 0);
    const totalExpenses = expenses.reduce((sum, exp) => sum + (exp.amount || 0), 0);
    const profit = totalSales - totalExpenses;
    
    reportText += `SUMMARY\n`;
    reportText += `-------\n`;
    reportText += `Total Sales: Rs.${totalSales.toFixed(2)}\n`;
    reportText += `Total Expenses: Rs.${totalExpenses.toFixed(2)}\n`;
    reportText += `Net Profit: Rs.${profit.toFixed(2)}\n\n`;
    
    if (bills && bills.length > 0) {
      reportText += `SALES (${bills.length} bills)\n`;
      reportText += `-------\n`;
      bills.forEach((bill, index) => {
        reportText += `${index + 1}. ${bill.customerName} - Rs.${(bill.grandTotal || bill.total).toFixed(2)}\n`;
      });
      reportText += `\n`;
    }
    
    if (expenses && expenses.length > 0) {
      reportText += `EXPENSES (${expenses.length} items)\n`;
      reportText += `--------\n`;
      expenses.forEach((exp, index) => {
        reportText += `${index + 1}. ${exp.category} - Rs.${exp.amount.toFixed(2)}\n`;
      });
      reportText += `\n`;
    }
    
    // Use native Share API
    const result = await Share.share({
      message: reportText,
      title: `Binest ${reportType} Report`
    });
    
    if (result.action === Share.sharedAction) {
      return { success: true, message: 'Report shared successfully!' };
    } else if (result.action === Share.dismissedAction) {
      return { success: false, error: 'Share cancelled' };
    }
    
    return { success: true, message: 'Report ready to share' };
  } catch (error) {
    // silent
    return { success: false, error: error.message };
  }
};
