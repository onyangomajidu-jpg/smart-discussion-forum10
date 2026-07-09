@echo off
REM ── Week-1 Integration Setup ──────────────────────────────────────────────
REM Run from: laravel\
REM Installs Sanctum, migrates personal_access_tokens, seeds test user.

echo [1/4] Installing Laravel Sanctum...
call composer require laravel/sanctum --no-interaction
if %ERRORLEVEL% NEQ 0 ( echo FAILED: composer require && exit /b 1 )

echo [2/4] Publishing Sanctum config + migration...
call php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider" --force
if %ERRORLEVEL% NEQ 0 ( echo FAILED: vendor:publish && exit /b 1 )

echo [3/4] Running migrations (creates personal_access_tokens table)...
call php artisan migrate --force
if %ERRORLEVEL% NEQ 0 ( echo FAILED: migrate && exit /b 1 )

echo [4/4] Seeding integration test user (test@example.com / password)...
call php artisan tinker --execute="App\Models\User::firstOrCreate(['email'=>'test@example.com'],['name'=>'Test User','password'=>bcrypt('password'),'role'=>'member']);"
if %ERRORLEVEL% NEQ 0 ( echo WARN: tinker seed failed - create user manually )

echo.
echo ✓ Setup complete. Start server: php artisan serve
echo ✓ Run integration test: cd ..\java-gui ^&^& mvn test -Dtest=IntegrationTest
