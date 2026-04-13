const express = require('express');
const router = express.Router();
const db = require('../database/db');

// Create Backup - Export all user data to SQL
router.post('/create', async (req, res) => {
    try {
        const { userId } = req.body;
        
        // Get all user data
        const [products] = await db.query('SELECT * FROM products WHERE user_id = ?', [userId]);
        const [customers] = await db.query('SELECT * FROM customers WHERE user_id = ?', [userId]);
        const [sales] = await db.query('SELECT * FROM sales WHERE user_id = ?', [userId]);
        const [expenses] = await db.query('SELECT * FROM expenses WHERE user_id = ?', [userId]);
        const [bills] = await db.query('SELECT * FROM bills WHERE user_id = ?', [userId]);
        const [categories] = await db.query('SELECT * FROM categories WHERE user_id = ?', [userId]);
        
        // Get bill items for each bill
        for (let bill of bills) {
            const [items] = await db.query('SELECT * FROM bill_items WHERE bill_id = ?', [bill.id]);
            bill.items = items;
        }
        
        const backupData = {
            products,
            customers,
            sales,
            expenses,
            bills,
            categories,
            timestamp: new Date().toISOString()
        };
        
        // Save backup to database
        const [result] = await db.query(
            'INSERT INTO backups (user_id, backup_data) VALUES (?, ?)',
            [userId, JSON.stringify(backupData)]
        );
        
        res.json({ 
            success: true, 
            backupId: result.insertId,
            message: 'Backup created successfully',
            backupData 
        });
    } catch (error) {
        res.status(500).json({ success: false, error: error.message });
    }
});

// Restore Backup - Import data from SQL to app
router.post('/restore', async (req, res) => {
    try {
        const { userId, backupId } = req.body;
        
        // Get backup data
        const [backups] = await db.query(
            'SELECT * FROM backups WHERE id = ? AND user_id = ?',
            [backupId, userId]
        );
        
        if (backups.length === 0) {
            return res.status(404).json({ success: false, error: 'Backup not found' });
        }
        
        const backupData = JSON.parse(backups[0].backup_data);
        
        res.json({ 
            success: true, 
            message: 'Backup restored successfully',
            data: backupData 
        });
    } catch (error) {
        res.status(500).json({ success: false, error: error.message });
    }
});

// Get all backups for a user
router.get('/list/:userId', async (req, res) => {
    try {
        const [backups] = await db.query(
            'SELECT id, created_at FROM backups WHERE user_id = ? ORDER BY created_at DESC',
            [req.params.userId]
        );
        
        res.json({ success: true, backups });
    } catch (error) {
        res.status(500).json({ success: false, error: error.message });
    }
});

// Sync local data to server
router.post('/sync', async (req, res) => {
    try {
        const { userId, localData } = req.body;
        
        // Clear existing data
        await db.query('DELETE FROM products WHERE user_id = ?', [userId]);
        await db.query('DELETE FROM customers WHERE user_id = ?', [userId]);
        await db.query('DELETE FROM sales WHERE user_id = ?', [userId]);
        await db.query('DELETE FROM expenses WHERE user_id = ?', [userId]);
        await db.query('DELETE FROM categories WHERE user_id = ?', [userId]);
        
        // Insert products
        if (localData.products && localData.products.length > 0) {
            for (let product of localData.products) {
                await db.query(
                    'INSERT INTO products (user_id, name, category, price, stock, description) VALUES (?, ?, ?, ?, ?, ?)',
                    [userId, product.name, product.category, product.price, product.stock, product.description]
                );
            }
        }
        
        // Insert customers
        if (localData.customers && localData.customers.length > 0) {
            for (let customer of localData.customers) {
                await db.query(
                    'INSERT INTO customers (user_id, name, mobile, email, address) VALUES (?, ?, ?, ?, ?)',
                    [userId, customer.name, customer.mobile, customer.email, customer.address]
                );
            }
        }
        
        // Insert sales
        if (localData.sales && localData.sales.length > 0) {
            for (let sale of localData.sales) {
                await db.query(
                    'INSERT INTO sales (user_id, customer_name, amount, date) VALUES (?, ?, ?, ?)',
                    [userId, sale.customerName, sale.amount, sale.date]
                );
            }
        }
        
        // Insert expenses
        if (localData.expenses && localData.expenses.length > 0) {
            for (let expense of localData.expenses) {
                await db.query(
                    'INSERT INTO expenses (user_id, category, amount, description, date) VALUES (?, ?, ?, ?, ?)',
                    [userId, expense.category, expense.amount, expense.description, expense.date]
                );
            }
        }
        
        res.json({ 
            success: true, 
            message: 'Data synced successfully to server' 
        });
    } catch (error) {
        res.status(500).json({ success: false, error: error.message });
    }
});

module.exports = router;
