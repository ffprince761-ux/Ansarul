const express = require('express');
const cors = require('cors');
const bodyParser = require('body-parser');
require('dotenv').config();

const app = express();
const PORT = process.env.PORT || 3000;

app.use(cors());
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));

const authRoutes = require('./routes/auth');
const productRoutes = require('./routes/products');
const customerRoutes = require('./routes/customers');
const saleRoutes = require('./routes/sales');
const expenseRoutes = require('./routes/expenses');
const billRoutes = require('./routes/bills');
const backupRoutes = require('./routes/backup');

app.use('/api/auth', authRoutes);
app.use('/api/products', productRoutes);
app.use('/api/customers', customerRoutes);
app.use('/api/sales', saleRoutes);
app.use('/api/expenses', expenseRoutes);
app.use('/api/bills', billRoutes);
app.use('/api/backup', backupRoutes);

app.get('/', (req, res) => {
    res.json({ message: 'Bizinote API Server Running' });
});

app.listen(PORT, () => {
    console.log(`Server running on port ${PORT}`);
    console.log(`API URL: http://localhost:${PORT}`);
});
