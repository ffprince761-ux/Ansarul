const express = require('express');
const router = express.Router();
const db = require('../database/db');

router.get('/:userId', async (req, res) => {
    try {
        const [customers] = await db.query('SELECT * FROM customers WHERE user_id = ?', [req.params.userId]);
        res.json({ success: true, customers });
    } catch (error) {
        res.status(500).json({ success: false, error: error.message });
    }
});

router.post('/', async (req, res) => {
    try {
        const { userId, name, mobile, email, address } = req.body;
        
        const [result] = await db.query(
            'INSERT INTO customers (user_id, name, mobile, email, address) VALUES (?, ?, ?, ?, ?)',
            [userId, name, mobile, email, address]
        );
        
        res.json({ success: true, customerId: result.insertId });
    } catch (error) {
        res.status(500).json({ success: false, error: error.message });
    }
});

router.put('/:id', async (req, res) => {
    try {
        const { name, mobile, email, address } = req.body;
        
        await db.query(
            'UPDATE customers SET name = ?, mobile = ?, email = ?, address = ? WHERE id = ?',
            [name, mobile, email, address, req.params.id]
        );
        
        res.json({ success: true });
    } catch (error) {
        res.status(500).json({ success: false, error: error.message });
    }
});

router.delete('/:id', async (req, res) => {
    try {
        await db.query('DELETE FROM customers WHERE id = ?', [req.params.id]);
        res.json({ success: true });
    } catch (error) {
        res.status(500).json({ success: false, error: error.message });
    }
});

module.exports = router;
