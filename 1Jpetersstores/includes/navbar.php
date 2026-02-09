<?php
// Initialize ProfileManager
$profileManager = new ProfileManager($_SESSION['user_id']);
$userProfile = $profileManager->getUserProfile();
// Get profile image URL
$profileImage = $userProfile['profile_image'] ? 'uploads/profile_images/' . $userProfile['profile_image'] : '/api/placeholder/96/96';
?>
<nav class="bg-purple-700 text-white px-6 py-4">
    <div class="container mx-auto">
        <!-- Desktop Navigation -->
        <div class="flex justify-between items-center">
            <!-- Left Logo -->
            <div class="flex items-center space-x-4">
                <img src="images/united-logo.jpeg" alt="United MBS Logo" class="h-12 w-auto">
                <h4 class="text-2xl font-bold hidden md:block">UnitedMBS/J Peters</h4>
            </div>
            <!-- Mobile Menu Button -->
            <button id="mobile-menu-button" class="md:hidden flex items-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <!-- Desktop Menu -->
            <div class="hidden md:flex items-center space-x-6">
                <a href="dashboard.php" class="hover:text-purple-200 font-bold">Home</a>
                <a href="contributions.php" class="hover:text-purple-200">Contributions</a>
                <a href="rewards.php" class="hover:text-purple-200">Rewards</a>
                <a href="events.php" class="hover:text-purple-200">Events</a>
                <a href="store.php" class="hover:text-purple-200">Store</a>
                <?php if ($userProfile['is_admin'] == 1): ?>
                <a href="admin/index.php" class="hover:text-purple-200 flex items-center space-x-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span>Admin Panel</span>
                </a>
                <?php endif; ?>
                <a href="logout.php" class="hover:text-purple-200">Logout</a>
            </div>
            <!-- Right Logo and Profile -->
            <div class="hidden md:flex items-center space-x-4">
                <a href="profile.php" class="flex items-center space-x-2">
                    <img src="<?php echo htmlspecialchars($profileImage); ?>" alt="Profile" class="w-8 h-8 rounded-full">
                    <span><?php echo htmlspecialchars($userProfile['fullname']); ?></span>
                </a>
                <img src="images/jpeters-logo.jpeg" alt="J Peters Logo" class="h-12 w-auto">
            </div>
        </div>
        <!-- Mobile Menu Dropdown -->
        <div id="mobile-menu" class="hidden md:hidden mt-4">
            <div class="flex flex-col space-y-3">
                <a href="dashboard.php" class="hover:text-purple-200">Home</a>
                <a href="contributions.php" class="hover:text-purple-200">Contributions</a>
                <a href="rewards.php" class="hover:text-purple-200">Rewards</a>
                <a href="events.php" class="hover:text-purple-200">Events</a>
                <a href="store.php" class="hover:text-purple-200">Store</a>
                <?php if ($userProfile['is_admin'] == 1): ?>
                <a href="admin/index.php" class="hover:text-purple-200 flex items-center space-x-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span>Admin Panel</span>
                </a>
                <?php endif; ?>
                <a href="logout.php" class="hover:text-purple-200">Logout</a>
                <a href="profile.php" class="flex items-center space-x-2">
                    <img src="<?php echo htmlspecialchars($profileImage); ?>" alt="Profile" class="w-8 h-8 rounded-full">
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