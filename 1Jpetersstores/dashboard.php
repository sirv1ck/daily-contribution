<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Initialize database connection
$conn = connectDB();

// Initialize managers
$contributionManager = new ContributionManager($_SESSION['user_id']);
$rewardManager = new RewardManager($_SESSION['user_id']);
$eventManager = new EventManager($_SESSION['user_id']);
$profileManager = new ProfileManager($_SESSION['user_id']);

// Get user profile
$userProfile = $profileManager->getUserProfile();

// Calculate profile completion percentage
$profile_fields = ['fullname', 'email', 'phone', 'address'];
$filled_fields = 0;
foreach ($profile_fields as $field) {
    if (!empty($userProfile[$field])) {
        $filled_fields++;
    }
}
$profile_completion = round(($filled_fields / count($profile_fields)) * 100);

// Get yearly contribution total
$yearly_total = $contributionManager->getYearlyTotal();

// Get recent contributions
$recent_contributions = $contributionManager->getContributions(5);

// Get available rewards
$available_rewards = $rewardManager->getAvailableRewards();

// Get upcoming events
$upcoming_events = $eventManager->getUpcomingEvents();

$admin_phone = 2348188502964;

$admin_whatsapp = 8188502964;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - JpetersMBS</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
      <?php include 'includes/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <!-- Dashboard Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Contributions Card -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-dollar-sign text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Total Contributions</p>
                        <p class="text-lg font-semibold">₦<?php echo number_format($yearly_total, 2); ?></p>
                    </div>
                </div>
            </div>

            <!-- Available Rewards Card -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-gift text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Available Rewards</p>
                        <p class="text-lg font-semibold"><?php echo count($available_rewards); ?> Items</p>
                    </div>
                </div>
            </div>

            <!-- Upcoming Events Card -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-calendar text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Upcoming Events</p>
                        <p class="text-lg font-semibold"><?php echo count($upcoming_events); ?> Events</p>
                    </div>
                </div>
            </div>

            <!-- Profile Completion Card -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-user text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Profile Completion</p>
                        <p class="text-lg font-semibold"><?php echo $profile_completion; ?>%</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Contribution History -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Recent Contributions</h3>
                <div class="space-y-4">
                    <?php foreach ($recent_contributions as $contribution): ?>
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="font-medium"><?php echo ucwords(str_replace('_', ' ', $contribution['payment_method'])); ?></p>
                            <p class="text-sm text-gray-500"><?php echo date('M d, Y', strtotime($contribution['contribution_date'])); ?></p>
                        </div>
                        <span class="text-green-600 font-medium">₦<?php echo number_format($contribution['amount'], 2); ?></span>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($recent_contributions)): ?>
                    <p class="text-center text-gray-500">No recent contributions</p>
                    <?php endif; ?>
                </div>
                <a href="contributions.php" class="mt-4 inline-block text-purple-600 hover:text-purple-700 text-sm font-medium">View All →</a>
            </div>

            <!-- Available Rewards/Foodstuff -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Available Rewards</h3>
                <div class="space-y-4">
                    <?php foreach (array_slice($available_rewards, 0, 3) as $reward): ?>
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="font-medium"><?php echo htmlspecialchars($reward['name']); ?></p>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($reward['description']); ?></p>
                        </div>
                        <span class="text-purple-600">Available</span>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($available_rewards)): ?>
                    <p class="text-center text-gray-500">No rewards available</p>
                    <?php endif; ?>
                </div>
                <a href="rewards.php" class="mt-4 inline-block text-purple-600 hover:text-purple-700 text-sm font-medium">View All →</a>
            </div>

            <!-- Upcoming Events -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Upcoming Events</h3>
                <div class="space-y-4">
                    <?php foreach (array_slice($upcoming_events, 0, 2) as $event): ?>
                    <div class="border-l-4 border-purple-600 pl-4">
                        <p class="font-medium"><?php echo htmlspecialchars($event['title']); ?></p>
                        <p class="text-sm text-gray-500">
                            <?php echo date('M d, Y', strtotime($event['event_date'])); ?> • 
                            <?php echo date('g:i A', strtotime($event['event_date'])); ?>
                        </p>
                        <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($event['location']); ?></p>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($upcoming_events)): ?>
                    <p class="text-center text-gray-500">No upcoming events</p>
                    <?php endif; ?>
                </div>
                <a href="events.php" class="mt-4 inline-block text-purple-600 hover:text-purple-700 text-sm font-medium">View All →</a>
            </div>
        </div>

        <!-- Add this code just before the closing div of the main content grid in dashboard.php -->
<div class="bg-white rounded-lg shadow p-6">
    <h3 class="text-lg font-semibold mb-4">Featured Products</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <?php 
        $storeManager = new StoreManager();
        $featured_products = $storeManager->getFeaturedProducts(3);
        
        foreach ($featured_products as $product): 
        ?>
        <div class="border rounded-lg p-4">
            <?php if ($product['image_url']): ?>
            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                 class="w-full h-48 object-cover rounded-lg mb-4">
            <?php endif; ?>
            
            <h4 class="font-medium text-lg mb-2"><?php echo htmlspecialchars($product['name']); ?></h4>
            <p class="text-gray-600 text-sm mb-2"><?php echo htmlspecialchars($product['description']); ?></p>
            <p class="text-purple-600 font-bold mb-2">₦<?php echo number_format($product['price'], 2); ?></p>
            
            <div class="space-y-2">
                <?php if ($product['phone_number']): ?>
                <a href="tel:<?php echo htmlspecialchars($admin_phone); ?>" 
                   class="block w-full text-center bg-purple-600 text-white py-2 rounded-lg hover:bg-purple-700">
                    Call to Order
                </a>
                <?php endif; ?>
                
                <?php if ($product['whatsapp_number']): ?>
                <a href="https://wa.me/234<?php echo htmlspecialchars($admin_whatsapp); ?>" 
                   target="_blank"
                   class="block w-full text-center border border-purple-600 text-purple-600 py-2 rounded-lg hover:bg-purple-50">
                    WhatsApp Order
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if (empty($featured_products)): ?>
        <p class="text-center text-gray-500 col-span-3">No products available</p>
        <?php endif; ?>
    </div>
    <a href="store.php" class="mt-4 inline-block text-purple-600 hover:text-purple-700 text-sm font-medium">
        View All Products →
    </a>
</div>
    </div>
</body>
</html>