# Quick Start Guide - Windows

## 🚀 Fastest Way to Get Started

### Option 1: XAMPP (Recommended - 10 minutes)

**Prerequisites:**
- XAMPP installed: https://www.apachefriends.org
- Download PHP 8.2 version (or latest)

**Steps:**
1. Start Apache and MySQL in XAMPP Control Panel
2. Double-click `setup-xampp.bat` in project folder
3. Copy project to `C:\xampp\htdocs\gict`
4. Open: http://localhost/gict/

**That's it!** 🎉

---

### Option 2: Native PHP + MySQL (Advanced)

**Prerequisites:**
- PHP 8.2: https://windows.php.net/download/
- MySQL: https://dev.mysql.com/downloads/installer/

**Steps:**
1. Install PHP and add to PATH
2. Install MySQL Server
3. Import database schema
4. Run: `php -S localhost:8000`
5. Open: http://localhost:8000/

---

## 📋 What Gets Set Up

✅ Database created (`gict_db`)
✅ Database schema imported
✅ File permissions configured
✅ Health check endpoint ready

---

## 🔍 Verify Installation

1. **Check Health:**
   - XAMPP: http://localhost/gict/health.php
   - Native PHP: http://localhost:8000/health.php

2. **Check Database:**
   - XAMPP: http://localhost/phpmyadmin
   - Native: Use MySQL Workbench or command line

---

## 🛠️ Troubleshooting

### Port 80 Already in Use (XAMPP)
1. Open XAMPP Control Panel
2. Click "Config" → "httpd.conf"
3. Change `Listen 80` to `Listen 8080`
4. Access: http://localhost:8080/gict/

### Database Connection Failed
Check `config/database.php`:
- XAMPP: `DB_PASS` should be empty `''`
- Docker: Already configured in docker-compose.yml

---

## 📚 More Details

See `WINDOWS_SETUP.md` for detailed instructions and alternative methods.

---

## 🎯 Next Steps

1. ✅ Application running
2. 📝 Create admin user (if needed)
3. 🔐 Test login functionality
4. 📊 Check admin dashboard
5. 🚀 Start developing!

