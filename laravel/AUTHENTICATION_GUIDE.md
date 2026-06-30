# Laravel Authentication & User Management System
## Smart Discussion Forum - Days 2-4 Implementation

## 📋 Overview

This implementation provides a complete authentication system with:
- User registration with Eloquent ORM
- IAuthentication interface implementation
- Forum rules acceptance gate
- Role-based middleware (Member, Lecturer, Administrator)
- Session management
- Password reset functionality
- Professional UI with forum rules panel

---

## 🏗️ Architecture

### Interface-Based Design
```
IAuthentication (Interface)
    ↓ implements
AuthenticationService (Implementation)
    ↓ uses
User Model (Eloquent ORM)
```

---

## 📁 File Structure

```
laravel/
├── app/
│   ├── Contracts/
│   │   └── IAuthentication.php          # Authentication interface
│   ├── Services/
│   │   ├── AuthenticationService.php    # Interface implementation
│   │   └── SessionManager.php           # Session management
│   ├── Http/
│   │   ├── Controllers/Auth/
│   │   │   ├── RegisterController.php
│   │   │   ├── LoginController.php
│   │   │   └── PasswordResetController.php
│   │   └── Middleware/
│   │       ├── MemberMiddleware.php
│   │       ├── LecturerMiddleware.php
│   │       └── AdministratorMiddleware.php
│   └── Models/
│       ├── User.php
│       ├── Member.php
│       ├── Lecturer.php
│       └── Admin.php
├── database/migrations/
│   └── 2024_01_02_000000_create_forum_rules_acceptances_table.php
└── resources/views/auth/
    ├── login.blade.php
    ├── register.blade.php
    ├── forgot-password.blade.php
    └── reset-password.blade.php
```

---

## 🔑 Key Features

### 1. IAuthentication Interface

Defines core authentication methods:
- `register(array $userData, bool $acceptedRules): User`
- `login(array $credentials, bool $remember): bool`
- `logout(Request $request): void`
- `resetPassword(string $email): string`
- `updatePasswordWithToken(array $credentials): bool`
- `hasAcceptedForumRules(int $userId): bool`

### 2. Forum Rules Acceptance Gate

**Implementation:**
- Checkbox on registration form (REQUIRED)
- Submit button disabled until rules accepted
- Database tracking in `forum_rules_acceptances` table
- Validation in `AuthenticationService::register()`

**Enforcement:**
```php
if (!$acceptedRules) {
    throw new \Exception('You must accept the forum rules before creating an account.');
}
```

### 3. Role-Based Middleware

**Three middleware classes:**
- `MemberMiddleware` - Allows only users with 'member' role
- `LecturerMiddleware` - Allows only users with 'lecturer' role
- `AdministratorMiddleware` - Allows only users with 'admin' role

**Usage in routes:**
```php
Route::middleware(['auth', 'member'])->group(function () {
    // Member-only routes
});
```

### 4. Session Management

**SessionManager Service provides:**
- `getCurrentSessionId()` - Get current session
- `getUserSessions($userId)` - Get all user sessions
- `terminateSession($sessionId)` - End specific session
- `terminateOtherSessions($userId, $currentSessionId)` - End all but current
- `cleanupExpiredSessions()` - Remove old sessions
- `put($key, $value)` - Store session data
- `get($key, $default)` - Retrieve session data
- `flash($key, $value)` - Flash data for next request

---

## 🚀 Setup Instructions

### 1. Run Migrations

```bash
cd c:\xampp\htdocs\smart-discussion-forum\laravel
php artisan migrate
```

This will create:
- users table
- members table
- lecturers table
- admins table
- forum_rules_acceptances table (NEW)
- sessions table
- password_reset_tokens table

### 2. Configure Mail for Password Reset

Edit `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@smartforum.com"
MAIL_FROM_NAME="Smart Discussion Forum"
```

### 3. Configure Session Driver

In `.env`:
```env
SESSION_DRIVER=database
SESSION_LIFETIME=120
```

### 4. Test the Application

Start the server:
```bash
php artisan serve
```

Visit: `http://localhost:8000`

---

## 🔄 Registration Flow

1. User visits `/register`
2. Fills registration form
3. Selects role (Member/Lecturer/Admin)
4. Role-specific fields appear dynamically
5. User reads forum rules in panel
6. **GATE: Must check "I accept forum rules" checkbox**
7. Submit button becomes enabled
8. Form submission validates:
   - Required fields
   - Password complexity
   - **Forum rules acceptance**
   - Role-specific fields
9. `AuthenticationService::register()` creates:
   - User record (Eloquent ORM)
   - Role-specific profile (Member/Lecturer/Admin)
   - Forum rules acceptance record
10. Auto-login after successful registration
11. Redirect to dashboard based on role

---

## 🔐 Login Flow

1. User visits `/login`
2. Enters email and password
3. Optional: Check "Remember me"
4. Can view forum rules in side panel
5. `AuthenticationService::login()` validates:
   - Credentials match
   - Account is active
   - User is not banned
6. Session regenerated for security
7. Redirect based on role:
   - Admin → `/admin/dashboard`
   - Lecturer → `/lecturer/dashboard`
   - Member → `/dashboard`

---

## 🔓 Password Reset Flow

1. User clicks "Forgot Password" on login
2. Enters email address
3. `AuthenticationService::resetPassword()` sends email with token
4. User clicks link in email
5. Visits `/reset-password/{token}`
6. Enters new password (twice)
7. `AuthenticationService::updatePasswordWithToken()` updates password
8. Redirect to login with success message

---

## 🛡️ Middleware Usage Examples

### Protect Routes by Role

