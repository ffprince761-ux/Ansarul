const express = require('express');
const router = express.Router();
const db = require('../database/db');

router.get('/:userId', async (req, res) => {
    try {
        const [sales] = await db.query('SELECT * FROM sales WHERE user_id = ? ORDER BY date DESC', [req.params.userId]);
        res.json({ success: true, sales });
    } catch (error) {
        res.status(500).json({ success: false, error: error.message });
    }
});

router.post('/', async (req, res) => {
    try {
        const { userId, customerId, customerName, amount } = req.body;
        
        const [result] = await db.query(
            'INSERT INTO sales (user_id, customer_id, customer_name, amount) VALUES (?, ?, ?, ?)',
            [userId, customerId, customerName, amount]
        );
        
        res.json({ success: true, saleId: result.insertId });
    } catch (error) {
        res.status(500).json({ success: false, error: error.message });
    }
});

module.exports = router;
