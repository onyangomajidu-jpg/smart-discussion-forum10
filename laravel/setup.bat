@echo off
echo ========================================
echo Smart Discussion Forum - Setup Script
echo ========================================
echo.

REM Check if Composer is installed
where composer >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Composer is not installed or not in PATH
    echo Download from: https://getcomposer.org/Composer-Setup.exe
    echo.
    pause
    exit /b 1
)

REM Check if Node.js is installed
where node >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Node.js is not installed or not in PATH
    echo Download from: https://nodejs.org/
    echo.
    pause
    exit /b 1
)

REM Check if NPM is installed
where npm >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] NPM is not installed or not in PATH
    echo Install Node.js from: https://nodejs.org/
    echo.
    pause
    exit /b 1
)

echo [OK] All required tools are installed
echo.

echo Step 1: Installing Composer dependencies...
composer install
if %errorlevel% neq 0 (
    echo [ERROR] Composer install failed
    pause
    exit /b 1
)
echo [OK] Composer dependencies installed
echo.

echo Step 2: Installing NPM dependencies...
call npm install
if %errorlevel% neq 0 (
    echo [ERROR] NPM install failed
    pause
    exit /b 1
)
echo [OK] NPM dependencies installed
echo.

echo Step 3: Building frontend assets...
call npm run build
if %errorlevel% neq 0 (
    echo [ERROR] NPM build failed
    pause
    exit /b 1
)
echo [OK] Frontend assets built
echo.

echo Step 4: Running database migrations...
php artisan migrate --force
if %errorlevel% neq 0 (
    echo [ERROR] Database migration failed
    echo Make sure:
    echo   - XAMPP MySQL is running
    echo   - Database 'smart_discussion_forum' exists
    echo   - .env file has correct database credentials
    pause
    exit /b 1
)
echo [OK] Database migrations completed
echo.

echo ========================================
echo Setup completed successfully!
echo ========================================
echo.
echo To start the Laravel server, run:
echo   php artisan serve
echo.
echo Server will be available at:
echo   http://localhost:8000
echo.
pause
