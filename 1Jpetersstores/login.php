<?php
session_start();
require_once 'config/database.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error_message = '';
$success_message = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token if implemented
    
    // Validate required fields
    if (empty($_POST['email']) || empty($_POST['password'])) {
        $error_message = 'Please fill in all required fields.';
    } else {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];

        // Attempt login
        $result = loginUser($email, $password);
        
        if ($result['success']) {
            // If remember me is checked, set cookie
            if (isset($_POST['remember-me']) && $_POST['remember-me'] == 'on') {
                $token = bin2hex(random_bytes(32));
                // Store token in database with user ID and expiration
                setcookie('remember_token', $token, time() + (86400 * 30), '/'); // 30 days
            }
            
            // Redirect to dashboard
            header('Location: dashboard.php');
            exit;
        } else {
            $error_message = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - JpetersMBS</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-purple: #6B46C1;
            --primary-purple-dark: #553C9A;
        }
        .btn-purple {
            background-color: var(--primary-purple);
            color: white;
            transition: background-color 0.3s;
        }
        .btn-purple:hover {
            background-color: var(--primary-purple-dark);
        }
    </style>
</head>
<body class="bg-gray-50">
    <nav class="bg-purple-700 text-white px-6 py-4">
    <div class="container mx-auto">
        <!-- Desktop Navigation -->
        <div class="flex justify-between items-center">
            <!-- Left Logo -->
            <div class="flex items-center space-x-4">
                <img src="images/united-logo.jpeg" alt="United MBS Logo" class="h-12 w-auto">
                <h1 class="text-2xl font-bold hidden md:block">UnitedMBS/J Peters</h1>
            </div>

            <!-- Mobile Menu Button -->
            <button id="mobile-menu-button" class="md:hidden flex items-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <!-- Desktop Menu -->
            <div class="hidden md:flex items-center space-x-6">
                <a href="#" class="hover:text-purple-200">Home</a>
                <a href="#features" class="hover:text-purple-200">Features</a>
                <a href="#about" class="hover:text-purple-200">About</a>
                <a href="#contact" class="hover:text-purple-200">Contact</a>
                <a href="login.php" class="hover:text-purple-200">Login</a>
                <a href="register.php" class="px-4 py-2 rounded-lg bg-white text-purple-700 hover:bg-purple-100">Register</a>
            </div>

            <!-- Right Logo and Profile -->
            <div class="hidden md:flex items-center space-x-4">
                <img src="images/jpeters-logo.jpeg" alt="J Peters Logo" class="h-12 w-auto">
            </div>
        </div>

        <!-- Mobile Menu Dropdown -->
        <div id="mobile-menu" class="hidden md:hidden mt-4">
            <div class="flex flex-col space-y-3">
               <a href="#" class="hover:text-purple-200">Home</a>
                <a href="#features" class="hover:text-purple-200">Features</a>
                <a href="#about" class="hover:text-purple-200">About</a>
                <a href="#contact" class="hover:text-purple-200">Contact</a>
                <a href="login.php" class="hover:text-purple-200">Login</a>
                <a href="register.php" class="px-4 py-2 rounded-lg bg-white text-purple-700 hover:bg-purple-100">Register</a>
            </div>
        </div>
    </div>
</nav>

    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-lg shadow-md">
            <div class="text-center">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Welcome Back</h2>
                <p class="text-gray-600">Please sign in to your account</p>
            </div>

            <?php if ($error_message): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($success_message); ?></span>
                </div>
            <?php endif; ?>

            <form class="mt-8 space-y-6" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="email" class="sr-only">Email address</label>
                        <input id="email" name="email" type="email" required 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-purple-500 focus:border-purple-500 focus:z-10 sm:text-sm" 
                               placeholder="Email address"
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    <div>
                        <label for="password" class="sr-only">Password</label>
                        <input id="password" name="password" type="password" required 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-purple-500 focus:border-purple-500 focus:z-10 sm:text-sm" 
                               placeholder="Password">
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember-me" name="remember-me" type="checkbox" 
                               class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                        <label for="remember-me" class="ml-2 block text-sm text-gray-900">Remember me</label>
                    </div>

                    <div class="text-sm">
                        <a href="forgot-password.php" class="font-medium text-purple-600 hover:text-purple-500">
                            Forgot password?
                        </a>
                    </div>
                </div>

                <div>
                    <button type="submit" 
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                        Sign in
                    </button>
                </div>
            </form>

            <div class="text-center">
                <p class="text-sm text-gray-600">Don't have an account? 
                    <a href="register.php" class="font-medium text-purple-600 hover:text-purple-500">Register here</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>