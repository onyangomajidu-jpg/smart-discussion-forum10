<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f4f4; margin: 0; padding: 30px 0; }
        .container { max-width: 560px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 40px 30px; text-align: center; }
        .header h1 { color: white; margin: 0; font-size: 24px; }
        .header p { color: rgba(255,255,255,0.85); margin: 8px 0 0; font-size: 14px; }
        .body { padding: 36px 40px; }
        .body h2 { color: #333; font-size: 20px; margin: 0 0 16px; }
        .body p { color: #555; line-height: 1.7; font-size: 15px; margin: 0 0 16px; }
        .badge { display: inline-block; background: #667eea; color: white; padding: 4px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; text-transform: capitalize; }
        .btn { display: inline-block; margin: 20px 0; padding: 14px 32px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 15px; }
        .footer { background: #f8f9fa; padding: 20px 40px; text-align: center; color: #999; font-size: 12px; border-top: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Discussion Hub</h1>
            <p>Your academic community awaits</p>
        </div>
        <div class="body">
            <h2>Welcome, {{ $user->name }}! 🎉</h2>
            <p>Your account has been created successfully. You're now part of the Discussion Hub community.</p>
            <p><strong>Account details:</strong></p>
            <p>
                Email: <strong>{{ $user->email }}</strong><br>
                Role: <span class="badge">{{ $user->role }}</span>
            </p>
            <p>You can now participate in discussions, access quizzes, and connect with fellow members.</p>
            <a href="{{ url('/dashboard') }}" class="btn">Go to Dashboard</a>
            <p style="font-size:13px; color:#888;">If you did not create this account, please ignore this email.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Discussion Hub. All rights reserved.
        </div>
    </div>
</body>
</html>
