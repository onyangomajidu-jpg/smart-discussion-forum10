<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Smart Discussion Forum</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navbar h1 {
            font-size: 24px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .btn-logout {
            background: rgba(255,255,255,0.2);
            padding: 10px 20px;
            border: 2px solid white;
            border-radius: 8px;
            color: white;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-logout:hover {
            background: white;
            color: #667eea;
        }
        
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .welcome-card {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .welcome-card h2 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .role-badge {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-top: 10px;
        }
        
        .role-member { background: #d4edda; color: #155724; }
        .role-lecturer { background: #d1ecf1; color: #0c5460; }
        .role-admin { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <h1>🎓 Smart Discussion Forum</h1>
            <div class="user-info">
                <span>{{ auth()->user()->name }}</span>
                <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn-logout">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container">
        @if (session('success'))
            <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                {{ session('success') }}
            </div>
        @endif

        <div class="welcome-card">
            <h2>Welcome, {{ auth()->user()->name }}! 👋</h2>
            <p style="color: #6c757d; margin-top: 10px;">You are logged in as:</p>
            <span class="role-badge role-{{ auth()->user()->role }}">
                {{ ucfirst(auth()->user()->role) }}
            </span>
            
            <div style="margin-top: 30px; padding-top: 30px; border-top: 1px solid #e1e4e8;">
                <p><strong>Email:</strong> {{ auth()->user()->email }}</p>
                <p style="margin-top: 10px;"><strong>Account Created:</strong> {{ auth()->user()->created_at->format('F d, Y') }}</p>
            </div>
        </div>

        <div style="background: white; border-radius: 12px; padding: 40px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h3 style="color: #333; margin-bottom: 20px;">Quick Links</h3>
            <ul style="list-style: none; padding: 0;">
                <li style="padding: 10px 0;"><a href="#" style="color: #667eea; text-decoration: none;">Browse Forums</a></li>
                <li style="padding: 10px 0;"><a href="#" style="color: #667eea; text-decoration: none;">My Profile</a></li>
                <li style="padding: 10px 0;"><a href="#" style="color: #667eea; text-decoration: none;">Settings</a></li>
            </ul>
        </div>
    </div>
</body>
</html>
