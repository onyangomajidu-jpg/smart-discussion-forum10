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
        .btn { display: inline-block; margin: 20px 0; padding: 14px 32px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white !important; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 15px; }
        .url-box { background: #f8f9fa; border: 1px solid #e1e4e8; border-radius: 6px; padding: 12px 16px; font-size: 12px; color: #555; word-break: break-all; margin-top: 8px; }
        .footer { background: #f8f9fa; padding: 20px 40px; text-align: center; color: #999; font-size: 12px; border-top: 1px solid #eee; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px 16px; border-radius: 4px; font-size: 13px; color: #856404; margin-top: 16px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Discussion Hub</h1>
            <p>Password Reset Request</p>
        </div>
        <div class="body">
            <h2>{{ $greeting ?? 'Hello!' }}</h2>

            @foreach ($introLines ?? [] as $line)
                <p>{{ $line }}</p>
            @endforeach

            @isset($actionText)
                <p style="text-align:center;">
                    <a href="{{ $actionUrl }}" class="btn">{{ $actionText }}</a>
                </p>
                <p style="font-size:13px; color:#888; text-align:center;">Button not working? Copy and paste this link into your browser:</p>
                <div class="url-box">{{ $actionUrl }}</div>
            @endisset

            @foreach ($outroLines ?? [] as $line)
                <p>{{ $line }}</p>
            @endforeach

            <div class="warning">
                If you did not request a password reset, no action is required. Your password will remain unchanged.
            </div>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Discussion Hub. All rights reserved.
        </div>
    </div>
</body>
</html>
