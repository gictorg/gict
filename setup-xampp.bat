@echo off
echo ========================================
echo GICT Application - XAMPP Setup Script
echo ========================================
echo.

REM Check if XAMPP is installed
if not exist "C:\xampp\mysql\bin\mysql.exe" (
    echo ERROR: XAMPP not found at C:\xampp
    echo Please install XAMPP first from https://www.apachefriends.org
    pause
    exit /b 1
)

echo [1/4] Creating database...
"C:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS gict_db;" 2>nul
if %errorlevel% neq 0 (
    echo WARNING: Could not create database. MySQL might not be running.
    echo Please start MySQL in XAMPP Control Panel first.
    pause
    exit /b 1
)
echo Database created successfully!

echo.
echo [2/4] Importing database schema...
if exist "database_schema.sql" (
    "C:\xampp\mysql\bin\mysql.exe" -u root gict_db < database_schema.sql
    if %errorlevel% equ 0 (
        echo Database schema imported successfully!
    ) else (
        echo WARNING: Could not import schema. You may need to import manually.
    )
) else (
    echo WARNING: database_schema.sql not found. Skipping import.
)

echo.
echo [3/4] Setting folder permissions...
if exist "uploads" (
    icacls "uploads" /grant Everyone:F /T /Q >nul 2>&1
    echo Uploads folder permissions set.
) else (
    mkdir uploads
    icacls "uploads" /grant Everyone:F /T /Q >nul 2>&1
    echo Uploads folder created and permissions set.
)

if exist "assets\generated_marksheets" (
    icacls "assets\generated_marksheets" /grant Everyone:F /T /Q >nul 2>&1
    echo Generated marksheets folder permissions set.
) else (
    mkdir "assets\generated_marksheets" 2>nul
    icacls "assets\generated_marksheets" /grant Everyone:F /T /Q >nul 2>&1
    echo Generated marksheets folder created and permissions set.
)

echo.
echo [4/4] Checking database configuration...
findstr /C:"DB_HOST" config\database.php | findstr /C:"localhost" >nul
if %errorlevel% equ 0 (
    echo Database configuration looks correct.
) else (
    echo WARNING: Please check config\database.php settings.
    echo Make sure DB_HOST is set to 'localhost' and DB_PASS is empty for XAMPP.
)

echo.
echo ========================================
echo Setup Complete!
echo ========================================
echo.
echo Next steps:
echo 1. Make sure Apache and MySQL are running in XAMPP Control Panel
echo 2. Copy this folder to C:\xampp\htdocs\gict
echo    OR create a virtual host (see WINDOWS_SETUP.md)
echo 3. Open browser: http://localhost/gict/
echo 4. Test health: http://localhost/gict/health.php
echo.
pause

