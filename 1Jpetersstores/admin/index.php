<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../config/database.php';
require_once 'check_admin.php';
checkAdmin();

class DashboardStats {
    private $conn;
    
    public function __construct() {
        $this->conn = connectDB();
    }
    
    public function getTotalUsers() {
        $sql = "SELECT COUNT(*) as total FROM users";
        return $this->conn->query($sql)->fetch_assoc()['total'];
    }
    
    public function getNewUsersToday() {
        $sql = "SELECT COUNT(*) as total FROM users 
                WHERE DATE(join_date) = CURRENT_DATE";
        return $this->conn->query($sql)->fetch_assoc()['total'];
    }
    
    public function getContributionStats() {
        $stats = [
            'today' => 0,
            'week' => 0,
            'month' => 0,
            'total' => 0
        ];
        
        // Today's contributions
        $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM contributions 
                WHERE DATE(contribution_date) = CURRENT_DATE";
        $stats['today'] = $this->conn->query($sql)->fetch_assoc()['total'];
        
        // This week's contributions
        $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM contributions 
                WHERE YEARWEEK(contribution_date) = YEARWEEK(CURRENT_DATE)";
        $stats['week'] = $this->conn->query($sql)->fetch_assoc()['total'];
        
        // This month's contributions
        $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM contributions 
                WHERE YEAR(contribution_date) = YEAR(CURRENT_DATE) 
                AND MONTH(contribution_date) = MONTH(CURRENT_DATE)";
        $stats['month'] = $this->conn->query($sql)->fetch_assoc()['total'];
        
        // Total contributions
        $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM contributions";
        $stats['total'] = $this->conn->query($sql)->fetch_assoc()['total'];
        
        return $stats;
    }
    
    public function getFoodstuffStats() {
        $sql = "SELECT COUNT(*) as total, 
                       SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available 
                FROM foodstuff 
                WHERE YEAR(created_at) = YEAR(CURRENT_DATE)";
        return $this->conn->query($sql)->fetch_assoc();
    }
    
