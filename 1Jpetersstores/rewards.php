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

// Initialize RewardManager
$rewardManager = new RewardManager($_SESSION['user_id']);

// Get available rewards
$available_rewards = $rewardManager->getAvailableRewards();

// Get user's yearly contribution total
$contributionManager = new ContributionManager($_SESSION['user_id']);
$yearly_total = $contributionManager->getYearlyTotal();

// Process reward claim
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim_reward'])) {
    try {
        $foodstuff_id = filter_var($_POST['foodstuff_id'], FILTER_VALIDATE_INT);
        $quantity = filter_var($_POST['quantity'], FILTER_VALIDATE_INT);
        
        if ($rewardManager->claimReward($foodstuff_id, $quantity)) {
            $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                          Reward claimed successfully!</div>';
        } else {
            throw new Exception("Failed to claim reward. Please try again.");
        }
    } catch (Exception $e) {
        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                      Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// Get user's claimed rewards
$sql = "SELECT fd.quantity, fd.distribution_date, fd.status, f.name, f.unit
        FROM foodstuff_distribution fd
        JOIN foodstuff f ON fd.foodstuff_id = f.id
        WHERE fd.user_id = ?
        ORDER BY fd.distribution_date DESC
        LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$claimed_rewards = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rewards - UnitedMBS/JPeters</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <?php include 'includes/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <?php echo $message; ?>

        <!-- Contribution Status -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Your Contribution Status</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-gray-600">Total Contributions This Year</p>
                    <p class="text-2xl font-bold text-purple-600">₦<?php echo number_format($yearly_total, 2); ?></p>
                </div>
                <div>
                    <p class="text-gray-600">Reward Eligibility Status</p>
                    <?php if ($yearly_total >= 1000): ?>
                    <p class="text-green-600 font-semibold">Eligible for Rewards</p>
                    <?php else: ?>
                    <p class="text-yellow-600 font-semibold">Need ₦<?php echo number_format(1000 - $yearly_total, 2); ?> more to qualify</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Available Rewards -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Available Rewards</h2>
                    <?php if ($yearly_total >= 1000): ?>
                    <div class="space-y-4">
                        <?php foreach ($available_rewards as $reward): ?>
                        <div class="border rounded p-4">
                            <h3 class="font-medium text-gray-900"><?php echo htmlspecialchars($reward['name']); ?></h3>
                            <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($reward['description']); ?></p>
                            <p class="text-sm text-gray-500 mt-2">Available: <?php echo $reward['quantity'] . ' ' . $reward['unit']; ?></p>
                            
                            <form action="" method="POST" class="mt-3">
                                <input type="hidden" name="foodstuff_id" value="<?php echo $reward['id']; ?>">
                                <div class="flex items-center space-x-3">
                                    <input type="number" name="quantity" min="1" max="<?php echo $reward['quantity']; ?>" required
                                           class="w-24 border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                                    <button type="submit" name="claim_reward"
                                            class="bg-purple-600 text-white py-2 px-4 rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                                        Claim
                                    </button>
                                </div>
                            </form>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($available_rewards)): ?>
                        <p class="text-center text-gray-500 py-4">No rewards available at this time.</p>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-8">
                        <p class="text-gray-600">Continue contributing to unlock rewards!</p>
                        <p class="text-sm text-gray-500 mt-2">Minimum yearly contribution: $1,000</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Claimed Rewards -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Your Claimed Rewards</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($claimed_rewards as $reward): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($reward['name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $reward['quantity'] . ' ' . $reward['unit']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('M d, Y', strtotime($reward['distribution_date'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                   <?php echo $reward['status'] === 'confirmed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                            <?php echo ucfirst($reward['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php if (empty($claimed_rewards)): ?>
                        <p class="text-center text-gray-500 py-4">No rewards claimed yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Form validation
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const quantity = parseInt(form.quantity.value);
                const max = parseInt(form.quantity.max);
                if (quantity <= 0 || quantity > max) {
                    e.preventDefault();
                    alert('Please enter a valid quantity (1-' + max + ')');
                }
            });
        });
    });
    </script>
</body>
</html>