<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ($generalsetting->name ?? config('app.name', 'Admin')) }} - Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        :root {
            --primary-color: #8b5cf6;
            --primary-hover: #7c3aed;
            --bg-dark: #1e293b;
            --bg-darker: #0f172a;
            --input-bg: #334155;
            --input-border: #475569;
            --text-light: #e2e8f0;
            --text-muted: #94a3b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            color: var(--text-light);
            overflow-x: hidden;
            display: flex;
        }

        /* Video Background with better visibility */
        .video-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            overflow: hidden;
        }

        .video-background video {
            position: absolute;
            top: 50%;
            left: 50%;
            min-width: 100%;
            min-height: 100%;
            width: auto;
            height: auto;
            transform: translate(-50%, -50%);
            object-fit: cover;
            opacity: 0.5;
        }

        .video-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: -1;
        }

        .split-container {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* Left Panel - Admin Login Form */
        .left-panel {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(5px);
        }

        .auth-form {
            width: 100%;
            max-width: 450px;
            padding: 2.5rem;
            background: rgba(30, 41, 59, 0.6);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .auth-header {
            margin-bottom: 2rem;
            text-align: center;
        }

        .auth-title {
            color: white;
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .time-display {
            margin: 1rem 0;
        }

        .current-time {
            font-size: 1.8rem;
            font-weight: bold;
            color: white;
            margin-bottom: 0.25rem;
        }

        .current-date {
            font-size: 1rem;
            color: var(--text-muted);
        }

        .auth-subtitle {
            color: var(--text-muted);
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-light);
            font-weight: 500;
            font-size: 0.95rem;
        }

        .form-control {
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            color: white;
            padding: 0.9rem 1.25rem;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            width: 100%;
        }

        .form-control:focus {
            background: var(--input-bg);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(139, 92, 246, 0.25);
            color: white;
            outline: none;
        }

        .form-control::placeholder {
            color: var(--text-muted);
        }

        .form-control.is-invalid {
            border-color: #ff6b6b;
            box-shadow: 0 0 0 0.25rem rgba(255, 107, 107, 0.25);
        }

        .password-field {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 0;
            font-size: 1rem;
        }

        .password-toggle:hover {
            color: var(--text-light);
        }

        .form-check {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .form-check-input {
            width: 18px;
            height: 18px;
            margin-right: 0.75rem;
            background-color: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 4px;
        }

        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .form-check-input:focus {
            box-shadow: 0 0 0 0.25rem rgba(139, 92, 246, 0.25);
        }

        .form-check-label {
            cursor: pointer;
            user-select: none;
        }

        .extra-links {
            margin-top: 1.5rem;
            text-align: center;
        }

        .extra-links a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .extra-links a:hover {
            color: var(--primary-hover);
            text-decoration: underline;
        }

        .btn-primary {
            background: linear-gradient(135deg, #C86FFF 0%, #5274FF 100%);
            border: none;
            padding: 0.9rem;
            border-radius: 10px;
            font-weight: 600;
            width: 100%;
            margin-top: 0.5rem;
            transition: all 0.3s ease;
            font-size: 1.05rem;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(200, 111, 255, 0.4);
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.7rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            backdrop-filter: blur(5px);
            transition: all 0.3s ease;
            font-weight: 500;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            transform: translateY(-2px);
        }

        /* Right Panel - Video Content & Company Details */
        .right-panel {
            flex: 1;
            background: linear-gradient(135deg, rgba(71, 85, 105, 0.7), rgba(51, 65, 85, 0.7), rgba(30, 41, 59, 0.7));
            padding: 2rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: white;
            backdrop-filter: blur(5px);
            position: relative;
            overflow: hidden;
        }

        .right-content {
            position: relative;
            z-index: 2;
        }

        .company-header {
            margin-bottom: 2rem;
            text-align: center;
        }

        .company-logo {
            font-size: 2.2rem;
            font-weight: bold;
            color: white;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #C86FFF 0%, #5274FF 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .company-tagline {
            color: var(--text-light);
            font-size: 1rem;
            margin-bottom: 0;
        }

        .prayer-times {
            background: rgba(30, 41, 59, 0.6);
            border-radius: 12px;
            padding: 1.5rem;
            backdrop-filter: blur(5px);
            margin-top: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .prayer-times h3 {
            color: white;
            font-size: 1.25rem;
            margin-bottom: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .location-select {
            background: rgba(51, 65, 85, 0.7);
            color: white;
            border: 1px solid var(--input-border);
            border-radius: 6px;
            padding: 0.4rem 0.75rem;
            font-size: 0.85rem;
            margin-left: 0.5rem;
        }

        .prayer-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
        }

        .prayer-item {
            text-align: center;
            padding: 0.75rem;
            background: rgba(51, 65, 85, 0.4);
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .prayer-item:hover {
            background: rgba(51, 65, 85, 0.6);
            transform: translateY(-2px);
        }

        .prayer-name {
            color: var(--text-muted);
            font-size: 0.8rem;
            margin-bottom: 0.35rem;
            font-weight: 500;
        }

        .prayer-time {
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* Tasbih Counter Styles */
        .tasbih-container {
            margin-top: 2rem;
            background: rgba(30, 41, 59, 0.6);
            border-radius: 12px;
            padding: 1.25rem;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .tasbih-title {
            color: white;
            font-size: 1.25rem;
            margin-bottom: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .tasbih-counter {
            font-size: 2.5rem;
            font-weight: bold;
            color: #C86FFF;
            margin: 0.5rem 0;
        }

        .tasbih-btn {
            background: linear-gradient(135deg, #C86FFF 0%, #5274FF 100%);
            border: none;
            color: white;
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            margin: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .tasbih-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(200, 111, 255, 0.4);
        }

        .tasbih-reset {
            background: rgba(239, 68, 68, 0.7);
        }

        .tasbih-reset:hover {
            background: rgba(239, 68, 68, 0.9);
        }

        /* Mobile Toggle Button */
        .mobile-toggle {
            display: none;
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: linear-gradient(135deg, #C86FFF 0%, #5274FF 100%);
            border: none;
            color: white;
            padding: 1rem;
            border-radius: 50%;
            font-size: 1.2rem;
            cursor: pointer;
            z-index: 1000;
            box-shadow: 0 4px 15px rgba(200, 111, 255, 0.4);
            transition: all 0.3s ease;
        }

        .mobile-toggle:hover {
            transform: scale(1.1);
        }

        /* Responsive Styles - Optimized for Mobile */
        @media (max-width: 992px) {
            .split-container {
                flex-direction: column;
                position: relative;
            }
            
            /* Mobile: Login form comes first and takes priority */
            .left-panel {
                order: 1;
                min-height: 100vh;
                padding: 1.5rem;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .right-panel {
                order: 2;
                min-height: auto;
                padding: 1.5rem;
                position: relative;
            }
            
            .auth-form {
                padding: 2rem;
                margin: 0;
            }
            
            .prayer-grid {
                grid-template-columns: repeat(5, 1fr);
            }
            
            /* Show mobile toggle button */
            .mobile-toggle {
                display: block;
            }
            
            /* Initially hide right panel content on mobile */
            .right-panel {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 999;
                overflow-y: auto;
            }
            
            .right-panel.show {
                display: flex;
            }
            
            /* Close button for mobile overlay */
            .mobile-close {
                position: absolute;
                top: 20px;
                right: 20px;
                background: rgba(255, 255, 255, 0.2);
                border: none;
                color: white;
                padding: 0.5rem;
                border-radius: 50%;
                font-size: 1.2rem;
                cursor: pointer;
                z-index: 1001;
            }
        }

        @media (max-width: 768px) {
            .prayer-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .company-logo {
                font-size: 1.8rem;
            }
            
            .auth-title {
                font-size: 1.75rem;
            }
            
            .current-time {
                font-size: 1.5rem;
            }
            
            .left-panel {
                min-height: 70vh;
            }
        }

        @media (max-width: 576px) {
            .left-panel, .right-panel {
                padding: 1rem;
            }
            
            .auth-form {
                padding: 1.5rem;
            }
            
            .prayer-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .company-logo {
                font-size: 1.5rem;
            }
            
            .tasbih-counter {
                font-size: 2rem;
            }
            
            .left-panel {
                min-height: 80vh;
            }
            
            .current-time {
                font-size: 1.3rem;
            }
            
            .auth-title {
                font-size: 1.5rem;
            }
        }

        /* Animation for mobile overlay */
        @keyframes slideIn {
            from {
                transform: translateY(100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .right-panel.show {
            animation: slideIn 0.3s ease-out;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .pulse {
            animation: pulse 0.3s ease-in-out;
        }
    </style>
</head>

<body>
    <!-- Video Background -->
    <div class="video-background">
    </div>
    <div class="video-overlay"></div>

    <div class="split-container">
        <!-- Left Panel - Admin Login Form -->
       <div class="right-panel" id="rightPanel">
            <button class="mobile-close" onclick="toggleMobilePanel()" style="display: none;">
                <i class="fas fa-times"></i>
            </button>
            
            <div class="right-content">
                <div class="company-header">
                    <h1 class="company-logo">{{ ($generalsetting->name ?? config('app.name', 'Admin')) }}</h1>
                </div>

                <div class="prayer-times">
                    <h3>
                        <i class="fas fa-mosque"></i> Prayer Times
                        <select id="locationSelect" class="location-select">
                            <optgroup label="বাংলাদেশের বিভাগ">
                                <option value="Dhaka">ঢাকা</option>
                                <option value="Chittagong">চট্টগ্রাম</option>
                                <option value="Sylhet">সিলেট</option>
                                <option value="Rajshahi">রাজশাহী</option>
                                <option value="Khulna">খুলনা</option>
                                <option value="Barisal">বরিশাল</option>
                                <option value="Rangpur">রংপুর</option>
                                <option value="Mymensingh">ময়মনসিংহ</option>
                            </optgroup>
                            <optgroup label="অন্যান্য দেশ">
                                <option value="Riyadh">রিয়াদ</option>
                                <option value="Dubai">দুবাই</option>
                                <option value="Cairo">কায়রো</option>
                                <option value="Istanbul">ইস্তাম্বুল</option>
                                <option value="Kuala Lumpur">কুয়ালালামপুর</option>
                                <option value="Jakarta">জাকার্তা</option>
                            </optgroup>
                        </select>
                    </h3>
                    <div class="prayer-grid">
                        <div class="prayer-item">
                            <div class="prayer-name">Fajr</div>
                            <div class="prayer-time" id="fajrTime">5:15 AM</div>
                        </div>
                        <div class="prayer-item">
                            <div class="prayer-name">Dhuhr</div>
                            <div class="prayer-time" id="dhuhrTime">12:30 PM</div>
                        </div>
                        <div class="prayer-item">
                            <div class="prayer-name">Asr</div>
                            <div class="prayer-time" id="asrTime">4:15 PM</div>
                        </div>
                        <div class="prayer-item">
                            <div class="prayer-name">Maghrib</div>
                            <div class="prayer-time" id="maghribTime">6:45 PM</div>
                        </div>
                        <div class="prayer-item">
                            <div class="prayer-name">Isha</div>
                            <div class="prayer-time" id="ishaTime">8:00 PM</div>
                        </div>
                    </div>
                </div>

                <!-- Tasbih Counter Section -->
                <div class="tasbih-container">
                    <h3 class="tasbih-title">
                        <i class="fas fa-pray"></i> Tasbih Counter
                    </h3>
                    <div class="tasbih-counter" id="tasbihCount">0</div>
                    <div class="current-dhikr" id="currentDhikr">
                        <span class="dhikr-text">সুবহানাল্লাহ</span>
                        <span class="dhikr-range">(১-৩৩)</span>
                    </div>
                    <div class="d-flex justify-content-center flex-wrap gap-2">
                        <button class="tasbih-btn" id="tasbihIncrement">
                            <i class="fas fa-plus"></i> +1 Count
                        </button>
                        <button class="tasbih-btn tasbih-reset" id="tasbihReset">
                            <i class="fas fa-redo"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>


        <!-- Right Panel - Video Content & Company Details -->
                <div class="left-panel">
            <div class="auth-form">
                <div class="auth-header">
                    <h2 class="auth-title">Admin Login</h2>
                    <div class="time-display">
                        <div class="current-time" id="currentTime">04:43:37 AM</div>
                        <div class="current-date" id="currentDate">Wednesday, August 20, 2025</div>
                    </div>
                    <p class="auth-subtitle">Access the admin dashboard</p>
                </div>

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input id="email" type="email" 
                       class="form-control @error('email') is-invalid @enderror"
                       name="email" value="{{ old('email') }}" 
                       required autocomplete="email" autofocus
                       placeholder="Enter your email address">
                @error('email')
                    <div class="invalid-feedback d-block" style="color: #ff6b6b; font-size: 0.85rem; margin-top: 0.25rem;">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="form-group password-field">
                <label for="password" class="form-label">Password</label>
                <input id="password" type="password" 
                       class="form-control @error('password') is-invalid @enderror" 
                       name="password" required autocomplete="current-password"
                       placeholder="Enter your password">
                <button type="button" class="password-toggle" onclick="togglePassword('password')">
                    <i id="passwordIcon" class="fas fa-eye"></i>
                </button>
                @error('password')
                    <div class="invalid-feedback d-block" style="color: #ff6b6b; font-size: 0.85rem; margin-top: 0.25rem;">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" 
                    {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label" for="remember" style="color: var(--text-light); font-size: 0.9rem;">
                    Remember Me
                </label>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-sign-in-alt me-2"></i>Login
            </button>

            @if (Route::has('password.request'))
                <div class="extra-links">
                    <p><a href="{{ route('password.request') }}">Forgot Your Password?</a></p>
                </div>
            @endif
        </form>

                <div class="text-center mt-4">
                    <a href="/" class="back-btn">
                        <i class="fas fa-arrow-left"></i> Back to Website
                    </a>
                </div>
            </div>
        </div>

    </div>

    <!-- Mobile Toggle Button -->
    <button class="mobile-toggle" onclick="toggleMobilePanel()">
        <i class="fas fa-mosque"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <script>
        // Check for validation errors and show SweetAlert
        document.addEventListener('DOMContentLoaded', function() {
            // Check for email validation error
            @if ($errors->has('email'))
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Email/Phone',
                    text: '{{ $errors->first("email") }}',
                    confirmButtonColor: '#8b5cf6',
                    confirmButtonText: 'Try Again'
                });
            @endif

            // Check for password validation error
            @if ($errors->has('password'))
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Password',
                    text: '{{ $errors->first("password") }}',
                    confirmButtonColor: '#8b5cf6',
                    confirmButtonText: 'Try Again'
                });
            @endif

            // Check for general authentication error
            @if (session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Login Failed',
                    text: '{{ session("error") }}',
                    confirmButtonColor: '#8b5cf6',
                    confirmButtonText: 'Try Again'
                });
            @endif

            // Check for success message
            @if (session('success'))
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: '{{ session("success") }}',
                    confirmButtonColor: '#8b5cf6',
                    timer: 2000
                });
            @endif
        });

        // Mobile panel toggle functionality
        function toggleMobilePanel() {
            const rightPanel = document.getElementById('rightPanel');
            const closeBtn = rightPanel.querySelector('.mobile-close');
            
            if (window.innerWidth <= 992) {
                rightPanel.classList.toggle('show');
                closeBtn.style.display = rightPanel.classList.contains('show') ? 'block' : 'none';
            }
        }

        // Real-time clock
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', {
                hour12: true,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            const dateString = now.toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            document.getElementById('currentTime').textContent = timeString;
            document.getElementById('currentDate').textContent = dateString;
        }

        // Update time every second
        setInterval(updateTime, 1000);
        updateTime(); // Initial call

        // Toggle password visibility
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(inputId + 'Icon');

            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }

        // Prayer times data for different locations
        const prayerTimesData = {
            // বাংলাদেশের বিভাগ
            'Dhaka': {
                fajr: '4:45 AM',
                dhuhr: '12:05 PM',
                asr: '3:25 PM',
                maghrib: '6:15 PM',
                isha: '7:30 PM'
            },
            'Chittagong': {
                fajr: '4:50 AM',
                dhuhr: '12:10 PM',
                asr: '3:30 PM',
                maghrib: '6:20 PM',
                isha: '7:35 PM'
            },
            'Sylhet': {
                fajr: '4:40 AM',
                dhuhr: '12:00 PM',
                asr: '3:20 PM',
                maghrib: '6:10 PM',
                isha: '7:25 PM'
            },
            'Rajshahi': {
                fajr: '4:55 AM',
                dhuhr: '12:15 PM',
                asr: '3:35 PM',
                maghrib: '6:25 PM',
                isha: '7:40 PM'
            },
            'Khulna': {
                fajr: '4:50 AM',
                dhuhr: '12:10 PM',
                asr: '3:30 PM',
                maghrib: '6:20 PM',
                isha: '7:35 PM'
            },
            'Barisal': {
                fajr: '4:45 AM',
                dhuhr: '12:05 PM',
                asr: '3:25 PM',
                maghrib: '6:15 PM',
                isha: '7:30 PM'
            },
            'Rangpur': {
                fajr: '4:50 AM',
                dhuhr: '12:10 PM',
                asr: '3:30 PM',
                maghrib: '6:20 PM',
                isha: '7:35 PM'
            },
            'Mymensingh': {
                fajr: '4:45 AM',
                dhuhr: '12:05 PM',
                asr: '3:25 PM',
                maghrib: '6:15 PM',
                isha: '7:30 PM'
            },
            // অন্যান্য দেশ
            'Riyadh': {
                fajr: '4:15 AM',
                dhuhr: '11:55 AM',
                asr: '3:15 PM',
                maghrib: '6:05 PM',
                isha: '7:45 PM'
            },
            'Dubai': {
                fajr: '4:30 AM',
                dhuhr: '12:15 PM',
                asr: '3:35 PM',
                maghrib: '6:25 PM',
                isha: '7:55 PM'
            },
            'Cairo': {
                fajr: '4:00 AM',
                dhuhr: '11:45 AM',
                asr: '3:05 PM',
                maghrib: '5:55 PM',
                isha: '7:20 PM'
            },
            'Istanbul': {
                fajr: '5:05 AM',
                dhuhr: '12:35 PM',
                asr: '3:55 PM',
                maghrib: '6:45 PM',
                isha: '8:10 PM'
            },
            'Kuala Lumpur': {
                fajr: '5:50 AM',
                dhuhr: '1:15 PM',
                asr: '4:35 PM',
                maghrib: '7:25 PM',
                isha: '8:40 PM'
            },
            'Jakarta': {
                fajr: '4:35 AM',
                dhuhr: '11:55 AM',
                asr: '3:15 PM',
                maghrib: '5:45 PM',
                isha: '7:05 PM'
            }
        };

        // Update prayer times based on selected location
        function updatePrayerTimes(location) {
            const times = prayerTimesData[location];
            if (times) {
                document.getElementById('fajrTime').textContent = times.fajr;
                document.getElementById('dhuhrTime').textContent = times.dhuhr;
                document.getElementById('asrTime').textContent = times.asr;
                document.getElementById('maghribTime').textContent = times.maghrib;
                document.getElementById('ishaTime').textContent = times.isha;
            }
        }

        // Listen for location selection changes
        document.getElementById('locationSelect').addEventListener('change', function() {
            updatePrayerTimes(this.value);
        });

        // Initialize with default location
        updatePrayerTimes('Dhaka');

        // Tasbih Counter Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tasbihCount = document.getElementById('tasbihCount');
            const tasbihIncrement = document.getElementById('tasbihIncrement');
            const tasbihReset = document.getElementById('tasbihReset');
            
            // Load saved count from localStorage
            let count = localStorage.getItem('tasbihCount') || 0;
            tasbihCount.textContent = count;
            
            // Update initial dhikr display
            updateCurrentDhikr(count);
            
            // Increment count
            tasbihIncrement.addEventListener('click', function() {
                count++;
                tasbihCount.textContent = count;
                localStorage.setItem('tasbihCount', count);
                
                // Add visual feedback
                tasbihCount.classList.add('pulse');
                setTimeout(() => {
                    tasbihCount.classList.remove('pulse');
                }, 300);
                
                // Play TTS based on count
                playTasbihTTS(count);
                
                // Update current dhikr display
                updateCurrentDhikr(count);
            });
            
            // Reset count
            tasbihReset.addEventListener('click', function() {
                Swal.fire({
                    title: 'Reset Tasbih Counter?',
                    text: 'Are you sure you want to reset the counter to 0?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#8b5cf6',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Yes, Reset!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        count = 0;
                        tasbihCount.textContent = count;
                        localStorage.setItem('tasbihCount', count);
                        
                        // Update dhikr display
                        updateCurrentDhikr(count);
                        
                        // Play reset sound with authentic Bengali
                        speakText('তসবি রিসেট হয়েছে');
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Tasbih Reset!',
                            text: 'Counter has been reset to 0',
                            confirmButtonColor: '#8b5cf6',
                            timer: 1500
                        });
                    }
                });
            });
        });

        // Update Current Dhikr Display
        function updateCurrentDhikr(count) {
            const currentDhikr = document.getElementById('currentDhikr');
            if (!currentDhikr) return;
            
            let dhikrText = '';
            let dhikrRange = '';
            
            if (count <= 33) {
                dhikrText = 'সুবহানাল্লাহ';
                dhikrRange = '(১-৩৩)';
            } else if (count <= 66) {
                dhikrText = 'আলহামদুলিল্লাহ';
                dhikrRange = '(৩৪-৬৬)';
            } else if (count <= 100) {
                dhikrText = 'লা ইলাহা ইল্লাল্লাহ';
                dhikrRange = '(৬৭-১০০)';
            } else {
                // Reset cycle
                dhikrText = 'সুবহানাল্লাহ';
                dhikrRange = '(১-৩৩)';
            }
            
            currentDhikr.innerHTML = `
                <span class="dhikr-text">${dhikrText}</span>
                <span class="dhikr-range">${dhikrRange}</span>
            `;
        }

        // TTS Function for Tasbih - Authentic Bengali Pronunciation
        function playTasbihTTS(count) {
            let textToSpeak = '';
            
            if (count <= 33) {
                textToSpeak = 'সুবহানাল্লাহ'; // Subhanallah - Glory to Allah
            } else if (count <= 66) {
                textToSpeak = 'আলহামদুলিল্লাহ'; // Alhamdulillah - Praise be to Allah
            } else if (count <= 100) {
                textToSpeak = 'লা ইলাহা ইল্লাল্লাহ'; // La ilaha illallah - There is no god but Allah
            } else {
                // Reset cycle
                textToSpeak = 'সুবহানাল্লাহ';
            }
            
            // Use enhanced Bengali TTS
            speakText(textToSpeak);
        }
        
        // Enhanced Bengali TTS Function - Authentic Pronunciation
        function speakText(text) {
            // Check if browser supports speech synthesis
            if ('speechSynthesis' in window) {
                // Cancel any ongoing speech
                speechSynthesis.cancel();
                
                // Create speech synthesis utterance
                const utterance = new SpeechSynthesisUtterance(text);
                
                // Configure voice settings for authentic Bengali pronunciation
                utterance.lang = 'bn-BD'; // Bangladesh Bengali
                utterance.rate = 0.7;   // Slower for better clarity and reverence
                utterance.pitch = 0.9;  // Slightly lower pitch for solemn tone
                utterance.volume = 0.9; // Higher volume for clear hearing
                
                // Enhanced voice selection for Bengali
                const voices = speechSynthesis.getVoices();
                let selectedVoice = null;
                
                // Priority order for Bengali voices
                const voicePriorities = [
                    'bn-BD', 'bn-IN', 'bn', 'Bengali', 'Bangla', 'Bangladeshi'
                ];
                
                for (const priority of voicePriorities) {
                    selectedVoice = voices.find(voice => 
                        voice.lang.includes(priority) || 
                        voice.name.toLowerCase().includes(priority.toLowerCase())
                    );
                    if (selectedVoice) break;
                }
                
                if (selectedVoice) {
                    utterance.voice = selectedVoice;
                    console.log('Using Bengali voice:', selectedVoice.name, 'Language:', selectedVoice.lang);
                } else {
                    // Fallback to any available voice
                    utterance.voice = voices[0] || null;
                    console.log('Bengali voice not found, using default voice');
                }
                
                // Add event listeners for better control
                utterance.onstart = function() {
                    console.log('Started speaking:', text);
                };
                
                utterance.onend = function() {
                    console.log('Finished speaking:', text);
                };
                
                utterance.onerror = function(event) {
                    console.error('Speech synthesis error:', event.error);
                };
                
                // Speak the text
                speechSynthesis.speak(utterance);
                
                // Log for debugging
                console.log('Speaking:', text, 'with voice:', utterance.voice ? utterance.voice.name : 'default');
                
            } else {
                console.log('Text to Speech not supported in this browser');
                // Fallback: Show alert for unsupported browsers
                alert('আপনার ব্রাউজারে Text-to-Speech সাপোর্ট নেই। Chrome বা Edge ব্যবহার করুন।');
            }
        }
        
        // Load voices when they become available
        if ('speechSynthesis' in window) {
            speechSynthesis.onvoiceschanged = function() {
                // Voices are now loaded
            };
        }

        // Handle window resize
        window.addEventListener('resize', function() {
            const rightPanel = document.getElementById('rightPanel');
            const closeBtn = rightPanel.querySelector('.mobile-close');
            
            if (window.innerWidth > 992) {
                rightPanel.classList.remove('show');
                closeBtn.style.display = 'none';
            }
        });
    </script>
</body>

</html>