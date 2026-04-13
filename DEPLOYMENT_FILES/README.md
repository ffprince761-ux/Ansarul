# Binest - Deployment Files (Production Ready)

> Last Updated: February 14, 2026

---

## 📁 Folder Structure

```
DEPLOYMENT_FILES/
├── 1_WEBSITE_FILES/      → Backend API (PHP) - Upload to hosting server
├── 2_DATABASE_FILE/      → SQL files - Import in MySQL/phpMyAdmin
├── 3_APP_SOURCE_CODE/    → React Native App - Build with EAS
├── 4_OWNER_ADMIN_PANEL/  → Owner Dashboard (PHP) - Upload to hosting server
```

---

## 1️⃣ WEBSITE_FILES (Backend API)
**Location on server:** `public_html/api/` or `public_html/backend/`
**What it contains:**
- `api/` → All API endpoints (auth, products, bills, customers, expenses, etc.)
- `config.php` → Database connection config (**EDIT THIS FIRST**)
- `PHPMailer/` → Email library for OTP
- `security/` → Rate limiting, security middleware
- `database/` → Database schema files

**Setup:**
1. Upload all files to your hosting
2. Edit `config.php` with your database credentials
3. Import database SQL from `2_DATABASE_FILE/`

---

## 2️⃣ DATABASE_FILE (SQL)
**What it contains:**
- `bizinote_complete.sql` → Complete database with all tables
- `database.sql` → Basic database structure
- `schema.sql` → Schema only

**Setup:**
1. Go to phpMyAdmin on your hosting
2. Create a new database
3. Import `bizinote_complete.sql`

---

## 3️⃣ APP_SOURCE_CODE (React Native / Expo)
**What it contains:**
- `App.js` → Main app entry
- `src/` → All screens, context, services, i18n, utils
- `app.json` → Expo config
- `eas.json` → EAS Build config
- `package.json` → Dependencies

**API URL:** `https://tensemock.in/api` (already set in `src/services/api.js`)

**Build APK:**
```bash
npm install
eas build --platform android --profile preview
```

**Build AAB (Play Store):**
```bash
eas build --platform android --profile production
```

---

## 4️⃣ OWNER_ADMIN_PANEL
**Location on server:** `public_html/owner/`
**What it contains:**
- `index.php` → Login page
- `dashboard.php` → Main dashboard
- `users.php` → User management
- `user_detail.php` → Individual user details
- `analytics.php` → Business analytics
- `settings.php` → App settings (block users, send notifications)
- `revenue.php` → Revenue tracking
- `system.php` → System health monitoring

**Setup:**
1. Upload all files to `owner/` folder on hosting
2. Run `install.php` first time to setup owner tables
3. Login with owner credentials

---

## ⚡ Quick Deployment Checklist

- [ ] Upload `1_WEBSITE_FILES/` to server as backend API
- [ ] Import `2_DATABASE_FILE/bizinote_complete.sql` in MySQL
- [ ] Edit `config.php` with DB credentials
- [ ] Upload `4_OWNER_ADMIN_PANEL/` to `owner/` folder
- [ ] Run `owner/install.php` once
- [ ] Build app APK from `3_APP_SOURCE_CODE/`
- [ ] Test app with production API
