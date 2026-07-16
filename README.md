# Smart Discussion Forum

A full-stack academic discussion platform built with **Laravel 11** (web + REST API) and a **Java 21 Swing** desktop client with offline-first SQLite caching.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Web backend | Laravel 11, PHP 8.2, MySQL |
| REST API | Laravel Sanctum (token auth) |
| Real-time | Laravel Reverb (WebSocket) |
| Java desktop | Java 21, Swing, OkHttp, JFreeChart, SQLite |
| Build | Maven (fat JAR via maven-shade-plugin) |
| Tests | PHPUnit / Laravel Feature Tests |

---

## Features

- **Registration & Login** — role-based (student, lecturer, admin), forum rules acceptance gate
- **Discussion Topics** — create, participate, answer, pin, lock, block users
- **Quiz Lifecycle** — lecturer creates draft → publishes → students attempt → auto-submit on timer expiry → marks assigned → participation record
- **AI Recommendations** — personalised topic suggestions based on participation history
- **Moderation** — issue warnings, blacklist users with expiry, enforced on every request via middleware
- **Statistics** — per-user engagement metrics, bar/pie charts (web + Java desktop)
- **Report Export** — discussion PDF export, social media share
- **Java Desktop Client** — topic list, real-time conversation panel, offline message queue, sync on reconnect, statistics panel with offline cache fallback

---

## Project Structure

```
smart-discussion-forum10/
├── laravel/          # Laravel web application & API
│   ├── app/
│   ├── database/
│   ├── resources/views/
│   ├── routes/
│   └── tests/
└── java-gui/         # Java Swing desktop client
    └── src/main/java/com/smartforum/
```

---

## Setup — Laravel

```bash
cd laravel
cp .env.example .env          # configure DB_DATABASE, DB_USERNAME, DB_PASSWORD
composer install
php artisan key:generate
php artisan migrate
php artisan serve
```

Visit `http://localhost:8000`

---

## Setup — Java Desktop

```bash
cd java-gui
mvn package -DskipTests
java -jar target\smart-discussion-forum-gui-1.0.0-SNAPSHOT.jar
```

Requires Laravel server running on `http://localhost:8000`.

---

## Running Tests

```bash
cd laravel
php artisan test --no-coverage
```

14 integration tests covering:
- Web → API post visibility (T1)
- Offline sync on reconnect (T2)
- Full quiz lifecycle (T3)
- Moderation & blacklist enforcement (T4)

---

## Demo Walkthrough

1. **Register** at `/register` — accept forum rules, select role
2. **Login** — redirected to role-specific dashboard
3. **Topics** — create a topic, post a message, reply
4. **Quiz** — lecturer creates & publishes quiz; student takes it with live countdown timer
5. **Moderation** — admin issues 2 warnings → blacklists user → banned user cannot log in
6. **AI Recommendations** — visible on student dashboard after first participation
7. **Export** — download discussion as PDF from topics screen
8. **Java Desktop** — login, view topics synced from API, post offline, reconnect to sync

---

## Default Roles

| Role | Access |
|---|---|
| `member` | Dashboard, Topics, Quizzes, Statistics |
| `lecturer` | + Quiz management, Lecturer analytics |
| `admin` | + Warnings, Blacklists, Admin dashboard |
