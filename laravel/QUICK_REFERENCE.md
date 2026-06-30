# Quick Reference - Laravel Authentication System

## 🚀 Quick Start

### 1. Setup
```bash
cd c:\xampp\htdocs\smart-discussion-forum\laravel
setup-auth.bat
```

### 2. Create Test Users
```bash
php artisan db:seed --class=AuthenticationSeeder
```

### 3. Start Server
```bash
php artisan serve
```

### 4. Visit Application
```
http://localhost:8000
```

---

## 🔑 Test Credentials

| Role     | Email                   | Password |
|----------|-------------------------|----------|
| Member   | student@example.com     | password |
| Lecturer | lecturer@example.com    | password |
| Admin    | admin@example.com       | password |

---

## 📍 Routes

| Route                           | Purpose              |
|---------------------------------|----------------------|
| GET  `/login`                   | Login form           |
| POST `/login`                   | Authenticate user    |
| POST `/logout`                  | Logout user          |
| GET  `/register`                | Registration form    |
| POST `/register`                | Create account       |
| GET  `/forgot-password`         | Forgot password form |
| POST `/forgot-password`         | Send reset link      |
| GET  `/reset-password/{token}`  | Reset form           |
| POST `/reset-password`          | Update password      |
| GET  `/dashboard`               | User dashboard       |

---

## 💻 Using IAuthentication Interface

### In Controllers

```php
use App\Contracts\IAuthentication;

class MyController extends Controller
{
    protected IAuthentication $auth;

    public function __construct(IAuthentication $auth)
    {
        $this->auth = $auth;
    }

    public function register(Request $request)
    {
        // Register new user
        $user = $this->auth->register(
            $request->all(),
            $request->boolean('accept_rules')
        );
    }

    public function login(Request $request)
    {
        // Login user
        $success = $this->auth->login(
            $request->only('email', 'password'),
            $request->boolean('remember')
        );
    }

    public function logout(Request $request)
    {
        // Logout user
        $this->auth->logout($request);
    }

    public function resetPassword(Request $request)
    {
        // Send reset link
        $status = $this->auth->resetPassword($request->email);
    }
}
```

---

## 🛡️ Role-Based Middleware

### Protect Routes

```php
// Member only
Route::middleware(['auth', 'member'])->group(function () {
    Route::get('/topics', [TopicController::class, 'index']);
});

// Lecturer only
Route::middleware(['auth', 'lecturer'])->group(function () {
    Route::get('/quizzes/create', [QuizController::class, 'create']);
});

// Admin only
Route::middleware(['auth', 'administrator'])->group(function () {
    Route::get('/admin/users', [AdminController::class, 'users']);
});
```

### Check Roles in Code

```php
// Check role
if (auth()->user()->isMember()) {
    // Member logic
}

if (auth()->user()->isLecturer()) {
    // Lecturer logic
}

if (auth()->user()->isAdmin()) {
    // Admin logic
}

// Get role
$role = auth()->user()->role; // 'member', 'lecturer', or 'admin'
```

---

## 📦 Session Management

### Using SessionManager

```php
use App\Services\SessionManager;

class MyController extends Controller
{
    protected SessionManager $session;

    public function __construct(SessionManager $session)
    {
        $this->session = $session;
    }

    public function example()
    {
        // Store data
        $this->session->put('key', 'value');
        
        // Get data
        $value = $this->session->get('key', 'default');
        
        // Flash message
        $this->session->flash('success', 'Saved!');
        
        // Check if exists
        if ($this->session->has('key')) {
            // ...
        }
        
        // Remove data
        $this->session->forget('key');
        
        // Get all user sessions
        $sessions = $this->session->getUserSessions(auth()->id());
        
        // Terminate other sessions
        $this->session->terminateOtherSessions(
            auth()->id(),
            $this->session->getCurrentSessionId()
        );
    }
}
```

---

## 🔍 Common Tasks

### Register New User Programmatically

```php
use App\Contracts\IAuthentication;

$auth = app(IAuthentication::class);

$user = $auth->register([
    'name' => 'New User',
    'email' => 'user@example.com',
    'password' => 'SecurePass123!',
    'role' => 'member',
    'student_id' => 'STU123',
    'programme' => 'Computer Science',
    'year_of_study' => 1,
], true); // true = accepted forum rules
```

### Login User Programmatically

```php
use App\Contracts\IAuthentication;

$auth = app(IAuthentication::class);

$success = $auth->login([
    'email' => 'user@example.com',
    'password' => 'password'
], true); // true = remember me

if ($success) {
    // User logged in
}
```

### Check Forum Rules Acceptance

```php
use App\Contracts\IAuthentication;

$auth = app(IAuthentication::class);

if ($auth->hasAcceptedForumRules(auth()->id())) {
    // User has accepted forum rules
}
```

---

## 🎨 Blade Directives

### Check Authentication

```blade
@auth
    <p>Welcome, {{ auth()->user()->name }}!</p>
@endauth

@guest
    <a href="{{ route('login') }}">Login</a>
@endguest
```

### Display Errors

```blade
@if ($errors->any())
    <div class="alert alert-danger">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif
```

### Display Flash Messages

```blade
@if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif
```

### CSRF Token

```blade
<form method="POST" action="/login">
    @csrf
    <!-- form fields -->
</form>
```

---

## 🐛 Debugging

### Check if User is Logged In

```php
dd(auth()->check());  // true or false
dd(auth()->user());   // User object or null
dd(auth()->id());     // User ID or null
```

### Check User Role

```php
dd(auth()->user()->role);          // 'member', 'lecturer', 'admin'
dd(auth()->user()->isMember());    // true or false
dd(auth()->user()->isLecturer());  // true or false
dd(auth()->user()->isAdmin());     // true or false
```

### View Session Data

```php
dd(session()->all());  // All session data
dd(session('key'));    // Specific key
```

---

## 📝 Validation Rules

### Registration

```php
'name' => 'required|string|max:255',
'email' => 'required|email|unique:users',
'password' => 'required|string|min:8|confirmed',
'role' => 'required|in:member,lecturer,admin',
'accept_rules' => 'required|accepted',
```

### Login

```php
'email' => 'required|email',
'password' => 'required|string',
```

### Password Reset

```php
'email' => 'required|email|exists:users',
'password' => 'required|string|min:8|confirmed',
'token' => 'required',
```

---

## ⚠️ Troubleshooting

### Issue: 404 Not Found on Routes
**Solution:** Clear route cache
```bash
php artisan route:clear
php artisan route:cache
```

### Issue: Session Not Persisting
**Solution:** Check .env file
```env
SESSION_DRIVER=database
```
Then: `php artisan migrate`

### Issue: Password Reset Email Not Sending
**Solution:** Configure mail in .env
```env
MAIL_MAILER=log  # For testing, check storage/logs
```

### Issue: "Class not found" errors
**Solution:** Regenerate autoload
```bash
composer dump-autoload
```

---

## 📚 Useful Artisan Commands

```bash
# Clear all caches
php artisan optimize:clear

# View routes
php artisan route:list

# View routes with specific name
php artisan route:list --name=login

# Create migration
php artisan make:migration create_something_table

# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Seed database
php artisan db:seed

# Create seeder
php artisan make:seeder SomethingSeeder
```

---

## 🎯 Next Features to Implement

- [ ] Email verification
- [ ] Two-factor authentication
- [ ] Social login (Google, Facebook)
- [ ] API authentication (Sanctum)
- [ ] Rate limiting
- [ ] Login history
- [ ] Device management
- [ ] Security notifications

---

**Need Help?** Check `AUTHENTICATION_GUIDE.md` for detailed documentation.
