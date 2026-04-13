# BIZINOTE - Small Business Management App

A complete React Native mobile application for small business management with inventory tracking, customer management, billing, expense tracking, and comprehensive reporting.

## Features

### 🔐 Authentication
- Splash Screen with branding
- Multi-language support (English, Hindi, Marathi, Gujarati)
- User registration with business details
- Secure login system

### 📊 Dashboard
- Real-time sales, expenses, and profit tracking
- Today's statistics with percentage changes
- Low stock alerts
- Recent activity feed
- Quick action buttons

### 📦 Inventory Management
- Product catalog with categories
- Stock management
- Low stock alerts
- Category-wise product organization
- Add/Edit/Delete products

### 👥 Customer Management
- Customer database
- Contact information storage
- Purchase history tracking
- Customer details view

### 🧾 Billing System
- Create bills with customer selection
- Add multiple items to bill
- Quantity management with stock validation
- Discount and tax calculation
- Save and share bills
- Payment mode tracking

### 💰 Expense Tracking
- Add expenses by category
- Daily/Monthly/Yearly expense views
- Category-wise expense organization
- Expense history

### 📈 Reports & Analytics
- Visual charts and graphs
- Sales overview
- Profit & Loss reports
- Best selling products
- Customer purchase analytics
- Daily/Monthly/Yearly reports
- Export to PDF/Excel

### ⚙️ Profile & Settings
- Business information management
- Language selection
- Password change
- Data backup & restore
- Notifications settings
- Help & Support

## Tech Stack

- **Framework**: React Native (Expo)
- **Navigation**: React Navigation v6
- **State Management**: React Context API
- **Storage**: AsyncStorage
- **UI Components**: React Native Paper
- **Gradients**: Expo Linear Gradient
- **Icons**: Expo Vector Icons (Ionicons)
- **Charts**: React Native Chart Kit

## Installation

### Prerequisites
- Node.js (v14 or higher)
- npm or yarn
- Expo CLI
- Android Studio (for Android development) or Xcode (for iOS development)

### Setup Instructions

1. **Clone or navigate to the project directory**
```bash
cd c:/xampp/htdocs/bizinote
```

2. **Install dependencies**
```bash
npm install
```

3. **Start the development server**
```bash
npm start
```

4. **Run on Android**
```bash
npm run android
```

5. **Run on iOS** (macOS only)
```bash
npm run ios
```

6. **Run on Web**
```bash
npm run web
```

## Project Structure

```
bizinote/
├── App.js                          # Main app entry point
├── app.json                        # Expo configuration
├── package.json                    # Dependencies
├── babel.config.js                 # Babel configuration
├── src/
│   ├── context/
│   │   └── AppContext.js          # Global state management
│   ├── navigation/
│   │   └── MainNavigator.js       # Bottom tab navigation
│   └── screens/
│       ├── Auth/
│       │   ├── SplashScreen.js
│       │   ├── LanguageSelectionScreen.js
│       │   ├── LoginScreen.js
│       │   └── RegisterScreen.js
│       ├── Dashboard/
│       │   └── DashboardScreen.js
│       ├── Inventory/
│       │   ├── InventoryScreen.js
│       │   └── AddProductScreen.js
│       ├── Customers/
│       │   ├── CustomersScreen.js
│       │   ├── AddCustomerScreen.js
│       │   └── CustomerDetailsScreen.js
│       ├── Billing/
│       │   └── BillingScreen.js
│       ├── Expense/
│       │   ├── ExpenseScreen.js
│       │   └── AddExpenseScreen.js
│       ├── Reports/
│       │   └── ReportsScreen.js
│       └── Profile/
│           └── ProfileScreen.js
```

## Features Breakdown

### Data Storage
All data is stored locally using AsyncStorage:
- User information
- Products and categories
- Customers
- Sales and bills
- Expenses
- Language preferences

### Color Scheme
- Primary Blue: #2563EB
- Success Green: #10B981
- Warning Orange: #F59E0B
- Danger Red: #EF4444
- Purple: #8B5CF6
- Cyan: #06B6D4

### Navigation Flow
1. **Splash Screen** → Language Selection (first time only)
2. **Language Selection** → Login/Register
3. **Login/Register** → Main App (Bottom Tabs)
4. **Main App Tabs**:
   - Home (Dashboard)
   - Inventory
   - Customers
   - Reports
   - Profile

## Usage Guide

### First Time Setup
1. Select your preferred language
2. Create an account with business details
3. Start adding products to inventory
4. Add customers to database
5. Create bills and track expenses
6. View reports and analytics

### Creating a Bill
1. Navigate to Dashboard
2. Click "Create Bill" quick action
3. Select customer
4. Add products with quantities
5. Apply discount and tax
6. Save or share the bill

### Managing Inventory
1. Go to Inventory tab
2. Click "Add Product"
3. Fill in product details
4. Select category
5. Set price and stock quantity
6. Save product

### Tracking Expenses
1. Go to Profile tab
2. Navigate to Expenses
3. Click "Add Expense"
4. Select category
5. Enter amount and description
6. Save expense

## Development

### Adding New Features
1. Create new screen component in appropriate folder
2. Add navigation route in MainNavigator.js
3. Update AppContext.js for state management
4. Implement AsyncStorage for data persistence

### Customization
- Modify colors in individual screen styles
- Update language options in LanguageSelectionScreen.js
- Add new categories in respective screens
- Customize reports in ReportsScreen.js

## Building for Production

### Android APK
```bash
expo build:android
```

### iOS IPA
```bash
expo build:ios
```

### Using EAS Build (Recommended)
```bash
npm install -g eas-cli
eas build --platform android
eas build --platform ios
```

## Troubleshooting

### Common Issues

1. **Metro bundler not starting**
   - Clear cache: `expo start -c`

2. **Dependencies not installing**
   - Delete node_modules and package-lock.json
   - Run `npm install` again

3. **App not loading on device**
   - Ensure device and computer are on same network
   - Check firewall settings

## Future Enhancements

- [ ] Cloud backup integration
- [ ] Multi-user support
- [ ] Barcode scanner for products
- [ ] WhatsApp bill sharing
- [ ] Advanced analytics with more charts
- [ ] Invoice templates
- [ ] Tax calculation automation
- [ ] Payment gateway integration
- [ ] Inventory alerts via notifications
- [ ] Export data to Excel/PDF

## License

This project is created for educational and business purposes.

## Support

For support and queries, contact through the app's Help & Support section.

---

**Made with ❤️ for Small Businesses**
