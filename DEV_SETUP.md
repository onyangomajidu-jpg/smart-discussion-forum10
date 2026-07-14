# Smart Discussion Forum - Development Setup Guide

## Table of Contents
1. [Prerequisites](#prerequisites)
2. [Backend Setup (Laravel)](#backend-setup-laravel)
3. [Java GUI Client Setup](#java-gui-client-setup)
4. [Database Setup](#database-setup)
5. [Running the Application](#running-the-application)
6. [Troubleshooting](#troubleshooting)

---

## Prerequisites

### Required Software

#### 1. XAMPP (Includes Apache, MySQL, PHP)
- **Version**: XAMPP for Windows (includes PHP 8.2+)
- **Download**: https://www.apachefriends.org/download.html
- **Installation Path**: Default (`C:\xampp`)

#### 2. PHP 8.2+
- Included with XAMPP
- Verify installation:
  ```bash
  php -v
  ```
  Should show PHP 8.2.x or higher

#### 3. Composer (PHP Dependency Manager)
- **Download**: https://getcomposer.org/Composer-Setup.exe
- Verify installation:
  ```bash
  composer --version
  ```

#### 4. Node.js & NPM
- **Version**: Node.js 18+ LTS
- **Download**: https://nodejs.org/
- Verify installation:
  ```bash
  node -v
  npm -v
  ```

#### 5. Java Development Kit (JDK)
- **Version**: JDK 21
- **Download**: https://adoptium.net/ (Eclipse Temurin)
- Set `JAVA_HOME` environment variable
- Verify installation:
  ```bash
  java -version
  javac -version
  ```

#### 6. Apache Maven
- **Version**: Maven 3.9+
- **Download**: https://maven.apache.org/download.cgi
- Add to PATH environment variable
- Verify installation:
  ```bash
  mvn -version
  ```

---

## Backend Setup (Laravel)

### 1. Start XAMPP Services
1. Open **XAMPP Control Panel**
2. Start **Apache** service
3. Start **MySQL** service

### 2. Install PHP Dependencies
Navigate to the Laravel directory:
```bash
cd c:\xampp\htdocs\smart-discussion-forum\laravel
composer install
```

### 3. Environment Configuration
The `.env` file is already configured. Key settings:
```env
APP_NAME="Smart Discussion Forum"
APP_URL=http://localhost
DB_CONNECTION=mysql
DB_DATABASE=smart_discussion_forum
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Generate Application Key
```bash
php artisan key:generate
```

### 5. Install Node Dependencies
```bash
npm install
```

### 6. Build Frontend Assets
```bash
npm run build
```

---

## Database Setup

### 1. Create Database
1. Open browser and go to: http://localhost/phpmyadmin
2. Click **New** in left sidebar
3. Database name: `smart_discussion_forum`
4. Collation: `utf8mb4_unicode_ci`
5. Click **Create**

### 2. Run Migrations
```bash
cd c:\xampp\htdocs\smart-discussion-forum\laravel
php artisan migrate
```

### 3. (Optional) Seed Database
```bash
php artisan db:seed
```

---

## Java GUI Client Setup

### 1. Navigate to Java Project
```bash
cd c:\xampp\htdocs\smart-discussion-forum\java-gui
```

### 2. Build the Project
```bash
mvn clean install
```

This will:
- Download all dependencies (SQLite, Jackson, OkHttp)
- Compile the Java source code
- Run unit tests
- Create executable JAR file

### 3. Verify Build
After successful build, you should find:
- `target/smart-discussion-forum-gui-1.0.0-SNAPSHOT.jar`

---

## Running the Application

### 1. Start Backend Server
In the Laravel directory:
```bash
cd c:\xampp\htdocs\smart-discussion-forum\laravel
php artisan serve
```
Server will start at: http://localhost:8000

### 2. (Optional) Start Vite Dev Server
For frontend development with hot reload:
```bash
npm run dev
```

### 3. Run Java GUI Client
```bash
cd c:\xampp\htdocs\smart-discussion-forum\java-gui
java -jar target/smart-discussion-forum-gui-1.0.0-SNAPSHOT.jar
```

Or use Maven:
```bash
mvn exec:java
```

---

## Project Structure

```
smart-discussion-forum/
├── laravel/               # Backend API
│   ├── app/
│   ├── database/
│   │   └── database.sqlite  # Development database
│   ├── public/
│   ├── resources/
│   ├── routes/
│   ├── .env               # Environment configuration
│   └── composer.json
│
├── java-gui/             # Desktop client
│   ├── src/
│   │   ├── main/java/
│   │   └── test/java/
│   ├── pom.xml           # Maven configuration
│   └── README.md
│
├── DEV_SETUP.md         # This file
├── README.md
└── SDD.md
```

---

## Development Workflow

### Laravel Development
```bash
# Watch for file changes and auto-rebuild
npm run dev

# Run tests
php artisan test

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Java GUI Development
```bash
# Compile and run
mvn clean compile exec:java

# Run tests
mvn test

# Create distributable JAR
mvn package
```

---

## Troubleshooting

### Issue: "composer: command not found"
**Solution**: Ensure Composer is installed and added to PATH. Restart terminal after installation.

### Issue: "php artisan serve" fails
**Solution**: 
- Check if port 8000 is already in use
- Use alternative port: `php artisan serve --port=8001`

### Issue: Database connection error
**Solution**:
- Verify MySQL is running in XAMPP Control Panel
- Check database name in `.env` matches the created database
- Ensure `DB_PASSWORD` is empty (default XAMPP)

### Issue: Maven build fails
**Solution**:
- Verify Java 21 is installed: `java -version`
- Check `JAVA_HOME` environment variable is set
- Clear Maven cache: `mvn clean`

### Issue: "Class not found" when running Java JAR
**Solution**: Use the shaded JAR created by `mvn package`:
```bash
java -jar target/smart-discussion-forum-gui-1.0.0-SNAPSHOT.jar
```

### Issue: Node/NPM errors
**Solution**:
- Delete `node_modules` folder and `package-lock.json`
- Run `npm install` again
- Clear NPM cache: `npm cache clean --force`

---

## API Endpoints

Once the Laravel server is running, the following endpoints are available:

- **Base URL**: http://localhost:8000
- **API Documentation**: http://localhost:8000/api/documentation (if configured)

Example endpoints:
- `GET /api/discussions` - List all discussions
- `POST /api/discussions` - Create new discussion
- `GET /api/discussions/{id}` - Get discussion details
- `POST /api/posts` - Create post in discussion

---

## Next Steps

After completing the setup:

1. ✅ Verify Laravel server is running: http://localhost:8000
2. ✅ Verify database connection in phpMyAdmin
3. ✅ Test Java GUI can connect to API
4. 📖 Refer to SDD.md for architecture and design details
5. 🔨 Start development!

---

## Support

For issues or questions:
1. Check the [Troubleshooting](#troubleshooting) section
2. Review Laravel documentation: https://laravel.com/docs
3. Review Maven documentation: https://maven.apache.org/guides/

---

**Last Updated**: January 2025
**Project**: Smart Discussion Forum
**Environment**: Windows + XAMPP + Laravel 12 + Java 21
**System Review**:Frontend dashboard architecture verified.