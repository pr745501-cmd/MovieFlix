<?php
session_start();
include '../config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';

    if ($action === 'login') {
        // Basic sanitization
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        // 1. Fetch user by username using prepared statements
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        // 2. Use password_verify() to check the hashed password
        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID to prevent session fixation attacks
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'admin') {
                header('Location: ../admin/dashboard.php');
            } else {
                header('Location: ../homepage.php');
            }
            exit();
        } else {
            // Generic error message keeps attackers guessing if username or password was wrong
            $error = "Invalid username or password.";
        }
    } elseif ($action === 'register') {
        // Registration handling
        $username = trim($_POST['reg_username']);
        $email = trim($_POST['reg_email']);
        $password = $_POST['reg_password'];

        if (!empty($username) && !empty($email) && !empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'user'; // Default role for standard registration

            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);

            if ($stmt->execute()) {
                $success = "Account created successfully! Please log in.";
            } else {
                $error = "Username or Email already exists.";
            }
        } else {
            $error = "Please fill in all fields.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Auth - MovieFlix</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Outfit', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        /* Tab Navigation */
        .tab-buttons {
            display: flex;
            gap: 0;
            margin-bottom: 2rem;
        }

        .tab-btn {
            flex: 1;
            padding: 0.75rem 1.5rem;
            background: transparent;
            border: none;
            border-bottom: 2px solid #e5e7eb;
            color: #6b7280;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .tab-btn.active {
            color: #059669;
            border-bottom-color: #059669;
        }

        .tab-btn:hover {
            color: #059669;
        }

        /* Form Visibility */
        .auth-form {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .auth-form.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Input Styling */
        .input-field {
            background: white;
            border: 1px solid #e5e7eb;
            color: #111827;
            transition: all 0.3s ease;
        }

        .input-field::placeholder {
            color: #9ca3af;
        }

        .input-field:focus {
            outline: none;
            border-color: #059669;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
            background: #f9fafb;
        }

        /* Buttons */
        .btn-primary {
            background: #059669;
            color: white;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .btn-primary:hover {
            background: #047857;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(5, 150, 105, 0.3);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        /* Links */
        .auth-link {
            color: #059669;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .auth-link:hover {
            color: #047857;
        }

        /* Alert Messages */
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            margin-bottom: 1rem;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-error {
            background: #fecaca;
            border: 1px solid #f87171;
            color: #991b1b;
        }

        .alert-success {
            background: #bbf7d0;
            border: 1px solid #6ee7b7;
            color: #065f46;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .auth-container {
                padding: 1.5rem !important;
            }
        }
    </style>
</head>

<body class="flex items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-2xl">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 text-white mb-4 shadow-lg">
                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24"><path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H9l2 4H8L6 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z"/></svg>
            </div>
            <h1 class="text-4xl font-bold text-gray-900 mb-2">MovieFlix</h1>
            <p class="text-gray-600 text-lg">Your Premium Streaming Hub</p>
        </div>

        <!-- Auth Container -->
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
            <!-- Tab Navigation -->
            <div class="flex border-b border-gray-200 px-8 pt-8">
                <button class="tab-btn active" data-tab="login">Sign In</button>
                <button class="tab-btn" data-tab="register">Create Account</button>
            </div>

            <!-- Login Form -->
            <div class="auth-form active p-8" id="login" data-tab="login">
                <form method="POST" class="space-y-5">
                    <input type="hidden" name="action" value="login">

                    <!-- Notifications -->
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-error">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success">
                            <?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Username -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Username</label>
                        <input type="text" name="username" required placeholder="Enter your username" 
                               class="w-full px-4 py-2.5 rounded-lg input-field border" />
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Password</label>
                        <input type="password" name="password" required placeholder="Enter your password" 
                               class="w-full px-4 py-2.5 rounded-lg input-field border" />
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between text-sm">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" class="w-4 h-4 rounded border-gray-300" />
                            <span class="text-gray-700">Remember me</span>
                        </label>
                        <a href="#" class="auth-link">Forgot password?</a>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="w-full btn-primary py-2.5 rounded-lg font-semibold text-white">
                        Sign In
                    </button>

                    <!-- Signup Link -->
                    <p class="text-center text-gray-600 text-sm">
                        Don't have an account? 
                        <button type="button" class="tab-btn-link" data-tab="register" style="background: none; border: none; cursor: pointer;">
                            <span class="auth-link">Create one</span>
                        </button>
                    </p>
                </form>
            </div>

            <!-- Register Form -->
            <div class="auth-form p-8" id="register" data-tab="register">
                <form method="POST" class="space-y-5">
                    <input type="hidden" name="action" value="register">

                    <!-- Notifications -->
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-error">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success">
                            <?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Username -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Username</label>
                        <input type="text" name="reg_username" required placeholder="Choose a username" 
                               class="w-full px-4 py-2.5 rounded-lg input-field border" />
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Email Address</label>
                        <input type="email" name="reg_email" required placeholder="name@example.com" 
                               class="w-full px-4 py-2.5 rounded-lg input-field border" />
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Password</label>
                        <input type="password" name="reg_password" required placeholder="Create a strong password" 
                               class="w-full px-4 py-2.5 rounded-lg input-field border" />
                    </div>

                    <!-- Terms -->
                    <label class="flex items-start gap-2 cursor-pointer text-sm">
                        <input type="checkbox" required class="w-4 h-4 rounded border-gray-300 mt-0.5" />
                        <span class="text-gray-700">
                            I agree to the <a href="#" class="auth-link">Terms of Service</a> and 
                            <a href="#" class="auth-link">Privacy Policy</a>
                        </span>
                    </label>

                    <!-- Submit Button -->
                    <button type="submit" class="w-full btn-primary py-2.5 rounded-lg font-semibold text-white">
                        Create Account
                    </button>

                    <!-- Signin Link -->
                    <p class="text-center text-gray-600 text-sm">
                        Already have an account? 
                        <button type="button" class="tab-btn-link" data-tab="login" style="background: none; border: none; cursor: pointer;">
                            <span class="auth-link">Sign in</span>
                        </button>
                    </p>
                </form>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8 text-sm text-gray-600 space-y-2">
            <p>© 2025 MovieFlix. All rights reserved.</p>
            <div class="flex gap-6 justify-center">
                <a href="#" class="hover:text-gray-900">Privacy Policy</a>
                <a href="#" class="hover:text-gray-900">Terms of Service</a>
                <a href="#" class="hover:text-gray-900">Contact Us</a>
            </div>
        </div>
    </div>

    <script>
        // Tab switching functionality
        document.querySelectorAll('[data-tab]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (this.classList.contains('tab-btn')) {
                    e.preventDefault();
                }
                
                const tabName = this.dataset.tab;
                
                // Remove active from all tabs and forms
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.auth-form').forEach(f => f.classList.remove('active'));
                
                // Add active to clicked tab and corresponding form
                document.querySelector(`[data-tab="${tabName}"].tab-btn`).classList.add('active');
                document.getElementById(tabName).classList.add('active');
            });
        });
    </script>
</body>

</html>