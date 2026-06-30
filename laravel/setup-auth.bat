@echo off
echo ===============================================
echo Smart Discussion Forum - Authentication Setup
echo ===============================================
echo.

echo [1/5] Running database migrations...
php artisan migrate

echo.
echo [2/5] Clearing application cache...
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo.
echo [3/5] Optimizing application...
php artisan config:cache
php artisan route:cache

echo.
echo [4/5] Creating storage link...
php artisan storage:link

echo.
echo [5/5] Setup complete!
echo.
echo ===============================================
echo Authentication System Ready!
echo ===============================================
echo.
echo Available Routes:
echo   - Login:    http://localhost:8000/login
echo   - Register: http://localhost:8000/register
echo   - Forgot:   http://localhost:8000/forgot-password
echo.
echo To start the server, run:
echo   php artisan serve
echo.
echo Then visit: http://localhost:8000
echo.
pause
