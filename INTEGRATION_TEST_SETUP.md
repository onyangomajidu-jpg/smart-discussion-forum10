# Integration Test Setup & Execution Guide

## Quick Start

### 1. Prepare Environment

```bash
cd laravel

# Install dependencies
composer install

# Copy environment file
cp .env.example .env.testing

# Generate app key
php artisan key:generate --env=testing

# Create test database
php artisan migrate --env=testing --database=testing
```

### 2. Configure Testing Database

Edit `.env.testing`:

```env
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

Or use file-based SQLite:

```env
DB_CONNECTION=sqlite
DB_DATABASE=database/testing.sqlite
```

### 3. Run Integration Tests

```bash
# All tests
php artisan test tests/Feature/IntegrationAuthFlowTest.php

# Specific test
php artisan test tests/Feature/IntegrationAuthFlowTest.php --filter=test_api_login_returns_token

# With verbose output
php artisan test tests/Feature/IntegrationAuthFlowTest.php -v

# With coverage report
php artisan test tests/Feature/IntegrationAuthFlowTest.php --coverage
```

---

## Test Execution Flow

```
┌─────────────────────────────────────────────────────────┐
│ IntegrationAuthFlowTest                                 │
├─────────────────────────────────────────────────────────┤
│                                                         │
│ setUp()                                                 │
│ ├─ Create test user (integration@test.com)             │
│ └─ Hash password (password123)                         │
│                                                         │
│ Test 1: Web Login                                       │
│ ├─ POST /login                                          │
│ └─ Assert: authenticated, redirected to /dashboard     │
│                                                         │
│ Test 2: Web Dashboard                                   │
│ ├─ GET /dashboard (authenticated)                       │
│ └─ Assert: view is 'dashboard', user data present      │
│                                                         │
│ Test 3: API Login                                       │
│ ├─ POST /api/login (JSON)                              │
│ └─ Assert: token returned, user payload valid          │
│                                                         │
│ Test 4: API Dashboard (Unauthenticated)                │
│ ├─ GET /api/dashboard (no token)                        │
│ └─ Assert: 401 Unauthorized                            │
│                                                         │
│ Test 5: API Dashboard (Authenticated)                   │
│ ├─ GET /api/dashboard (with token)                      │
│ └─ Assert: stats returned, structure valid             │
│                                                         │
│ Test 6: API Login Failure                               │
│ ├─ POST /api/login (wrong password)                     │
│ └─ Assert: 401, error message                          │
│                                                         │
│ Test 7: API Logout                                      │
│ ├─ POST /api/logout (with token)                        │
│ ├─ GET /api/dashboard (revoked token)                   │
│ └─ Assert: 401 after logout                            │
│                                                         │
│ Test 8: Web Logout                                      │
│ ├─ POST /logout (authenticated)                         │
│ └─ Assert: guest, redirected to /login                 │
│                                                         │
│ Test 9: Java GUI Login Flow                             │
│ ├─ POST /api/login → get token                          │
│ ├─ GET /api/dashboard (with token)                      │
│ └─ Assert: complete flow succeeds                       │
│                                                         │
│ Test 10: Data Consistency                               │
│ ├─ GET /dashboard (web)                                 │
│ ├─ GET /api/dashboard (API)                             │
│ └─ Assert: same stats structure                         │
│                                                         │
│ Test 11: Ping Endpoint                                  │
│ ├─ GET /api/ping (no auth)                              │
│ └─ Assert: 200, status ok                              │
│                                                         │
│ Test 12: Role-Based Access                              │
│ ├─ Member: GET /dashboard → 200                         │
│ └─ Assert: role-based routing works                     │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

---

## Expected Output

```
   PASS  tests/Feature/IntegrationAuthFlowTest.php
  ✓ test web login creates session
  ✓ test web dashboard loads
  ✓ test api login returns token
  ✓ test api dashboard requires auth
  ✓ test api dashboard returns stats
  ✓ test api login invalid credentials
  ✓ test api logout revokes token
  ✓ test web logout clears session
  ✓ test java gui login flow
  ✓ test dashboard data consistency
  ✓ test ping endpoint
  ✓ test role based dashboard access

Tests:  12 passed (48 assertions)
Duration: 2.45s
```

