# Installation Status & Next Steps

## Current Status ❌

The setup commands cannot run yet because required tools are not installed.

## Missing Tools

### 1. Composer (Required)
- **Purpose**: PHP dependency manager for Laravel
- **Download**: https://getcomposer.org/Composer-Setup.exe
- **Installation**: 
  1. Run the installer
  2. Follow the wizard (it will auto-detect PHP from XAMPP)
  3. Restart your terminal/command prompt
- **Verify**: Run `composer --version`

### 2. Node.js & NPM (Required)
- **Purpose**: JavaScript runtime for building frontend assets
- **Download**: https://nodejs.org/en/download/ (Choose LTS version)
- **Installation**:
  1. Run the installer
  2. Accept defaults (includes NPM)
  3. Restart your terminal/command prompt
- **Verify**: Run `node -v` and `npm -v`

### 3. Apache Maven (Required for Java GUI)
- **Purpose**: Build tool for Java project
- **Download**: https://maven.apache.org/download.cgi
- **Installation**:
  1. Extract ZIP to `C:\Program Files\Apache\Maven`
  2. Add to PATH: `C:\Program Files\Apache\Maven\bin`
  3. Restart your terminal/command prompt
- **Verify**: Run `mvn -version`

---

## Automated Setup (After Installing Tools)

Once you've installed Composer and Node.js, simply run:

```batch
cd c:\xampp\htdocs\smart-discussion-forum\laravel
setup.bat
```

This will automatically:
- ✅ Install all Composer dependencies
- ✅ Install all NPM dependencies  
- ✅ Build frontend assets
- ✅ Run database migrations

---

## Manual Setup (Alternative)

If you prefer to run commands manually:

### 1. Ensure XAMPP MySQL is Running
- Open XAMPP Control Panel
- Start Apache
- Start MySQL

### 2. Create Database
- Go to http://localhost/phpmyadmin
- Create new database: `smart_discussion_forum`
- Collation: `utf8mb4_unicode_ci`

### 3. Run Setup Commands
```bash
cd c:\xampp\htdocs\smart-discussion-forum\laravel

# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install

# Build frontend assets
npm run build

# Run database migrations
php artisan migrate
```

---

## After Setup Completes

### Start Laravel Development Server
```bash
cd c:\xampp\htdocs\smart-discussion-forum\laravel
php artisan serve
```

Visit: http://localhost:8000

### Build Java GUI (After Maven is installed)
```bash
cd c:\xampp\htdocs\smart-discussion-forum\java-gui
mvn clean install
```

### Run Java GUI
```bash
cd c:\xampp\htdocs\smart-discussion-forum\java-gui
java -jar target\smart-discussion-forum-gui-1.0.0-SNAPSHOT.jar
```

---

## Quick Install Links

| Tool | Download Link |
|------|--------------|
| Composer | https://getcomposer.org/Composer-Setup.exe |
| Node.js | https://nodejs.org/en/download/ |
| Maven | https://maven.apache.org/download.cgi |

---

**Action Required**: Install Composer and Node.js first, then run `setup.bat`
