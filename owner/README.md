# Bizinote Owner Monitoring Panel

## 🎯 Overview

The Owner Monitoring Panel is a comprehensive web-based dashboard designed for the Bizinote app owner/developer to monitor and analyze the entire platform's performance, user activity, revenue, and system health.

## 📁 Installation

### Step 1: Import Database Tables

Run the SQL file to create owner-specific tables:

```bash
mysql -u root -p bizinote < setup_owner_tables.sql
```

Or import via phpMyAdmin:
1. Open phpMyAdmin
2. Select `bizinote` database
3. Go to Import tab
4. Choose `setup_owner_tables.sql`
5. Click Go

### Step 2: Access the Panel

Navigate to: `http://localhost/bizinote/owner/`

### Step 3: Login

**Default Credentials:**
- Username: `owner`
- Password: `owner123`

⚠️ **IMPORTANT:** Change the password after first login!

## 🔐 Security

- The panel is separate from the main app
- Uses separate authentication system
- All owner actions are logged
- Session-based security
- IP tracking for all activities

## 📊 Features

### 1. Dashboard
- Total users and active users
- Total revenue and trends
- Bills and products statistics
- Revenue trend charts (30 days)
- User growth charts (6 months)
- Top performing businesses
- Recent user registrations

### 2. Users Management
- Complete list of all registered users
- Search and filter functionality
- User statistics (products, customers, bills, revenue)
- Registration dates and last activity
- Contact information

### 3. Revenue Analytics
- Total revenue (all time)
- Monthly and daily revenue
- Average bill value
- Revenue trends (12 months)
- Payment method distribution
- Revenue by business with percentages
- Comparative analysis

### 4. Analytics
- User engagement metrics (DAU, MAU)
- Feature usage statistics
- Daily activity tracking
- User retention analysis
- Product categories distribution
- Average metrics per user

### 5. System Health
- Database size monitoring
- System uptime tracking
- Table record counts
- Security event logs
- Failed login attempts
- Blocked IPs management
- Owner activity logs

## 🛠️ Technical Stack

- **Frontend:** HTML5, CSS3, Bootstrap 5
- **Backend:** PHP 8.x
- **Database:** MySQL (shared with main app)
- **Charts:** Chart.js
- **Icons:** Font Awesome 6

## 📋 Database Tables

### owner_users
Stores owner account credentials

### owner_activity_logs
Tracks all owner actions for audit trail

### app_analytics
Stores platform-wide analytics metrics

## 🎨 UI Features

- Modern gradient design
- Responsive layout (mobile-friendly)
- Fixed sidebar navigation
- Real-time charts and graphs
- Interactive tables
- Search functionality
- Auto-refresh dashboard (5 minutes)

## 📈 Key Metrics Tracked

1. **Business Metrics**
   - Total registered users
   - Active users (7-day, 30-day)
   - User growth trends
   - Retention rates

2. **Revenue Metrics**
   - Total platform revenue
   - Daily/monthly revenue
   - Revenue per user
   - Payment method distribution

3. **Engagement Metrics**
   - Feature usage statistics
   - Daily active users
   - Session patterns
   - User behavior

4. **System Metrics**
   - Database size
   - System uptime
   - Security events
   - Performance indicators

## 🔄 Auto-Refresh

The dashboard automatically refreshes every 5 minutes to show the latest data.

## 🚀 Future Enhancements

- Real-time notifications
- Export reports (CSV, PDF)
- Email alerts
- Advanced filtering
- Custom date ranges
- API access
- Mobile app version

## 📞 Support

For issues or questions, contact the development team.

## 📝 License

© 2026 Bizinote. All rights reserved.