    public function getUpcomingEvents() {
        $sql = "SELECT title, event_date, location 
                FROM events 
                WHERE event_date > NOW() 
                ORDER BY event_date ASC 
                LIMIT 5";
        return $this->conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getRecentContributions() {
        $sql = "SELECT u.fullname, c.amount, c.contribution_date 
                FROM contributions c 
                JOIN users u ON c.user_id = u.id 
                ORDER BY c.contribution_date DESC 
                LIMIT 5";
        return $this->conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

                public function getStoreStats() {
                $stats = [
                    'total_products' => 0,
                    'available_products' => 0,
                    'out_of_stock' => 0,
                    'total_orders' => 0
                ];
                
                // Get product stats
                $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
                        SUM(CASE WHEN status = 'out_of_stock' THEN 1 ELSE 0 END) as out_of_stock
                        FROM store_products";
                $result = $this->conn->query($sql)->fetch_assoc();
                
                $stats['total_products'] = $result['total'];
                $stats['available_products'] = $result['available'];
                $stats['out_of_stock'] = $result['out_of_stock'];
                
                // Get total orders
                $sql = "SELECT COUNT(*) as total FROM store_orders";
                $stats['total_orders'] = $this->conn->query($sql)->fetch_assoc()['total'];
                
                return $stats;
            }
            
            public function getRecentOrders() {
                $sql = "SELECT so.*, sp.name as product_name 
                        FROM store_orders so
                        JOIN store_products sp ON so.product_id = sp.id
                        ORDER BY so.order_date DESC 
                        LIMIT 5";
                return $this->conn->query($sql)->fetch_all(MYSQLI_ASSOC);
            }
        public function getNewUsers() {
        $sql = "SELECT id, fullname, email, phone, address, join_date 
                FROM users 
                WHERE status = 'inactive' 
                ORDER BY join_date DESC";
        return $this->conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    }
}

            $dashboard = new DashboardStats();
            $userStats = [
                'total' => $dashboard->getTotalUsers(),
                'new' => $dashboard->getNewUsersToday()
            ];
            $contributionStats = $dashboard->getContributionStats();
            $foodstuffStats = $dashboard->getFoodstuffStats();
            $upcomingEvents = $dashboard->getUpcomingEvents();
            $recentContributions = $dashboard->getRecentContributions();
            $storeStats = $dashboard->getStoreStats();
            $recentOrders = $dashboard->getRecentOrders();
            $newUsers = $dashboard->getNewUsers();
            $contributionManager = new ContributionManager();
    $pendingContributions = $contributionManager->getPendingContributions();


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
</head>
<body class="bg-gray-50">
    <?php include 'header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Dashboard Overview</h1>
        
        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Users -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Total Users</p>
                        <p class="text-2xl font-semibold text-gray-800"><?php echo number_format($userStats['total']); ?></p>
                        <p class="text-sm text-green-500">+<?php echo $userStats['new']; ?> today</p>
                    </div>
                </div>
            </div>

            <!-- Contributions Today -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-hand-holding-dollar text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Today's Contributions</p>
                        <p class="text-2xl font-semibold text-gray-800">₦<?php echo number_format($contributionStats['today']); ?></p>
                        <p class="text-sm text-gray-500">Month: ₦<?php echo number_format($contributionStats['month']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Available Foodstuff -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-box text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Available Foodstuff</p>
                        <p class="text-2xl font-semibold text-gray-800"><?php echo $foodstuffStats['available']; ?></p>
                        <p class="text-sm text-gray-500">Total: <?php echo $foodstuffStats['total']; ?></p>
                    </div>
                </div>
            </div>

            <!-- Total Contributions -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <i class="fas fa-chart-line text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Total Contributions</p>
                        <p class="text-2xl font-semibold text-gray-800">₦<?php echo number_format($contributionStats['total']); ?></p>
                        <p class="text-sm text-gray-500">Week: ₦<?php echo number_format($contributionStats['week']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Store Stats -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Products -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
                <i class="fas fa-box-archive text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Total Products</p>
                <p class="text-2xl font-semibold text-gray-800"><?php echo number_format($storeStats['total_products']); ?></p>
                <p class="text-sm text-gray-500"><?php echo $storeStats['available_products']; ?> available</p>
            </div>
        </div>
    </div>

    <!-- Out of Stock -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-red-100 text-red-600">
                <i class="fas fa-exclamation-circle text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Out of Stock</p>
                <p class="text-2xl font-semibold text-gray-800"><?php echo number_format($storeStats['out_of_stock']); ?></p>
                <p class="text-sm text-red-500">Needs attention</p>
            </div>
        </div>
    </div>

    <!-- Total Orders -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600">
                <i class="fas fa-shopping-cart text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Total Orders</p>
                <p class="text-2xl font-semibold text-gray-800"><?php echo number_format($storeStats['total_orders']); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders -->
<div class="bg-white rounded-lg shadow mb-8">
    <div class="p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Recent Orders</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="text-left text-xs font-medium text-gray-500 uppercase">
                        <th class="py-3 px-4">Customer</th>
                        <th class="py-3 px-4">Product</th>
                        <th class="py-3 px-4">Amount</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                    <tr class="border-t">
                        <td class="py-3 px-4"><?php echo htmlspecialchars($order['customer_name']); ?></td>
                        <td class="py-3 px-4"><?php echo htmlspecialchars($order['product_name']); ?></td>
                        <td class="py-3 px-4">₦<?php echo number_format($order['total_amount']); ?></td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 text-xs rounded-full <?php 
                                echo ($order['order_status'] === 'confirmed') ? 'bg-green-100 text-green-800' : 
                                     ($order['order_status'] === 'cancelled' ? 'bg-red-100 text-red-800' : 
                                     'bg-yellow-100 text-yellow-800');
                            ?>">
                                <?php echo ucfirst($order['order_status']); ?>
                            </span>
                        </td>
                        <td class="py-3 px-4"><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
        
        <!-- Recent Activity Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Contributions -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Recent Contributions</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="text-left text-xs font-medium text-gray-500 uppercase">
                                    <th class="py-3 px-4">Member</th>
                                    <th class="py-3 px-4">Amount</th>
                                    <th class="py-3 px-4">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentContributions as $contribution): ?>
                                <tr class="border-t">
                                    <td class="py-3 px-4"><?php echo htmlspecialchars($contribution['fullname']); ?></td>
                                    <td class="py-3 px-4">₦<?php echo number_format($contribution['amount']); ?></td>
                                    <td class="py-3 px-4"><?php echo date('M d, Y', strtotime($contribution['contribution_date'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Upcoming Events -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Upcoming Events</h2>
                    <div class="space-y-4">
                        <?php foreach ($upcomingEvents as $event): ?>
                        <div class="border-l-4 border-purple-500 pl-4 py-2">
                            <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($event['title']); ?></h3>
                            <p class="text-sm text-gray-600">
                                <i class="fas fa-calendar-alt mr-2"></i>
                                <?php echo date('M d, Y', strtotime($event['event_date'])); ?>
                            </p>
                            <p class="text-sm text-gray-600">
                                <i class="fas fa-location-dot mr-2"></i>
                                <?php echo htmlspecialchars($event['location']); ?>
                            </p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Approve Contribution section -->
        <div class="bg-white rounded-lg shadow mb-8">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Pending Contributions</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="text-left text-xs font-medium text-gray-500 uppercase">
                                <th class="py-3 px-4">Member</th>
                                <th class="py-3 px-4">Amount</th>
                                <th class="py-3 px-4">Payment Method</th>
                                <th class="py-3 px-4">Date</th>
                                <th class="py-3 px-4">Notes</th>
                                <th class="py-3 px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pendingContributions)): ?>
                            <tr class="border-t">
                                <td colspan="6" class="py-3 px-4 text-center text-gray-500">No pending contributions</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($pendingContributions as $contribution): ?>
                                <tr class="border-t" id="contribution-row-<?php echo $contribution['id']; ?>">
                                    <td class="py-3 px-4"><?php echo htmlspecialchars($contribution['fullname']); ?></td>
                                    <td class="py-3 px-4">₦<?php echo number_format($contribution['amount'], 2); ?></td>
                                    <td class="py-3 px-4"><?php echo htmlspecialchars($contribution['payment_method']); ?></td>
                                    <td class="py-3 px-4"><?php echo date('M d, Y', strtotime($contribution['contribution_date'])); ?></td>
                                    <td class="py-3 px-4"><?php echo htmlspecialchars($contribution['notes']); ?></td>
                                   <td class="py-3 px-4 space-x-2">
                                    <button 
                                        onclick="approveContribution(<?php echo $contribution['id']; ?>)"
                                        class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm">
                                        Approve
                                    </button>
                                    <button 
                                        onclick="rejectContribution(<?php echo $contribution['id']; ?>)"
                                        class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm">
                                        Reject
                                    </button>
                                </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
    <div class="bg-white rounded-lg shadow mb-8">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">New User Registrations</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="text-left text-xs font-medium text-gray-500 uppercase">
                                <th class="py-3 px-4">Name</th>
                                <th class="py-3 px-4">Email</th>
                                <th class="py-3 px-4">Phone</th>
                                <th class="py-3 px-4">Join Date</th>
                                <th class="py-3 px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($newUsers as $user): ?>
                            <tr class="border-t" id="user-row-<?php echo $user['id']; ?>">
                                <td class="py-3 px-4"><?php echo htmlspecialchars($user['fullname']); ?></td>
                                <td class="py-3 px-4"><?php echo htmlspecialchars($user['email']); ?></td>
                                <td class="py-3 px-4"><?php echo htmlspecialchars($user['phone']); ?></td>
                                <td class="py-3 px-4"><?php echo date('M d, Y', strtotime($user['join_date'])); ?></td>
                                <td class="py-3 px-4">
                                    <button 
                                        onclick="approveUser(<?php echo $user['id']; ?>)"
                                        class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm">
                                        Approve
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function approveUser(userId) {
            if (confirm('Are you sure you want to approve this user?')) {
                $.ajax({
                    url: 'approve_user.php',
                    method: 'POST',
                    data: { user_id: userId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Remove the user row from the table
                            $(`#user-row-${userId}`).fadeOut(400, function() {
                                $(this).remove();
                                // If no more rows, maybe show a "No pending users" message
                                if ($('tbody tr').length === 0) {
                                    $('tbody').append('<tr><td colspan="5" class="py-3 px-4 text-center text-gray-500">No pending user registrations</td></tr>');
                                }
                            });
                            // Show success message
                            alert('User approved successfully!');
                        } else {
                            alert('Failed to approve user: ' + response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                        alert('An error occurred while approving the user. Please check the console for details.');
                    }
                });
            }
        }
        function approveContribution(contributionId) {
            if (confirm('Are you sure you want to approve this contribution?')) {
                $.ajax({
                    url: 'approve_contribution.php',
                    method: 'POST',
                    data: { contribution_id: contributionId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Remove the contribution row from the table
                            $(`#contribution-row-${contributionId}`).fadeOut(400, function() {
                                $(this).remove();
                                // If no more rows, show "No pending contributions" message
                                if ($('tbody tr').length === 0) {
                                    $('tbody').append('<tr><td colspan="6" class="py-3 px-4 text-center text-gray-500">No pending contributions</td></tr>');
                                }
                            });
                            // Update the dashboard stats
                            updateDashboardStats();
                            // Show success message
                            alert('Contribution approved successfully!');
                        } else {
                            alert('Failed to approve contribution: ' + response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                        alert('An error occurred while approving the contribution. Please check the console for details.');
                    }
                });
            }
        }
        
        function rejectContribution(contributionId) {
                if (confirm('Are you sure you want to reject this contribution? This action cannot be undone.')) {
                    $.ajax({
                        url: 'reject_contribution.php',
                        method: 'POST',
                        data: { contribution_id: contributionId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                // Remove the contribution row from the table
                                $(`#contribution-row-${contributionId}`).fadeOut(400, function() {
                                    $(this).remove();
                                    // If no more rows, show "No pending contributions" message
                                    if ($('tbody tr').length === 0) {
                                        $('tbody').append('<tr><td colspan="6" class="py-3 px-4 text-center text-gray-500">No pending contributions</td></tr>');
                                    }
                                });
                                // Update the dashboard stats
                                updateDashboardStats();
                                // Show success message
                                alert('Contribution rejected successfully!');
                            } else {
                                alert('Failed to reject contribution: ' + response.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX Error:', status, error);
                            alert('An error occurred while rejecting the contribution. Please check the console for details.');
                        }
                    });
                }
            }
        
        function updateDashboardStats() {
            $.ajax({
                url: 'get_dashboard_stats.php',
                method: 'GET',
                dataType: 'json',
                success: function(stats) {
                    // Update contribution stats
                    $('.contribution-total').text('₦' + stats.contributions.total.toLocaleString());
                    $('.contribution-today').text('₦' + stats.contributions.today.toLocaleString());
                    $('.contribution-month').text('₦' + stats.contributions.month.toLocaleString());
                    $('.contribution-week').text('₦' + stats.contributions.week.toLocaleString());
                }
            });
        }
</script>
</body>
</html>