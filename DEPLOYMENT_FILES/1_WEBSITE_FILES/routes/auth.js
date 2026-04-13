const express = require('express');
const router = express.Router();
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const db = require('../database/db');

router.post('/register', async (req, res) => {
    try {
        const { businessName, mobile, email, password } = req.body;
        
        const hashedPassword = await bcrypt.hash(password, 10);
        
        const [result] = await db.query(
            'INSERT INTO users (business_name, mobile, email, password) VALUES (?, ?, ?, ?)',
            [businessName, mobile, email, hashedPassword]
        );
        
        const token = jwt.sign({ userId: result.insertId }, process.env.JWT_SECRET);
        
        res.json({ 
            success: true, 
            token, 
            user: { id: result.insertId, businessName, mobile, email }
        });
    } catch (error) {
        res.status(500).json({ success: false, error: error.message });
    }
});

router.post('/login', async (req, res) => {
    try {
        const { email, password } = req.body;
        
        const [users] = await db.query('SELECT * FROM users WHERE email = ?', [email]);
        
        if (users.length === 0) {
            return res.status(401).json({ success: false, error: 'Invalid credentials' });
        }
        
        const user = users[0];
        const validPassword = await bcrypt.compare(password, user.password);
        
        if (!validPassword) {
            return res.status(401).json({ success: false, error: 'Invalid credentials' });
        }
        
        const token = jwt.sign({ userId: user.id }, process.env.JWT_SECRET);
        
        res.json({ 
            success: true, 
            token,
            user: { 
                id: user.id, 
                businessName: user.business_name, 
                mobile: user.mobile, 
                email: user.email 
            }
        });
    } catch (error) {
        res.status(500).json({ success: false, error: error.message });
    }
});

module.exports = router;
