const express = require('express');
const router = express.Router();
const db = require('../database/db');

router.get('/:userId', async (req, res) => {
    try {
        const [expenses] = await db.query('SELECT * FROM expenses WHERE user_id = ? ORDER BY date DESC', [req.params.userId]);
        res.json({ success: true, expenses });
    } catch (error) {
        res.status(500).json({ success: false, error: error.message });
    }
});

router.post('/', async (req, res) => {
    try {
        const { userId, category, amount, description } = req.body;
        
        const [result] = await db.query(
            'INSERT INTO expenses (user_id, category, amount, description) VALUES (?, ?, ?, ?)',
            [userId, category, amount, description]
        );
        
        res.json({ success: true, expenseId: result.insertId });
    } catch (error) {
        res.status(500).json({ success: false, error: error.message });
    }
});

module.exports = router;
