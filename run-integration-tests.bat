@echo off
setlocal enabledelayedexpansion
title Week 1 Integration Test Runner

echo ============================================================
echo  Smart Discussion Forum — Week 1 Integration Test
echo  Web login ^<-^> API ^<-^> Java GUI
echo ============================================================
echo.

:: ── 1. Check Laravel is running ──────────────────────────────────────────
echo [1/4] Checking Laravel server at http://localhost:8000 ...
curl -s -o nul -w "%%{http_code}" http://localhost:8000/api/ping > %TEMP%\ping_code.txt 2>nul
set /p HTTP_CODE=<%TEMP%\ping_code.txt
if "%HTTP_CODE%"=="200" (
    echo       OK — server is running.
) else (
    echo       WARN — server not detected (code: %HTTP_CODE%).
    echo       Starting Laravel dev server in background...
    start /B cmd /c "cd /d %~dp0laravel && php artisan serve --port=8000 > nul 2>&1"
    timeout /t 4 /nobreak > nul
    echo       Laravel started. Continuing...
)
echo.

:: ── 2. Seed test user ────────────────────────────────────────────────────
echo [2/4] Seeding test user (test@example.com / password123) ...
cd /d %~dp0laravel
php artisan db:seed --class=AuthenticationSeeder --force > nul 2>&1
if %ERRORLEVEL%==0 (
    echo       OK — seeder ran successfully.
) else (
    echo       WARN — seeder returned non-zero (user may already exist, continuing).
)
echo.

:: ── 3. Run Java integration tests ────────────────────────────────────────
echo [3/4] Running Java Week1IntegrationTest via Maven ...
cd /d %~dp0java-gui
call mvn test -Dtest=Week1IntegrationTest -pl . 2>&1
set MVN_EXIT=%ERRORLEVEL%
echo.

:: ── 4. Report ─────────────────────────────────────────────────────────────
echo [4/4] Results
if %MVN_EXIT%==0 (
    echo       ALL TESTS PASSED ✓
) else (
    echo       SOME TESTS FAILED — check output above.
    echo       Common blockers:
    echo         - Laravel not running: start with 'php artisan serve'
    echo         - DB not migrated: run 'php artisan migrate --force'
    echo         - Sanctum not configured: check config/sanctum.php
)
echo.
echo ============================================================
echo  Surefire report: java-gui\target\surefire-reports\
echo ============================================================
pause
