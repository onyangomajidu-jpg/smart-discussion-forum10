<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Smart Discussion Forum</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            display: flex;
            max-width: 1000px;
            width: 100%;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .login-form-section {
            flex: 1;
            padding: 50px 40px;
        }
        
        .forum-rules-section {
            flex: 1;
            background: #f8f9fa;
            padding: 50px 40px;
            border-left: 1px solid #dee2e6;
            overflow-y: auto;
            max-height: 600px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            color: #667eea;
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .logo p {
            color: #6c757d;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group input[type="email"],
        .form-group input[type="password"] {
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
        
        .form-group input.error {
            border-color: #dc3545;
        }
        
        .error-message {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .checkbox-group input[type="checkbox"] {
            margin-right: 8px;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .checkbox-group label {
            color: #6c757d;
            font-size: 14px;
            cursor: pointer;
            margin: 0;
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
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .forgot-password {
            text-align: center;
            margin-top: 20px;
        }
        
        .forgot-password a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }
        
        .forgot-password a:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        
        .register-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #e1e4e8;
        }
        
        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
        
        .forum-rules-section h2 {
            color: #333;
            font-size: 22px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        
        .rules-list {
            list-style: none;
            padding: 0;
        }
        
        .rules-list li {
            padding: 12px 0;
            padding-left: 25px;
            position: relative;
            color: #495057;
            line-height: 1.6;
            font-size: 14px;
        }
        
        .rules-list li::before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #667eea;
            font-weight: bold;
            font-size: 16px;
        }
        
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }
            
            .forum-rules-section {
                border-left: none;
                border-top: 1px solid #dee2e6;
                max-height: 400px;
            }
            
            .login-form-section,
            .forum-rules-section {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Login Form Section -->
        <div class="login-form-section">
            <div class="logo">
                <h1>🎓 Smart Discussion Forum</h1>
                <p>Welcome back! Please login to your account</p>
            </div>

            @if (session('success'))
                <div class="success-message">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="error-message" style="background: #f8d7da; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST">
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
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="{{ $errors->has('password') ? 'error' : '' }}"
                        required
                        placeholder="Enter your password"
                    >
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="remember" name="remember" value="1">
                    <label for="remember">Remember me</label>
                </div>

                <button type="submit" class="btn-login">Login</button>

                <div class="forgot-password">
                    <a href="{{ route('password.request') }}">Forgot your password?</a>
                </div>

                <div class="register-link">
                    <span style="color: #6c757d;">Don't have an account?</span>
                    <a href="{{ route('register') }}">Register here</a>
                </div>
            </form>
        </div>

        <!-- Forum Rules Panel -->
        <div class="forum-rules-section">
            <h2>📋 Forum Rules</h2>
            <ul class="rules-list">
                <li>Be respectful and courteous to all members</li>
                <li>No harassment, hate speech, or discrimination</li>
                <li>Stay on topic and contribute meaningfully</li>
                <li>No spam, advertising, or self-promotion</li>
                <li>Respect intellectual property and cite sources</li>
                <li>Use appropriate language and keep content PG-13</li>
                <li>No sharing of personal information</li>
                <li>Report inappropriate content to moderators</li>
                <li>Follow academic integrity guidelines</li>
                <li>One account per person only</li>
                <li>Moderators' decisions are final</li>
                <li>Have fun and help build a great community!</li>
            </ul>
        </div>
    </div>
</body>
</html>
