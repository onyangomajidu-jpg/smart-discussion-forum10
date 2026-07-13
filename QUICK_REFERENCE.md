# Developer Quick Reference — Days 6-7

## Common Commands

### Laravel

```bash
# Start development server
php artisan serve

# Run integration tests
php artisan test tests/Feature/IntegrationAuthFlowTest.php

# Run specific test
php artisan test tests/Feature/IntegrationAuthFlowTest.php --filter=test_api_login_returns_token

# Clear cache
php artisan cache:clear && php artisan config:clear

# Migrate database
php artisan migrate

# Seed database
php artisan db:seed

# Tinker (interactive shell)
php artisan tinker
```

### Java GUI

```bash
# Build project
cd java-gui && mvn clean package

# Run GUI
java -jar target/smartforum-gui.jar

# Run tests
mvn test

# View logs
tail -f storage/logs/laravel.log
```

---

## API Endpoints

### Authentication

```bash
# Login (returns token)
POST /api/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123"
}

Response:
{
  "token": "1|abc123xyz...",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "role": "member"
  }
}
```

### Dashboard

```bash
# Get dashboard stats (authenticated)
GET /api/dashboard
Authorization: Bearer {token}

Response:
{
  "stats": {
    "topicsParticipated": 3,
    "totalPosts": 12,
    "quizAttempts": 2,
    "availableQuizzes": 5,
    "avgScore": 78.5,
    "recentTopics": [...],
    "recentAttempts": [...]
  }
}
```

### Connectivity

```bash
# Ping (no auth required)
GET /api/ping

Response:
{
  "status": "ok"
}
```

---

## File Locations

| Component | File |
|-----------|------|
| Web Dashboard | `laravel/resources/views/dashboard.blade.php` |
| API Controller | `laravel/app/Http/Controllers/Api/DashboardApiController.php` |
| Java GUI | `java-gui/src/main/java/com/smartforum/ui/MainWindow.java` |
| Tests | `laravel/tests/Feature/IntegrationAuthFlowTest.php` |
| Routes | `laravel/routes/web.php` |

---

## Database Queries

### User Stats

```sql
-- Topics participated
SELECT COUNT(DISTINCT topic_id) FROM posts WHERE user_id = ?;

-- Total posts
SELECT COUNT(*) FROM posts WHERE user_id = ?;

-- Quiz attempts
SELECT COUNT(*) FROM quiz_attempts WHERE user_id = ?;

-- Average score
SELECT AVG(score) FROM quiz_attempts WHERE user_id = ?;

-- Recent topics
SELECT DISTINCT t.id, t.title FROM posts p
JOIN topics t ON p.topic_id = t.id
WHERE p.user_id = ?
ORDER BY p.created_at DESC
LIMIT 5;

-- Recent attempts
SELECT q.id, q.title, qa.score FROM quiz_attempts qa
JOIN quizzes q ON qa.quiz_id = q.id
WHERE qa.user_id = ?
ORDER BY qa.created_at DESC
LIMIT 5;
```

---

## Debugging Tips

### Laravel

```php
// Log to file
Log::info('Message', ['data' => $data]);

// Dump and die
dd($variable);

// Dump
dump($variable);

// Query debugging
DB::enableQueryLog();
// ... run queries ...
dd(DB::getQueryLog());
```

### Java

```java
// Print to console
System.out.println("Debug: " + value);

// Print error
System.err.println("Error: " + e.getMessage());

// Stack trace
e.printStackTrace();

// Logging
Logger.getLogger(ClassName.class.getName()).info("Message");
```

---

## Common Issues & Fixes

### Issue: 404 on /api/dashboard

**Fix:**
```bash
php artisan cache:clear
php artisan config:clear
# Verify route in routes/web.php
```

### Issue: 401 Unauthorized

**Fix:**
```bash
# Check token is being sent
# Verify token hasn't expired
# Check Sanctum config
```

### Issue: CORS Error

**Fix:**
```php
// config/cors.php
'allowed_origins' => ['*'],
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
```

### Issue: Java GUI can't connect

**Fix:**
```java
// Check API base URL
System.setProperty("api.baseUrl", "http://localhost:8000/api");

// Verify server is running
curl http://localhost:8000/api/ping
```

### Issue: Stats showing 0

