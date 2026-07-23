<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('images/forum-favicon.png') }}">
    <title>Login - Discussion Hub</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: white;
            border-radius: 15px;
            padding: 50px 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .logo {
            text-align: center;
            margin-bottom: 35px;
        }

        .logo img {
            width: 64px;
            height: 64px;
            object-fit: contain;
            display: block;
            margin: 0 auto 14px;
        }
        .logo h1 { color: #667eea; font-size: 22px; font-weight: 700; margin-bottom: 6px; }
        .logo p  { color: #6c757d; font-size: 14px; }

        .form-group { margin-bottom: 20px; }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e4e8;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group input.error { border-color: #dc3545; }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-right: 44px;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #6c757d;
            padding: 0;
            display: flex;
            align-items: center;
        }

        .toggle-password:hover { color: #667eea; }

        .checkbox-group input[type="checkbox"] {
            accent-color: #667eea;
        }

        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success { background: #d4edda; color: #155724; }
        .alert-danger  { background: #f8d7da; color: #721c24; }

        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-right: 8px;
            cursor: pointer;
        }

        .checkbox-group label {
            color: #6c757d;
            font-size: 14px;
            cursor: pointer;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .links {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }

        .links a { color: #667eea; text-decoration: none; }
        .links a:hover { text-decoration: underline; }

        .divider {
            border-top: 1px solid #e1e4e8;
            margin: 20px 0;
        }
    
    /* ── Page-load overlay ── */
    #page-loader {
        display:none; position:fixed; inset:0; z-index:99998;
        background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
        align-items:center; justify-content:center; flex-direction:column; gap:18px;
    }
    #page-loader.show { display:flex; }
    .pl-logo { width:64px; height:64px; border-radius:16px; background:rgba(255,255,255,.15); display:flex; align-items:center; justify-content:center; border:2px solid rgba(255,255,255,.3); }
    .pl-logo img { width:44px; height:44px; object-fit:contain; filter:drop-shadow(0 2px 6px rgba(0,0,0,.3)); }
    .pl-spinner { width:40px; height:40px; border:3px solid rgba(255,255,255,.25); border-top-color:#fff; border-radius:50%; animation:plSpin .7s linear infinite; }
    @keyframes plSpin { to { transform:rotate(360deg); } }
    .pl-text { color:rgba(255,255,255,.85); font-size:14px; font-weight:600; letter-spacing:.3px; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo">
            <img src="{{ asset('images/forum.png') }}" alt="Discussion Hub">
            <h1>Discussion Hub</h1>
            <p>Welcome back! Please login to your account</p>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST" id="loginForm">
            @csrf

            <div class="form-group">
                <label for="email">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    class="{{ $errors->has('email') ? 'error' : '' }}"
                    required
                    autofocus
                    placeholder="Enter your email"
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="{{ $errors->has('password') ? 'error' : '' }}"
                        required
                        placeholder="Enter your password"
                    >
                    <button type="button" class="toggle-password" onclick="togglePassword('password', this)" tabindex="-1">
                        <svg id="eye-password" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </div>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" id="remember" name="remember" value="1">
                <label for="remember">Remember me</label>
            </div>

            <button type="submit" class="btn-login">Login</button>

            <div class="links" style="margin-top: 15px;">
                <a href="{{ route('password.request') }}">Forgot your password?</a>
            </div>

            <div class="divider"></div>

            <div class="links">
                <span style="color: #6c757d;">Don't have an account?</span>
                <a href="{{ route('register') }}"> Register here</a>
            </div>
        </form>
    </div>
    <script>
        function togglePassword(fieldId, btn) {
            const input = document.getElementById(fieldId);
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            btn.querySelector('svg').style.opacity = isHidden ? '0.5' : '1';
        }

        // Refresh CSRF token before submit to prevent 419 on stale/expired sessions
        document.getElementById('loginForm').addEventListener('submit', function (e) {
            e.preventDefault();
            var form = this;
            fetch('/sanctum/csrf-cookie', { credentials: 'same-origin' })
                .catch(function () {})
                .finally(function () {
                    fetch('/csrf-token', { credentials: 'same-origin' })
                        .then(function (r) { return r.ok ? r.json() : null; })
                        .then(function (data) {
                            if (data && data.token) {
                                form.querySelector('input[name="_token"]').value = data.token;
                            }
                        })
                        .catch(function () {})
                        .finally(function () { form.submit(); });
                });
        });
    </script>

<div id="page-loader">
    <div class="pl-logo"><img src="{{ asset('images/forum.png') }}" alt=""></div>
    <div class="pl-spinner"></div>
    <div class="pl-text">Loading…</div>
</div>
<script>
(function(){
    var loader = document.getElementById('page-loader');
    document.addEventListener('click', function(e) {
        var a = e.target.closest('a[href]');
        if (!a) return;
        var href = a.getAttribute('href');
        if (!href || href === '#' || href.startsWith('javascript') ||
            href.startsWith('http') || href.startsWith('//') ||
            a.hasAttribute('download') || a.target === '_blank') return;
        loader.classList.add('show');
    });
    document.addEventListener('submit', function(e) {
        if (e.target.id === 'loginForm') return;
        loader.classList.add('show');
    });
    window.addEventListener('pageshow', function() { loader.classList.remove('show'); });
    setInterval(function() { fetch('/api/ping', {credentials:'same-origin'}).catch(function(){}); }, 240000);
})();
</script>
</body>
</html>
