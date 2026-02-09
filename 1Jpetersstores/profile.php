<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Initialize ProfileManager
$profileManager = new ProfileManager($_SESSION['user_id']);
$userProfile = $profileManager->getUserProfile();

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $updateData = [
            'fullname' => $_POST['fullname'],
            'phone' => $_POST['phone'],
            'address' => $_POST['address'],
            'bank_name' => $_POST['bank_name'],
            'account_name' => $_POST['account_name'],
            'account_number' => $_POST['account_number']
        ];
        
        // Handle image upload
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $profileManager->uploadProfileImage($_FILES['profile_image']);
            if ($uploadResult['success']) {
                $updateData['profile_image'] = $uploadResult['path'];
            } else {
                $message = $uploadResult['message'];
                $messageType = 'error';
            }
        }
        
        if ($profileManager->updateProfile($updateData)) {
            $message = 'Profile updated successfully!';
            $messageType = 'success';
            $userProfile = $profileManager->getUserProfile(); // Refresh profile data
        } else {
            $message = 'Failed to update profile.';
            $messageType = 'error';
        }
    } elseif (isset($_POST['change_password'])) {
        // Password change handling remains the same
        if (empty($_POST['current_password']) || empty($_POST['new_password'])) {
            $message = 'Both current and new passwords are required.';
            $messageType = 'error';
        } else {
            if ($profileManager->changePassword($_POST['current_password'], $_POST['new_password'])) {
                $message = 'Password changed successfully!';
                $messageType = 'success';
            } else {
                $message = 'Failed to change password. Please check your current password.';
                $messageType = 'error';
            }
        }
    }
}

// Get profile image URL
$profileImage = $userProfile['profile_image'] ? 'uploads/profile_images/' . $userProfile['profile_image'] : '/api/placeholder/96/96';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - JpetersMBS</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <?php include 'includes/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-3xl mx-auto">
            <?php if ($message): ?>
            <div class="mb-4 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <div class="bg-white shadow rounded-lg">
                <!-- Profile Header -->
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center space-x-4">
                        <div class="relative group">
                            <img src="<?php echo htmlspecialchars($profileImage); ?>" 
                                 alt="Profile" 
                                 class="w-24 h-24 rounded-full object-cover">
                            <label for="profile_image" 
                                   class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50 text-white rounded-full opacity-0 group-hover:opacity-100 cursor-pointer transition-opacity">
                                Change Photo
                            </label>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($userProfile['fullname']); ?></h2>
                            <p class="text-gray-500">Member since <?php echo date('F Y', strtotime($userProfile['join_date'])); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Profile Form -->
                <form action="profile.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
                    <input type="hidden" name="update_profile" value="1">
                    <input type="file" id="profile_image" name="profile_image" accept="image/*" class="hidden" onchange="previewImage(this)">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Rest of the form fields remain the same -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Full Name</label>
                            <input type="text" name="fullname" value="<?php echo htmlspecialchars($userProfile['fullname']); ?>" 
                                   required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" value="<?php echo htmlspecialchars($userProfile['email']); ?>" 
                                   disabled class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-50">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($userProfile['phone']); ?>" 
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Address</label>
                            <textarea name="address" rows="3" 
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500"><?php echo htmlspecialchars($userProfile['address']); ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Bank Details section -->
                    <div class="p-6 border-t border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Bank Details</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Bank Name</label>
                                <input type="text" 
                                       name="bank_name" 
                                       value="<?php echo htmlspecialchars($userProfile['bank_name'] ?? ''); ?>" 
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                            </div>
                    
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Account Name</label>
                                <input type="text" 
                                       name="account_name" 
                                       value="<?php echo htmlspecialchars($userProfile['account_name'] ?? ''); ?>" 
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                            </div>
                    
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Account Number</label>
                                <input type="text" 
                                       name="account_number" 
                                       value="<?php echo htmlspecialchars($userProfile['account_number'] ?? ''); ?>" 
                                       pattern="[0-9]*"
                                       maxlength="10"
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" 
                                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                            Save Profile Changes
                        </button>
                    </div>
                </form>

                 <!-- Password Change Form -->
                <form action="profile.php" method="POST" class="p-6 border-t border-gray-200">
                    <input type="hidden" name="change_password" value="1">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Change Password</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Current Password</label>
                            <input type="password" name="current_password" required
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">New Password</label>
                            <input type="password" name="new_password" required
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button type="submit" 
                                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                            Change Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const profileImg = input.closest('.relative').querySelector('img');
                profileImg.src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    
        document.querySelector('input[name="account_number"]').addEventListener('input', function(e) {
            // Remove any non-numeric characters
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // Limit to 10 digits
            if (this.value.length > 10) {
                this.value = this.value.slice(0, 10);
            }
        });

    // Rest of your JavaScript validation remains the same
    document.addEventListener('DOMContentLoaded', function() {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const requiredFields = form.querySelectorAll('[required]');
                let valid = true;
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        valid = false;
                        field.classList.add('border-red-500');
                    } else {
                        field.classList.remove('border-red-500');
                    }
                });

                if (!valid) {
                    e.preventDefault();
                    alert('Please fill in all required fields.');
                }
            });
        });
    });
    </script>
</body>
</html>