```php
// Member-only routes
Route::middleware(['auth', 'member'])->group(function () {
    Route::get('/my-topics', [TopicController::class, 'myTopics']);
});

// Lecturer-only routes
Route::middleware(['auth', 'lecturer'])->group(function () {
    Route::get('/create-quiz', [QuizController::class, 'create']);
});

// Admin-only routes
Route::middleware(['auth', 'administrator'])->group(function () {
    Route::get('/users/manage', [UserController::class, 'manage']);
});
```

### Check Role in Controllers

```php
if (auth()->user()->isMember()) {
    // Member-specific logic
}

if (auth()->user()->isLecturer()) {
    // Lecturer-specific logic
}

if (auth()->user()->isAdmin()) {
    // Admin-specific logic
}
```

---

## 📊 Database Schema

### forum_rules_acceptances
```sql
- id (bigint, PK)
- user_id (bigint, FK → users.id)
- accepted_at (timestamp)
- ip_address (varchar)
- created_at (timestamp)
- updated_at (timestamp)
```

---

## 🎨 UI Features

### Login Screen (Fig 6.1)
- Clean, modern design
- Two-panel layout:
  - **Left Panel:** Login form
    - Email field
    - Password field
    - Remember me checkbox
    - Forgot password link
    - Register link
  - **Right Panel:** Forum rules display
    - Scrollable list
    - Always visible during login
- Gradient background
- Responsive design

### Registration Screen
- Extended form with role selection
- Dynamic role-specific fields
- Forum rules in prominent box
- **Acceptance checkbox (GATE)**
- Submit button state management
- Visual feedback for required fields

---

## 🔍 Testing Checklist

### Registration
- [ ] Can access `/register`
- [ ] All roles available in dropdown
- [ ] Role-specific fields appear correctly
- [ ] Submit button disabled until rules accepted
- [ ] Forum rules gate enforced
- [ ] Validation errors display properly
- [ ] Auto-login after registration works
- [ ] Redirects to correct dashboard

### Login
- [ ] Can access `/login`
- [ ] Forum rules visible in panel
- [ ] Invalid credentials show error
- [ ] Inactive account blocked
- [ ] Banned account blocked
- [ ] Remember me works
- [ ] Role-based redirects work

### Password Reset
- [ ] Can request reset link
- [ ] Email sent successfully
- [ ] Reset token works
- [ ] Password updated correctly
- [ ] Old password no longer works

### Middleware
- [ ] Member middleware blocks non-members
- [ ] Lecturer middleware blocks non-lecturers
- [ ] Admin middleware blocks non-admins
- [ ] Redirects to login if not authenticated

---

## 🔧 Configuration

### Middleware Aliases

In `bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'member' => \App\Http\Middleware\MemberMiddleware::class,
        'lecturer' => \App\Http\Middleware\LecturerMiddleware::class,
        'administrator' => \App\Http\Middleware\AdministratorMiddleware::class,
    ]);
})
```

### Service Binding

In `app/Providers/AppServiceProvider.php`:
```php
public function register(): void
{
    $this->app->bind(IAuthentication::class, AuthenticationService::class);
}
```

---

## 📝 Code Examples

### Using AuthenticationService

```php
use App\Contracts\IAuthentication;

class ExampleController extends Controller
{
    protected IAuthentication $authService;

    public function __construct(IAuthentication $authService)
    {
        $this->authService = $authService;
    }

    public function register(Request $request)
    {
        $user = $this->authService->register(
            $request->all(),
            $request->boolean('accept_rules')
        );
        
        // User created with Eloquent ORM
    }
}
```

### Using SessionManager

```php
use App\Services\SessionManager;

class ExampleController extends Controller
{
    protected SessionManager $sessionManager;

    public function __construct(SessionManager $sessionManager)
    {
        $this->sessionManager = $sessionManager;
    }

    public function store(Request $request)
    {
        // Store data
        $this->sessionManager->put('key', 'value');
        
        // Flash message
        $this->sessionManager->flash('success', 'Action completed!');
        
        // Terminate other sessions
        $this->sessionManager->terminateOtherSessions(
            auth()->id(),
            $this->sessionManager->getCurrentSessionId()
        );
    }
}
```

---

## ⚡ Advanced Features

### 1. Forum Rules Gate Enforcement

The gate is enforced at multiple levels:
- **Frontend:** Disabled submit button
- **Backend:** Validation rule
- **Service:** Business logic check
- **Database:** Tracking acceptance

### 2. Security Features
- Password hashing (bcrypt)
- Session regeneration on login
- CSRF protection
- Rate limiting on login attempts
- Account activation check
- Ban check on login
- Secure password reset tokens

### 3. Role-Based Access Control
- Database-driven roles
- Middleware enforcement
- Helper methods on User model
- Easy to extend with new roles

---

## 🎯 Next Steps

1. **Day 5-7:** Implement forum posting system
2. **Day 8-10:** Add group management
3. **Day 11-14:** Build quiz system
4. **Day 15-17:** Implement moderation tools
5. **Day 18-21:** Add recommendation engine

---

## 📚 References

- Laravel Authentication: https://laravel.com/docs/authentication
- Eloquent ORM: https://laravel.com/docs/eloquent
- Middleware: https://laravel.com/docs/middleware
- Sessions: https://laravel.com/docs/session
- Password Reset: https://laravel.com/docs/passwords

---

## ✅ Deliverables Completed

✅ User registration with Eloquent ORM
✅ IAuthentication interface with methods: register(), login(), logout(), resetPassword()
✅ Forum rules acceptance gate on registration
✅ Role-based middleware: Member, Lecturer, Administrator
✅ Login screen UI with username/password fields, forgot password, forum rules panel (Fig 6.1)
✅ Session management via SessionManager
✅ Password reset functionality
✅ Professional, responsive UI design

---

**Implementation Date:** January 2024
**Laravel Version:** 11.x
**PHP Version:** 8.2+
