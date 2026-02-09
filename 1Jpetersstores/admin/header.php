<?php
// admin/header.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - JpetersMBS</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
   
    <?php
// Initialize ProfileManager
$profileManager = new ProfileManager($_SESSION['user_id']);
$userProfile = $profileManager->getUserProfile();

// Get profile image URL
$profileImage = $userProfile['profile_image'] ? '../uploads/profile_images/' . $userProfile['profile_image'] : '/api/placeholder/96/96';
?>
<nav class="bg-purple-700 text-white px-6 py-4">
    <div class="container mx-auto">
        <!-- Desktop Navigation -->
        <div class="flex justify-between items-center">
            <!-- Left Logo -->
            <div class="flex items-center space-x-4">
                <a href="index.php" class="hover:text-purple-200 font-bold">
                    <img src="../images/united-logo.jpeg" alt="United MBS Logo" class="h-12 w-auto">
                <h3 class="font-bold hidden md:block">UnitedMBS/J Peters</h3></a>
            </div>

            <!-- Mobile Menu Button -->
            <button id="mobile-menu-button" class="md:hidden flex items-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <!-- Desktop Menu -->
            <div class="hidden md:flex items-center space-x-6">
                <a href="manage_members.php" class="hover:text-purple-200">Members</a>
                <a href="track_contributions.php" class="hover:text-purple-200">Contributions</a>
                <a href="store.php" class="hover:text-purple-200">Store</a>
                <a href="manage_foodstuff.php" class="hover:text-purple-200">Foodstuff</a>
                <a href="manage_events.php" class="hover:text-purple-200">Events</a>
                <a href="generate_reports.php" class="hover:text-purple-200">Reports</a>
                <a href="../logout.php" class="hover:text-purple-200">Logout</a> &nbsp;  
            </div>

            <!-- Right Logo and Profile -->
            <div class="hidden md:flex items-center space-x-4">
                <a href="../profile.php" class="flex items-center space-x-2">
                    <span><?php echo htmlspecialchars($userProfile['fullname']); ?></span>
                </a>
                <img src="../images/jpeters-logo.jpeg" alt="J Peters Logo" class="h-12 w-auto">
            </div>
        </div>

        <!-- Mobile Menu Dropdown -->
        <div id="mobile-menu" class="hidden md:hidden mt-4">
            <div class="flex flex-col space-y-3">
               <a href="manage_members.php" class="hover:text-purple-200">Members</a>
                <a href="track_contributions.php" class="hover:text-purple-200">Contributions</a>
                <a href="store.php" class="hover:text-purple-200">Store</a>
                <a href="manage_foodstuff.php" class="hover:text-purple-200">Foodstuff</a>
                <a href="manage_events.php" class="hover:text-purple-200">Events</a>
                <a href="generate_reports.php" class="hover:text-purple-200">Reports</a>
                <a href="../logout.php" class="hover:text-purple-200">Logout</a>
                <a href="../profile.php" class="flex items-center space-x-2 hover:text-purple-200">
                    <span><?php echo htmlspecialchars($userProfile['fullname']); ?></span>
                </a>
            </div>
        </div>
    </div>
</nav>

<script>
document.getElementById('mobile-menu-button').addEventListener('click', function() {
    document.getElementById('mobile-menu').classList.toggle('hidden');
});
</script>