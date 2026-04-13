import React, { useContext } from 'react';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { createStackNavigator } from '@react-navigation/stack';
import { Ionicons } from '@expo/vector-icons';
import { AppContext } from '../context/AppContext';
import translations from '../i18n/translations';
import DashboardScreen from '../screens/Dashboard/DashboardScreen';
import InventoryScreen from '../screens/Inventory/InventoryScreen';
import AddProductScreen from '../screens/Inventory/AddProductScreen';
import EditProductScreen from '../screens/Inventory/EditProductScreen';
import ProductDetailsScreen from '../screens/Inventory/ProductDetailsScreen';
import CustomersScreen from '../screens/Customers/CustomersScreen';
import AddCustomerScreen from '../screens/Customers/AddCustomerScreen';
import EditCustomerScreen from '../screens/Customers/EditCustomerScreen';
import CustomerDetailsScreen from '../screens/Customers/CustomerDetailsScreen';
import ReportsScreen from '../screens/Reports/ReportsScreen';
import ProfileScreen from '../screens/Profile/ProfileScreen';
import BillingScreen from '../screens/Billing/BillingScreen';
import InvoiceScreen from '../screens/Billing/InvoiceScreen';
import ExpenseScreen from '../screens/Expense/ExpenseScreen';
import AddExpenseScreen from '../screens/Expense/AddExpenseScreen';
import BackupRestoreScreen from '../screens/Profile/BackupRestoreScreen';
import NotificationSettingsScreen from '../screens/Profile/NotificationSettingsScreen';
import TermsConditionsScreen from '../screens/Auth/TermsConditionsScreen';
import PrivacyPolicyScreen from '../screens/Auth/PrivacyPolicyScreen';
import HelpSupportScreen from '../screens/Profile/HelpSupportScreen';
import NotificationScreen from '../screens/Notifications/NotificationScreen';
import DueScreen from '../screens/Udhari/DueScreen';

const Tab = createBottomTabNavigator();
const Stack = createStackNavigator();

const DashboardStack = () => (
  <Stack.Navigator>
    <Stack.Screen 
      name="DashboardMain" 
      component={DashboardScreen}
      options={{ headerShown: false }}
    />
    <Stack.Screen 
      name="Billing" 
      component={BillingScreen}
      options={{ headerShown: false }}
    />
    <Stack.Screen 
      name="Invoice" 
      component={InvoiceScreen}
      options={{ headerShown: false }}
    />
    <Stack.Screen 
      name="Expense" 
      component={ExpenseScreen}
      options={{ title: 'Expenses' }}
    />
    <Stack.Screen 
      name="AddExpense" 
      component={AddExpenseScreen}
      options={{ title: 'Add Expense' }}
    />
    <Stack.Screen 
      name="Notifications" 
      component={NotificationScreen}
      options={{ headerShown: false }}
    />
    <Stack.Screen 
      name="DueScreen" 
      component={DueScreen}
      options={{ headerShown: false }}
    />
  </Stack.Navigator>
);

const InventoryStack = () => (
  <Stack.Navigator>
    <Stack.Screen 
      name="InventoryMain" 
      component={InventoryScreen}
      options={{ headerShown: false }}
    />
    <Stack.Screen 
      name="ProductDetails" 
      component={ProductDetailsScreen}
      options={{ headerShown: false }}
    />
    <Stack.Screen 
      name="AddProduct" 
      component={AddProductScreen}
      options={{ 
        title: 'Add Product',
        headerStyle: { backgroundColor: '#2563EB' },
        headerTintColor: '#fff',
      }}
    />
    <Stack.Screen 
      name="EditProduct" 
      component={EditProductScreen}
      options={{ 
        title: 'Edit Product',
        headerStyle: { backgroundColor: '#2563EB' },
        headerTintColor: '#fff',
      }}
    />
  </Stack.Navigator>
);

const CustomersStack = () => (
  <Stack.Navigator>
    <Stack.Screen 
      name="CustomersList" 
      component={CustomersScreen}
      options={{ headerShown: false }}
    />
    <Stack.Screen 
      name="AddCustomer" 
      component={AddCustomerScreen}
      options={{ title: 'Add Customer' }}
    />
    <Stack.Screen 
      name="EditCustomer" 
      component={EditCustomerScreen}
      options={{ headerShown: false }}
    />
    <Stack.Screen 
      name="CustomerDetails" 
      component={CustomerDetailsScreen}
      options={{ title: 'Customer Details' }}
    />
    <Stack.Screen 
      name="Invoice" 
      component={InvoiceScreen}
      options={{ headerShown: false }}
    />
  </Stack.Navigator>
);

const ReportsStack = () => (
  <Stack.Navigator>
    <Stack.Screen 
      name="ReportsMain" 
      component={ReportsScreen}
      options={{ headerShown: false }}
    />
    <Stack.Screen 
      name="Invoice" 
      component={InvoiceScreen}
      options={{ headerShown: false }}
    />
  </Stack.Navigator>
);

const ProfileStack = () => (
  <Stack.Navigator>
    <Stack.Screen 
      name="ProfileMain" 
      component={ProfileScreen}
      options={{ headerShown: false }}
    />
    <Stack.Screen 
      name="BackupRestore" 
      component={BackupRestoreScreen}
      options={{ title: 'Backup & Restore' }}
    />
    <Stack.Screen 
      name="NotificationSettings" 
      component={NotificationSettingsScreen}
      options={{ headerShown: false }}
    />
    <Stack.Screen 
      name="TermsConditions" 
      component={TermsConditionsScreen}
      options={{ headerShown: false }}
    />
    <Stack.Screen 
      name="PrivacyPolicy" 
      component={PrivacyPolicyScreen}
      options={{ headerShown: false }}
    />
    <Stack.Screen 
      name="HelpSupport" 
      component={HelpSupportScreen}
      options={{ headerShown: false }}
    />
  </Stack.Navigator>
);

const MainNavigator = () => {
  const { language } = useContext(AppContext);
  const lang = language === 'hi' ? 'hi' : 'en';
  const t = (key) => translations[lang]?.[key] || translations['en']?.[key] || key;

  return (
    <Tab.Navigator
      screenOptions={({ route }) => ({
        tabBarIcon: ({ focused, color, size }) => {
          let iconName;

          if (route.name === 'Home') {
            iconName = focused ? 'home' : 'home-outline';
          } else if (route.name === 'Inventory') {
            iconName = focused ? 'cube' : 'cube-outline';
          } else if (route.name === 'Customers') {
            iconName = focused ? 'people' : 'people-outline';
          } else if (route.name === 'Reports') {
            iconName = focused ? 'bar-chart' : 'bar-chart-outline';
          } else if (route.name === 'Profile') {
            iconName = focused ? 'person' : 'person-outline';
          }

          return <Ionicons name={iconName} size={size} color={color} />;
        },
        tabBarActiveTintColor: '#2563EB',
        tabBarInactiveTintColor: 'gray',
        headerShown: false,
      })}
    >
      <Tab.Screen name="Home" component={DashboardStack} options={{ tabBarLabel: lang === 'hi' ? 'होम' : 'Home' }} />
      <Tab.Screen name="Inventory" component={InventoryStack} options={{ tabBarLabel: t('inventory') }} />
      <Tab.Screen name="Customers" component={CustomersStack} options={{ tabBarLabel: t('customers') }} />
      <Tab.Screen name="Reports" component={ReportsStack} options={{ tabBarLabel: t('reports') }} />
      <Tab.Screen name="Profile" component={ProfileStack} options={{ tabBarLabel: t('profile') }} />
    </Tab.Navigator>
  );
};

export default MainNavigator;
