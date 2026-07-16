<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('images/forum-favicon.png') }}">
    <title>Register - Smart Discussion Forum</title>
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
            padding: 40px 20px;
        }
        
        .register-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 50px 40px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .logo img {
            width: 70px;
            height: 70px;
            object-fit: contain;
            display: block;
            margin: 0 auto 16px;
        }
        .logo h1 {
            color: #667eea;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .logo p {
            color: #6c757d;
            font-size: 15px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
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
        
        .form-group label .required {
            color: #dc3545;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e4e8;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-right: 44px;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 38%;
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
        
        .role-specific-fields {
            display: none;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .role-specific-fields.active {
            display: block;
        }
        
        .forum-rules-box {
            background: #f8f9fa;
            border: 2px solid #667eea;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .forum-rules-box h3 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .forum-rules-box ul {
            list-style: none;
            padding: 0;
        }
        
        .forum-rules-box li {
            padding: 8px 0;
            padding-left: 25px;
            position: relative;
            color: #495057;
            font-size: 13px;
        }
        
        .forum-rules-box li::before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #667eea;
            font-weight: bold;
        }
        
        .acceptance-checkbox {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .acceptance-checkbox input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-right: 10px;
            cursor: pointer;
        }
        
        .acceptance-checkbox label {
            color: #856404;
            font-weight: 600;
            cursor: pointer;
            margin: 0;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .btn-register {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn-register:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-register:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .login-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #e1e4e8;
        }
        
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .register-container {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="logo">
            <img src="{{ asset('images/forum.png') }}" alt="Smart Discussion Forum">
            <h1>Smart Discussion Forum</h1>
            <p>Create your account to join our community</p>
        </div>

        @if ($errors->any())
            <div class="error-message">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form action="{{ route('register') }}" method="POST" id="registerForm">
            @csrf
            
            <div class="form-row">
                <div class="form-group">
                    <label for="name">Full Name <span class="required">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password <span class="required">*</span></label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('password', this)" tabindex="-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </button>
                    </div>
                    <small style="color: #6c757d; font-size: 12px;">Min 8 chars, mixed case, numbers & symbols</small>
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Confirm Password <span class="required">*</span></label>
                    <div class="password-wrapper">
                        <input type="password" id="password_confirmation" name="password_confirmation" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('password_confirmation', this)" tabindex="-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="role">I am a <span class="required">*</span></label>
                <select id="role" name="role" required>
                    <option value="">-- Select Role --</option>
                    <option value="member" {{ old('role') == 'member' ? 'selected' : '' }}>Student Member</option>
                    <option value="lecturer" {{ old('role') == 'lecturer' ? 'selected' : '' }}>Lecturer</option>
                    <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Administrator</option>
                </select>
            </div>

            <!-- Member-specific fields -->
            <div id="memberFields" class="role-specific-fields">
                <h4 style="margin-bottom: 15px; color: #667eea;">Student Information</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label for="student_id">Student ID</label>
                        <input type="text" id="student_id" name="student_id" value="{{ old('student_id') }}">
                    </div>
                    <div class="form-group">
                        <label for="programme">Programme</label>
                        <input type="text" id="programme" name="programme" value="{{ old('programme') }}" placeholder="e.g., BSc Computer Science">
                    </div>
                </div>
                <div class="form-group">
                    <label for="year_of_study">Year of Study</label>
                    <select id="year_of_study" name="year_of_study">
                        <option value="">-- Select Year --</option>
                        <option value="1">Year 1</option>
                        <option value="2">Year 2</option>
                        <option value="3">Year 3</option>
                        <option value="4">Year 4</option>
                        <option value="5">Year 5+</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="group_id">Join a Group</label>
                    <select id="group_id" name="group_id">
                        <option value="">-- Select a Group (optional) --</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" {{ old('group_id') == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Lecturer-specific fields -->
            <div id="lecturerFields" class="role-specific-fields">
                <h4 style="margin-bottom: 15px; color: #667eea;">Lecturer Information</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label for="staff_id">Staff ID</label>
                        <input type="text" id="staff_id" name="staff_id" value="{{ old('staff_id') }}">
                    </div>
                    <div class="form-group">
                        <label for="department">Department</label>
                        <input type="text" id="department" name="department" value="{{ old('department') }}">
                    </div>
                </div>
                <div class="form-group">
                    <label for="specialisation">Specialisation</label>
                    <input type="text" id="specialisation" name="specialisation" value="{{ old('specialisation') }}">
                </div>
            </div>

            <!-- Forum Rules (Gate requirement) -->
            <div class="forum-rules-box">
                <h3>📋 Forum Rules - Please Read Carefully</h3>
                <ul>
                    <li>Be respectful and courteous to all members at all times</li>
                    <li>No harassment, hate speech, bullying, or discrimination of any kind</li>
                    <li>Stay on topic and contribute meaningfully to discussions</li>
                    <li>No spam, unsolicited advertising, or excessive self-promotion</li>
                    <li>Respect intellectual property rights and always cite your sources</li>
                    <li>Use appropriate language and keep all content suitable for academic environment</li>
                    <li>Never share personal information (yours or others')</li>
                    <li>Report inappropriate content or behavior to moderators immediately</li>
                    <li>Follow academic integrity guidelines - no plagiarism or cheating</li>
                    <li>Maintain only one account per person</li>
                    <li>Moderators and administrators reserve the right to edit or remove content</li>
                    <li>Violations may result in warnings, temporary suspension, or permanent ban</li>
                </ul>
            </div>

            <!-- Forum Rules Acceptance Gate -->
            <div class="acceptance-checkbox">
                <input type="checkbox" id="accept_rules" name="accept_rules" value="1" required>
                <label for="accept_rules">
                    I have read and agree to abide by the forum rules <span class="required">*</span>
                </label>
            </div>

            <button type="submit" class="btn-register" id="submitBtn">Create Account</button>

            <div class="login-link">
                <span style="color: #6c757d;">Already have an account?</span>
                <a href="{{ route('login') }}">Login here</a>
            </div>
        </form>
    </div>

    <script>
        // Role-based field toggling
        document.getElementById('role').addEventListener('change', function() {
            const role = this.value;
            
            // Hide all role-specific fields
            document.querySelectorAll('.role-specific-fields').forEach(field => {
                field.classList.remove('active');
            });
            
            // Show relevant fields
            if (role === 'member') {
                document.getElementById('memberFields').classList.add('active');
            } else if (role === 'lecturer') {
                document.getElementById('lecturerFields').classList.add('active');
            }
        });

        // Gate enforcement - Disable submit button until rules are accepted
        const acceptCheckbox = document.getElementById('accept_rules');
        const submitBtn = document.getElementById('submitBtn');
        
        submitBtn.disabled = true;
        
        acceptCheckbox.addEventListener('change', function() {
            submitBtn.disabled = !this.checked;
        });

        // Show role fields if old input exists
        window.addEventListener('DOMContentLoaded', function() {
            const roleSelect = document.getElementById('role');
            if (roleSelect.value) {
                roleSelect.dispatchEvent(new Event('change'));
            }
        });

        function togglePassword(fieldId, btn) {
            const input = document.getElementById(fieldId);
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            btn.querySelector('svg').style.opacity = isHidden ? '0.5' : '1';
        }
    </script>
</body>
</html>
