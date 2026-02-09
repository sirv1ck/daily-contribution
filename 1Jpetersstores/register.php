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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    $required_fields = ['fullname', 'email', 'phone', 'password', 'confirm_password', 'address', 'terms'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $missing_fields[] = ucfirst(str_replace('_', ' ', $field));
        }
    }
    
    if (!empty($missing_fields)) {
        $error_message = 'Please fill in all required fields: ' . implode(', ', $missing_fields);
    } else {
        // Validate email format
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Please enter a valid email address.';
        }
        // Validate password length and complexity
        elseif (strlen($_POST['password']) < 8) {
            $error_message = 'Password must be at least 8 characters long.';
        }
        // Validate password match
        elseif ($_POST['password'] !== $_POST['confirm_password']) {
            $error_message = 'Passwords do not match.';
        }
        // Validate phone number (basic format)
        elseif (!preg_match('/^[0-9+\-\s()]{10,15}$/', $_POST['phone'])) {
            $error_message = 'Please enter a valid phone number.';
        }
        // Validate terms acceptance
        elseif (!isset($_POST['terms']) || $_POST['terms'] !== 'on') {
            $error_message = 'You must accept the Terms and Conditions.';
        } else {
            // Prepare data for registration
            $userData = [
                'fullname' => trim($_POST['fullname']),
                'email' => trim($_POST['email']),
                'phone' => trim($_POST['phone']),
                'password' => $_POST['password'],
                'address' => trim($_POST['address'])
            ];
            
            // Attempt registration
            $result = registerUser($userData);
            
             if ($result['success']) {
                    $success_message = 'Registration successful! Your account is pending activation. Please contact administrator for account activation.';
                }
            
       /*     if ($result['success']) {
                $success_message = $result['message'];
                // Optionally auto-login after registration
                $loginResult = loginUser($userData['email'], $userData['password']);
                if ($loginResult['success']) {
                    header('Location: dashboard.php');
                    exit;
                }
            } else {
                $error_message = $result['message'];
            }   */
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - JpetersMBS</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
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

    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-lg shadow-md">
            <div class="text-center">
                <h2 class="text-3xl font-bold text-gray-900">Create Account</h2>
                <p class="mt-2 text-gray-600">Join our community today</p>
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
                <div class="rounded-md shadow-sm space-y-4">
                    <div>
                        <label for="fullname" class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input type="text" name="fullname" id="fullname" required 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500"
                               value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : ''; ?>">
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
                        <input type="email" name="email" id="email" required 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500"
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                        <input type="tel" name="phone" id="phone" required 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500"
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" name="password" id="password" required 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" required 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                    </div>

                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                        <textarea name="address" id="address" rows="3" 
                                  class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                    </div>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="terms" id="terms" required
                           class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded"
                           <?php echo isset($_POST['terms']) ? 'checked' : ''; ?>>
                    <label for="terms" class="ml-2 block text-sm text-gray-900">
                        I agree to the <a href="#" class="text-purple-600 hover:text-purple-500">Terms and Conditions</a>
                    </label>
                </div>

                <div>
                    <button type="submit" 
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                        Register
                    </button>
                </div>
            </form>
            <div class="text-center">
                <p class="text-sm text-gray-600">Already have an account? 
                    <a href="login.php" class="font-medium text-purple-600 hover:text-purple-500">Sign in</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>