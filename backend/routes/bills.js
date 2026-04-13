const express = require('express');
const router = express.Router();
const db = require('../database/db');

router.get('/:userId', async (req, res) => {
    try {
        const [bills] = await db.query('SELECT * FROM bills WHERE user_id = ? ORDER BY date DESC', [req.params.userId]);
        
        for (let bill of bills) {
            const [items] = await db.query('SELECT * FROM bill_items WHERE bill_id = ?', [bill.id]);
            bill.items = items;
        }
        
        res.json({ success: true, bills });
    } catch (error) {
        res.status(500).json({ success: false, error: error.message });
    }
});

router.post('/', async (req, res) => {
    try {
        const { userId, customerId, customerName, items, discount, tax, total, paymentMode } = req.body;
        
        const [billResult] = await db.query(
            'INSERT INTO bills (user_id, customer_id, customer_name, total, discount, tax, payment_mode) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [userId, customerId, customerName, total, discount, tax, paymentMode]
        );
        
        const billId = billResult.insertId;
        
        for (let item of items) {
            await db.query(
                'INSERT INTO bill_items (bill_id, product_id, product_name, quantity, price) VALUES (?, ?, ?, ?, ?)',
                [billId, item.productId, item.name, item.quantity, item.price]
            );
            
            await db.query(
                'UPDATE products SET stock = stock - ? WHERE id = ?',
                [item.quantity, item.productId]
            );
        }
        
        res.json({ success: true, billId });
    } catch (error) {
        res.status(500).json({ success: false, error: error.message });
    }
});

module.exports = router;
