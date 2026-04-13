const express = require('express');
const router = express.Router();
const db = require('../database/db');

router.get('/:userId', async (req, res) => {
    try {
        const [products] = await db.query('SELECT * FROM products WHERE user_id = ?', [req.params.userId]);
        res.json({ success: true, products });
    } catch (error) {
        res.status(500).json({ success: false, error: error.message });
    }
});

router.post('/', async (req, res) => {
    try {
        const { userId, name, category, price, stock, description } = req.body;
        
        const [result] = await db.query(
            'INSERT INTO products (user_id, name, category, price, stock, description) VALUES (?, ?, ?, ?, ?, ?)',
            [userId, name, category, price, stock, description]
        );
        
        res.json({ success: true, productId: result.insertId });
    } catch (error) {
        res.status(500).json({ success: false, error: error.message });
    }
});

router.put('/:id', async (req, res) => {
    try {
        const { name, category, price, stock, description } = req.body;
        
        await db.query(
            'UPDATE products SET name = ?, category = ?, price = ?, stock = ?, description = ? WHERE id = ?',
            [name, category, price, stock, description, req.params.id]
        );
        
        res.json({ success: true });
    } catch (error) {
        res.status(500).json({ success: false, error: error.message });
    }
});

router.delete('/:id', async (req, res) => {
    try {
        await db.query('DELETE FROM products WHERE id = ?', [req.params.id]);
        res.json({ success: true });
    } catch (error) {
        res.status(500).json({ success: false, error: error.message });
    }
});

module.exports = router;
