# GICT Application - Windows Setup Guide

Simple guide to run the GICT application on Windows using XAMPP (no Docker required).

## 📥 Step 1: Install XAMPP

1. Download XAMPP: https://www.apachefriends.org/download.html
2. Choose **PHP 8.2** version (or latest available)
3. Install to default location: `C:\xampp`
4. During installation, allow firewall access if prompted

## 🚀 Step 2: Start Services

1. Open **XAMPP Control Panel** (from Start Menu)
2. Click **"Start"** button for:
   - ✅ **Apache**
   - ✅ **MySQL**
3. Both should show green "Running" status

## 📁 Step 3: Setup Project

### Option A: Use Setup Script (Easiest)
1. Copy your `gict` project folder anywhere (e.g., `C:\projects\gict`)
2. Double-click `setup-xampp.bat` in the project folder
3. Follow the prompts

### Option B: Manual Setup
1. Copy your `gict` folder to `C:\xampp\htdocs\gict`
2. Open phpMyAdmin: http://localhost/phpmyadmin
3. Click "New" to create database named `gict_db`
4. Select `gict_db`, go to "Import" tab
5. Choose `database_schema.sql` file and click "Go"

## ⚙️ Step 4: Configure Database

Edit `config/database.php` (in your project folder):

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'gict_db');
define('DB_USER', 'root');
define('DB_PASS', '');  // Empty for XAMPP default
```

## 🔧 Step 5: Enable PHP Extensions

1. Open: `C:\xampp\php\php.ini`
2. Find these lines and remove the `;` at the start:
   ```ini
   extension=gd
   extension=mbstring
   extension=curl
   extension=pdo_mysql
   ```
3. Save the file
4. Restart Apache in XAMPP Control Panel

## 📝 Step 6: Set Folder Permissions

1. Right-click on `uploads` folder → **Properties** → **Security** tab
2. Click **"Edit"** → **"Add"** → Type `Everyone` → **OK**
3. Check **"Full control"** → **OK**
4. Repeat for `assets\generated_marksheets` folder

## ✅ Step 7: Test Application

1. Open browser: **http://localhost/gict/**
2. Check health: **http://localhost/gict/health.php**
3. You should see a JSON response with status "healthy"

## 🎯 Quick Access

- **Application:** http://localhost/gict/
- **phpMyAdmin:** http://localhost/phpmyadmin
- **Health Check:** http://localhost/gict/health.php

## ❗ Common Issues

### Port 80 Already in Use
**Solution:** Change Apache port
1. XAMPP Control Panel → Apache "Config" → `httpd.conf`
2. Find `Listen 80` and change to `Listen 8080`
3. Restart Apache
4. Access: http://localhost:8080/gict/

### Database Connection Failed
**Check:**
- MySQL is running in XAMPP
- Database name is `gict_db`
- Username is `root`
- Password is empty `''` in config file

### Permission Denied for Uploads
**Solution:**
- Right-click folder → Properties → Security
- Add "Everyone" with "Full Control"
- Apply to all subfolders

### PHP Extensions Not Working
**Check:**
- Correct `php.ini` file is being used
- Extensions are uncommented (no `;` at start)
- Apache was restarted after changes

## 📚 Need More Help?

See `WINDOWS_SETUP.md` for detailed instructions and alternative methods.

---

**That's it! Your application should now be running on Windows.** 🎉

