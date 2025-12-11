# Running GICT Application on Windows

This guide provides multiple methods to run the GICT PHP application on Windows.

**⭐ Recommended for most users: XAMPP (Method 1) - No Docker required!**

## Method 1: XAMPP (Easiest - Recommended)

### Step 1: Install XAMPP
1. Download XAMPP from: https://www.apachefriends.org/download.html
2. Choose PHP 8.2 version (or latest)
3. Install to `C:\xampp` (default location)

### Step 2: Start Services
1. Open **XAMPP Control Panel**
2. Start **Apache** and **MySQL** services
3. Click "Start" for both services

### Step 3: Setup Database
1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Create a new database named `gict_db`
3. Import your database schema:
   - Click on `gict_db` database
   - Go to "Import" tab
   - Select `database_schema.sql` file
   - Click "Go"

### Step 4: Configure Database
Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'gict_db');
define('DB_USER', 'root');
define('DB_PASS', '');  // XAMPP default is empty password
```

### Step 5: Copy Application Files
1. Copy your entire `gict` folder to `C:\xampp\htdocs\gict`
2. Or create a symlink if you want to keep files in original location

### Step 6: Set Permissions
1. Right-click on `uploads` folder → Properties → Security
2. Give "Write" permissions to "Everyone" or "Users"
3. Do the same for `assets/generated_marksheets` folder

### Step 7: Access Application
- Open browser: http://localhost/gict/
- Or if in root: http://localhost/

### Step 8: Enable Required PHP Extensions
1. Open `C:\xampp\php\php.ini`
2. Find and uncomment (remove `;`):
   ```ini
   extension=gd
   extension=mbstring
   extension=curl
   extension=pdo_mysql
   ```
3. Restart Apache in XAMPP Control Panel

---

## Method 2: Docker Desktop (Optional - For Containerization)

### Step 1: Install Docker Desktop
1. Download from: https://www.docker.com/products/docker-desktop
2. Install and restart your computer
3. Start Docker Desktop

### Step 2: Install MySQL Container
```bash
docker run -d \
  --name gict-mysql \
  -e MYSQL_ROOT_PASSWORD=test_pass \
  -e MYSQL_DATABASE=gict_db \
  -p 3306:3306 \
  mysql:8.0
```

### Step 3: Setup Database
1. Wait for MySQL to start (30-60 seconds)
2. Import database schema:
   ```bash
   # Copy SQL file to container
   docker cp database_schema.sql gict-mysql:/tmp/
   
   # Import database
   docker exec -i gict-mysql mysql -uroot -ptest_pass gict_db < database_schema.sql
   ```

### Step 4: Build and Run Application
```bash
# Navigate to project directory
cd C:\path\to\gict

# Build Docker image
docker build -t gict-app .

# Run application container
docker run -d \
  --name gict-web \
  -p 8080:80 \
  --link gict-mysql:mysql \
  -e DB_HOST=mysql \
  -e DB_NAME=gict_db \
  -e DB_USER=root \
  -e DB_PASS=test_pass \
  gict-app
```

### Step 5: Access Application
- Open browser: http://localhost:8080/

### Alternative: Docker Compose (Easier)
Create `docker-compose.yml`:
```yaml
version: '3.8'
services:
  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: test_pass
      MYSQL_DATABASE: gict_db
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql

  web:
    build: .
    ports:
      - "8080:80"
    depends_on:
      - mysql
    environment:
      - DB_HOST=mysql
      - DB_NAME=gict_db
      - DB_USER=root
      - DB_PASS=test_pass

volumes:
  mysql_data:
```

Then run:
```bash
docker-compose up -d
```

---

## Method 3: WSL2 + Docker (Best Performance)

### Step 1: Install WSL2
1. Open PowerShell as Administrator
2. Run:
   ```powershell
   wsl --install
   ```
3. Restart computer

### Step 2: Install Docker in WSL2
1. Open Ubuntu (or your WSL distro)
2. Follow Docker installation for Linux
3. Or use Docker Desktop with WSL2 backend

### Step 3: Follow Docker steps from Method 2

---

## Method 4: Native PHP Installation (Advanced)

### Step 1: Install PHP
1. Download PHP 8.2 from: https://windows.php.net/download/
2. Extract to `C:\php`
3. Add `C:\php` to System PATH

### Step 2: Install MySQL
1. Download MySQL from: https://dev.mysql.com/downloads/installer/
2. Install MySQL Server
3. Set root password: `test_pass`

### Step 3: Install Apache
1. Download from: https://httpd.apache.org/download.cgi
2. Or use PHP built-in server for development:
   ```bash
   php -S localhost:8000
   ```

### Step 4: Configure PHP
1. Copy `php.ini-development` to `php.ini`
2. Enable extensions:
   ```ini
   extension=gd
   extension=mbstring
   extension=curl
   extension=pdo_mysql
   ```

### Step 5: Setup Database
- Use MySQL Workbench or command line to import `database_schema.sql`

---

## Quick Setup Script for XAMPP

Create `setup-xampp.bat`:

```batch
@echo off
echo Setting up GICT Application on XAMPP...

REM Create database
"C:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS gict_db;"

REM Import schema
"C:\xampp\mysql\bin\mysql.exe" -u root gict_db < database_schema.sql

REM Set permissions
icacls "uploads" /grant Everyone:F /T
icacls "assets\generated_marksheets" /grant Everyone:F /T

echo Setup complete!
echo Access application at: http://localhost/gict/
pause
```

---

## Troubleshooting

### Port 80 Already in Use
- XAMPP: Change Apache port in `httpd.conf` (Listen 8080)
- Or stop IIS/Skype/other services using port 80

### Database Connection Failed
- Check MySQL is running
- Verify credentials in `config/database.php`
- Check firewall settings

### Permission Denied for Uploads
- Right-click folder → Properties → Security
- Add "Everyone" with "Full Control"
- Or run as Administrator

### PHP Extensions Not Loading
- Check `php.ini` file location: `php --ini`
- Uncomment extension lines
- Restart Apache/PHP server

### Session Issues
- Check `php.ini` session settings
- Ensure `session.save_path` is writable
- Clear browser cookies

---

## Recommended Setup for Development

**For Quick Testing & Development:** Use XAMPP (Method 1) ⭐ **Recommended**
**For Advanced Users:** Use Native PHP Installation (Method 4)
**For Linux-like Environment:** Use WSL2 (Method 3)

---

## Next Steps

1. Import database schema
2. Configure `config/database.php`
3. Set file permissions for uploads
4. Access http://localhost/gict/
5. Test login functionality
6. Check health endpoint: http://localhost/gict/health.php