---

## Troubleshooting

### Issue: "SQLSTATE[HY000]: General error: 1 no such table"

**Cause:** Test database not migrated

**Fix:**
```bash
php artisan migrate --env=testing
```

---

### Issue: "Call to undefined method DashboardApiController"

**Cause:** Controller not found

**Fix:**
```bash
# Verify file exists
ls laravel/app/Http/Controllers/Api/DashboardApiController.php

# Clear cache
php artisan cache:clear
php artisan config:clear
```

---

### Issue: "Unauthenticated" on API calls

**Cause:** Sanctum not configured

**Fix:**
```php
// config/sanctum.php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost,127.0.0.1')),
```

---

### Issue: "CORS error" in Java GUI

**Cause:** CORS headers not set

**Fix:**
```php
// config/cors.php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_methods' => ['*'],
'allowed_origins' => ['*'],
'allowed_headers' => ['*'],
```

---

### Issue: "Token not persisting"

**Cause:** AuthService not storing token

**Fix:**
```java
// AuthService.java
public void setToken(String token) {
    this.token = token;
    cache.saveToken(token);  // Add persistence
}
```

---

## Manual Testing (Without PHPUnit)

### Test Web Login

```bash
# Start Laravel server
php artisan serve

# In browser: http://localhost:8000/login
# Email: integration@test.com
# Password: password123
# Expected: Redirect to /dashboard
```

### Test API Login

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "integration@test.com",
    "password": "password123"
  }'

# Expected response:
# {
#   "token": "1|abc123xyz...",
#   "user": {
#     "id": 1,
#     "name": "Test User",
#     "email": "integration@test.com",
#     "role": "member"
#   }
# }
```

### Test API Dashboard

```bash
TOKEN="1|abc123xyz..."

curl -X GET http://localhost:8000/api/dashboard \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json"

# Expected response:
# {
#   "stats": {
#     "topicsParticipated": 0,
#     "totalPosts": 0,
#     "quizAttempts": 0,
#     "availableQuizzes": 0,
#     "avgScore": null,
#     "recentTopics": [],
#     "recentAttempts": []
#   }
# }
```

### Test Ping

```bash
curl http://localhost:8000/api/ping

# Expected response:
# {"status":"ok"}
```

---

## Java GUI Testing

### 1. Build Java Project

```bash
cd java-gui
mvn clean package
```

### 2. Run Java GUI

```bash
java -jar target/smartforum-gui.jar
```

### 3. Test Login Flow

1. Enter email: `integration@test.com`
2. Enter password: `password123`
3. Click "Login"
4. Expected: MainWindow opens with dashboard data

### 4. Verify Dashboard Data

- Check stat cards populate
- Verify progress bars show values
- Confirm topic/quiz lists display

---

## CI/CD Integration

### GitHub Actions Example

```yaml
name: Integration Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_DATABASE: testing
          MYSQL_ROOT_PASSWORD: root
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3

    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.1
          extensions: pdo_mysql
      
      - name: Install dependencies
        run: cd laravel && composer install
      
      - name: Run tests
        run: cd laravel && php artisan test tests/Feature/IntegrationAuthFlowTest.php
```

---

## Performance Benchmarks

Run with timing:

```bash
php artisan test tests/Feature/IntegrationAuthFlowTest.php -v --profile
```

Expected timings:
- Web login: ~50-100ms
- API login: ~30-50ms
- Dashboard load: ~100-200ms
- Total suite: ~2-3 seconds

---

## Next Steps

After all tests pass:

1. ✅ Deploy to staging
2. ✅ Run smoke tests
3. ✅ Test with real data
4. ✅ Performance testing
5. ✅ Security audit
6. ✅ Deploy to production

---

## Support

For issues or questions:

1. Check WEEK1_INTEGRATION_TEST.md for detailed scenarios
2. Review test code: `tests/Feature/IntegrationAuthFlowTest.php`
3. Check Laravel logs: `storage/logs/laravel.log`
4. Check Java logs: Console output in IDE
