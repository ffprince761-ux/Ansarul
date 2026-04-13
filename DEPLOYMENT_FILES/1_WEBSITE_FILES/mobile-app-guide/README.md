# 📱 Mobile App Loading Implementation Guide

## क्या Problem Solve Hogi:
- ✅ App open hote waqt "Loading..." dikhega jab tak backend se data nahi aata
- ✅ Jab bhi API call ho, automatic loading dikhega
- ✅ Data aa gaya to loading apne aap hat jayega
- ✅ Multiple API calls ko handle karta hai - sab khatam hone ke baad hi loading hat ta hai

## 📁 Files:

### 1. LoadingProvider.js
**Global Loading Context** - Isko App.js ke around wrap karo

### 2. useApi.js  
**API Hook** - Isme `get`, `post`, `put`, `delete` methods hain with automatic loading

### 3. App-setup.js
**Example Setup** - Complete App.js structure with navigation

---

## 🚀 Installation Steps:

### Step 1: Files Copy Karo
Apne Expo project mein yeh 2 files copy karo:
```
/src
  /components
    LoadingProvider.js    ← Copy this
    useApi.js             ← Copy this
```

### Step 2: App.js Setup
```javascript
import { LoadingProvider } from './components/LoadingProvider';

export default function App() {
  return (
    <LoadingProvider>      {/* ← Wrap everything */}
      <NavigationContainer>
        {/* Your screens */}
      </NavigationContainer>
    </LoadingProvider>
  );
}
```

### Step 3: API Call Karte Waqt
```javascript
import { useApi } from './components/useApi';

function HomeScreen() {
  const { get, post } = useApi();

  const fetchData = async () => {
    // ⬇️ Automatic loading dikhega
    const result = await get('products');
    
    // ⬇️ Data aa gaya to loading hat gaya
    if (result.success) {
      setProducts(result.data);
    }
  };
}
```

---

## 🎯 Usage Examples:

### 1. Basic API Call with Loading:
```javascript
const { get } = useApi();

// Loading dikhega, data aa gaya to hat jayega
const result = await get('users');
```

### 2. Custom Loading Text:
```javascript
const result = await post('save', data, {
  loaderText: 'Saving please wait...'
});
```

### 3. Without Loading (Silent):
```javascript
const result = await get('notifications', { 
  showLoader: false 
});
```

### 4. App Initial Load:
```javascript
import { useAppLoader } from './components/useApi';

function App() {
  const { loadInitialData } = useAppLoader();

  useEffect(() => {
    // App start hote waqt sab data load karo
    loadInitialData().then(() => {
      console.log('App ready!');
    });
  }, []);
}
```

---

## ⚙️ Configuration:

### API URL Change Karo:
**useApi.js** mein yeh line change karo:
```javascript
const API_BASE_URL = 'https://yourdomain.com/api';
// Ya agar local hai to:
const API_BASE_URL = 'http://192.168.1.100:8080/api';
```

---

## 🔥 Key Features:

1. **Automatic Loading**: Har API call pe loading dikhega
2. **Smart Counter**: Multiple API calls ko handle karta hai
3. **Custom Text**: Har call pe alag loading text dikh sakte ho
4. **Silent Mode**: Chhup ke data fetch kar sakte ho (background mein)
5. **Error Handling**: Automatic error handling with loading cleanup

---

## 💡 Example Flow:

```
User opens app
    ↓
Loading shows "Loading app..."
    ↓
Backend se data aata hai
    ↓
Loading automatically hat jata hai
    ↓
App screen dikhata hai

---

User "Save" button click karta hai
    ↓
Loading shows "Saving..."
    ↓
API call complete hota hai
    ↓
Loading hat jata hai
    ↓
Success message dikhata hai
```

---

## 🆘 Common Issues:

### Issue: Loading nahi dikhta
**Solution**: Check karo `LoadingProvider` App.js mein wrap hai ya nahi

### Issue: API URL galat
**Solution**: `useApi.js` mein `API_BASE_URL` check karo

### Issue: Loading hat ta hi nahi
**Solution**: `hideLoading()` finally block mein hai - check karo

---

**Questions?** Error aaye to console.log se debug karo ya backend API check karo.
