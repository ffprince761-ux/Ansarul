# BIZINOTE Backend API - SQL Database Setup

## 🗄️ Database Features

- **MySQL Database** - Complete SQL database integration
- **Backup & Restore** - Data backup aur restore functionality
- **Sync Feature** - Local data ko server pe sync karo
- **Multi-user Support** - Multiple users ka data separately store hota hai

## 📋 Setup Instructions

### Step 1: XAMPP Start Karein

1. **XAMPP Control Panel** open karein
2. **Apache** aur **MySQL** start karein
3. Dono services **green** honi chahiye

### Step 2: Database Create Karein

1. Browser mein jao: `http://localhost/phpmyadmin`
2. **New** button click karein
3. Database name: `bizinote_db`
4. **Create** button click karein

### Step 3: Tables Create Karein

**Option 1: SQL File Import**
```sql
1. PhpMyAdmin mein bizinote_db select karein
2. Import tab pe jao
3. File choose karein: backend/database/schema.sql
4. Go button click karein
```

**Option 2: Manual SQL Run**
```sql
1. PhpMyAdmin mein SQL tab open karein
2. backend/database/schema.sql file ka content copy karein
3. SQL box mein paste karein
4. Go button click karein
```

### Step 4: Backend Dependencies Install

```bash
cd c:\xampp\htdocs\bizinote\backend
npm install
```

### Step 5: Backend Server Start

```bash
npm start
```

Server start hone ke baad dikhega:
```
Server running on port 3000
API URL: http://localhost:3000
```

## 🔧 Configuration

`.env` file mein settings:
```env
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=          # XAMPP default mein password blank hota hai
DB_NAME=bizinote_db
DB_PORT=3306
PORT=3000
```

## 📊 Database Tables

1. **users** - User accounts
2. **products** - Product inventory
3. **customers** - Customer database
4. **categories** - Product categories
5. **sales** - Sales records
6. **expenses** - Expense tracking
7. **bills** - Bill records
8. **bill_items** - Bill line items
9. **backups** - Backup history

## 🔄 API Endpoints

### Authentication
- `POST /api/auth/register` - New user registration
- `POST /api/auth/login` - User login

### Products
- `GET /api/products/:userId` - Get all products
- `POST /api/products` - Add new product
- `PUT /api/products/:id` - Update product
- `DELETE /api/products/:id` - Delete product

### Customers
- `GET /api/customers/:userId` - Get all customers
- `POST /api/customers` - Add new customer
- `PUT /api/customers/:id` - Update customer
- `DELETE /api/customers/:id` - Delete customer

### Sales
- `GET /api/sales/:userId` - Get all sales
- `POST /api/sales` - Add new sale

### Expenses
- `GET /api/expenses/:userId` - Get all expenses
- `POST /api/expenses` - Add new expense

### Bills
- `GET /api/bills/:userId` - Get all bills
- `POST /api/bills` - Create new bill

### Backup & Restore
- `POST /api/backup/create` - Create backup
- `POST /api/backup/restore` - Restore backup
- `GET /api/backup/list/:userId` - Get backup list
- `POST /api/backup/sync` - Sync local data to server

## 📱 Mobile App Integration

Mobile app automatically SQL database se connect hoga jab:
1. Backend server running ho
2. Mobile app aur server same network pe ho
3. API_URL correctly configured ho

### API URL Update (Mobile App)

`src/services/api.js` mein:
```javascript
// Local development
const API_URL = 'http://localhost:3000/api';

// Network access (apne computer ka IP use karein)
const API_URL = 'http://192.168.1.100:3000/api';
```

## 💾 Backup Features

### 1. Create Backup
- Saara data SQL database mein save hota hai
- Timestamp ke saath backup create hota hai
- Multiple backups rakh sakte hain

### 2. Restore Backup
- Purana data wapas la sakte hain
- Backup select karke restore karein
- Current data replace ho jayega

### 3. Sync to Server
- Local AsyncStorage data ko SQL pe upload karein
- Automatic sync bhi ho sakta hai
- Data loss se bachne ke liye useful

### 4. Export Local
- JSON format mein data export
- File download kar sakte hain
- Manual backup ke liye

## 🔒 Security Features

- Password encryption with bcrypt
- JWT token authentication
- SQL injection protection
- User data isolation

## 🚀 Testing

### Test Backend API

```bash
# Test server running
curl http://localhost:3000

# Test registration
curl -X POST http://localhost:3000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"businessName":"Test Store","mobile":"1234567890","email":"test@test.com","password":"test123"}'
```

### Check Database

1. PhpMyAdmin open karein
2. `bizinote_db` select karein
3. Tables check karein
4. Data verify karein

## 📝 Common Issues

### Issue 1: "Cannot connect to database"
**Solution:**
- XAMPP mein MySQL running hai check karein
- Database name correct hai verify karein
- .env file settings check karein

### Issue 2: "Port 3000 already in use"
**Solution:**
```bash
# Port change karein .env mein
PORT=3001
```

### Issue 3: "Table doesn't exist"
**Solution:**
- schema.sql file run karein
- PhpMyAdmin mein tables check karein

## 🎯 Next Steps

1. ✅ Backend server start karein
2. ✅ Database tables create karein
3. ✅ Mobile app se test karein
4. ✅ Backup/Restore test karein
5. ✅ Production deployment ke liye ready

## 📞 Support

Backend issues ke liye:
- Check server logs
- Verify database connection
- Test API endpoints with Postman/curl

---

**Backend successfully configured! Ab aap SQL database ke saath kaam kar sakte hain.** 🎉