**Fix:**
```sql
-- Check if data exists
SELECT * FROM posts WHERE user_id = 1;
SELECT * FROM quiz_attempts WHERE user_id = 1;

-- Verify queries
SELECT COUNT(DISTINCT topic_id) FROM posts WHERE user_id = 1;
```

---

## Test Execution

### Run All Tests

```bash
cd laravel
php artisan test tests/Feature/IntegrationAuthFlowTest.php
```

### Run Single Test

```bash
php artisan test tests/Feature/IntegrationAuthFlowTest.php --filter=test_api_login_returns_token
```

### With Verbose Output

```bash
php artisan test tests/Feature/IntegrationAuthFlowTest.php -v
```

### With Coverage

```bash
php artisan test tests/Feature/IntegrationAuthFlowTest.php --coverage
```

---

## Manual API Testing

### Using curl

```bash
# Login
TOKEN=$(curl -s -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password123"}' \
  | jq -r '.token')

# Get dashboard
curl -X GET http://localhost:8000/api/dashboard \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" | jq

# Ping
curl http://localhost:8000/api/ping | jq
```

### Using Postman

1. Create collection "SmartForum"
2. Add requests:
   - POST /api/login
   - GET /api/dashboard
   - GET /api/ping
3. Set Authorization: Bearer {{token}}
4. Use pre-request script to extract token

---

## Performance Optimization

### Database

```php
// Use eager loading
$users = User::with('posts', 'quizAttempts')->get();

// Use select to limit columns
$posts = Post::select('id', 'title', 'user_id')->get();

// Use indexes
Schema::table('posts', function (Blueprint $table) {
    $table->index('user_id');
    $table->index('topic_id');
});
```

### API Response

```php
// Cache dashboard stats
Cache::remember("dashboard.{$user->id}", 300, function () {
    return $this->calculateStats();
});
```

### Java GUI

```java
// Load data in background thread
SwingWorker<JsonNode, Void> worker = new SwingWorker<>() {
    @Override protected JsonNode doInBackground() throws Exception {
        return api.get("/dashboard");
    }
};
worker.execute();
```

---

## Security Checklist

- [ ] Passwords hashed (bcrypt)
- [ ] Tokens use Sanctum
- [ ] API requires auth
- [ ] CSRF protection enabled
- [ ] Rate limiting configured
- [ ] Session timeout set
- [ ] Token expiration set
- [ ] SQL injection prevented (use ORM)
- [ ] XSS protection enabled
- [ ] CORS properly configured

---

## Useful Links

- Laravel Docs: https://laravel.com/docs
- Sanctum: https://laravel.com/docs/sanctum
- PHPUnit: https://phpunit.de/
- Java Swing: https://docs.oracle.com/javase/tutorial/uiswing/
- Postman: https://www.postman.com/

---

## Environment Variables

### .env

```env
APP_NAME="Smart Discussion Forum"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
SESSION_DOMAIN=localhost
```

### .env.testing

```env
APP_ENV=testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

---

## Git Workflow

```bash
# Create feature branch
git checkout -b feature/dashboard-integration

# Make changes
git add .
git commit -m "feat: implement dashboard integration"

# Push to remote
git push origin feature/dashboard-integration

# Create pull request
# Review & merge
```

---

## Deployment

### Staging

```bash
git push origin main
# CI/CD runs tests
# Deploy to staging
# Run smoke tests
```

### Production

```bash
# After staging approval
git tag v1.0.0
git push origin v1.0.0
# CI/CD deploys to production
```

---

## Support Resources

- **Documentation:** See WEEK1_INTEGRATION_TEST.md
- **Setup Guide:** See INTEGRATION_TEST_SETUP.md
- **Summary:** See DAYS_6_7_SUMMARY.md
- **Code:** Check inline comments in source files
- **Tests:** Run tests with -v flag for details

---

## Quick Stats

- **Tests:** 12 comprehensive tests
- **Coverage:** 48 assertions
- **Execution Time:** ~2-3 seconds
- **Files Modified:** 4
- **Files Created:** 5
- **Lines of Code:** ~1500+
- **Documentation:** 3 guides

---

**Last Updated:** Week 1, Days 6-7
**Version:** 1.0
**Status:** Ready for Development ✅
