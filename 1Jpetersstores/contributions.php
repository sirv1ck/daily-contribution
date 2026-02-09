<?php
// contribution.php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Initialize database connection
$conn = connectDB();

// Process contribution submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contribution'])) {
    try {
        $amount = filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT);
        $payment_method = filter_var($_POST['payment_method'], FILTER_SANITIZE_STRING);
        $notes = filter_var($_POST['notes'], FILTER_SANITIZE_STRING);
        $contribution_date = filter_var($_POST['contribution_date'], FILTER_SANITIZE_STRING);
        
        // Validate date format only, removing future date restriction
        $selected_date = new DateTime($contribution_date);
        
        if ($amount <= 0) {
            throw new Exception("Please enter a valid amount");
        }
        
        // Check if date already has a contribution
        $check_date = $conn->prepare("SELECT COUNT(*) as count FROM contributions 
                                    WHERE user_id = ? AND DATE(contribution_date) = ?");
        $check_date->bind_param("is", $_SESSION['user_id'], $contribution_date);
        $check_date->execute();
        $date_result = $check_date->get_result()->fetch_assoc();
        
        if ($date_result['count'] > 0) {
            throw new Exception("A contribution already exists for the selected date");
        }
        
        $contribution = new ContributionManager($_SESSION['user_id']);
        if ($contribution->addContributionWithDate($amount, $payment_method, $notes, $contribution_date)) {
            $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                          Contribution submitted successfully! Awaiting admin approval.</div>';
        } else {
            throw new Exception("Failed to submit contribution");
        }
    } catch (Exception $e) {
        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                      Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// Get user's current balance
$stmt = $conn->prepare("SELECT cash FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user_balance = $stmt->get_result()->fetch_assoc()['cash'];

// Fetch contribution statistics
$stats = $conn->prepare("SELECT 
    COUNT(*) as total_contributions,
    SUM(CASE WHEN status = 'confirmed' THEN amount ELSE 0 END) as total_amount,
    AVG(CASE WHEN status = 'confirmed' THEN amount ELSE NULL END) as avg_amount
    FROM contributions 
    WHERE user_id = ? AND YEAR(contribution_date) = YEAR(CURRENT_DATE)");
$stats->bind_param("i", $_SESSION['user_id']);
$stats->execute();
$statistics = $stats->get_result()->fetch_assoc();

// Fetch recent contributions
$contribution = new ContributionManager($_SESSION['user_id']);
$recent_contributions = $contribution->getContributions(10);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contributions - JpetersMBS</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
</head>
<body class="bg-gray-50">
    <?php include 'includes/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <?php echo $message; ?>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Current Balance -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-gray-500 text-sm font-medium">Current Balance</h3>
                <p class="text-2xl font-bold text-gray-900">₦<?php echo number_format($user_balance, 2); ?></p>
            </div>
            
            <!-- Total Contributions -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-gray-500 text-sm font-medium">Total Contributions (This Year)</h3>
                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($statistics['total_contributions']); ?></p>
            </div>
            
            <!-- Total Amount -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-gray-500 text-sm font-medium">Total Amount (This Year)</h3>
                <p class="text-2xl font-bold text-gray-900">₦<?php echo number_format($statistics['total_amount'], 2); ?></p>
            </div>
            
            <!-- Average Contribution -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-gray-500 text-sm font-medium">Average Contribution</h3>
                <p class="text-2xl font-bold text-gray-900">₦<?php echo number_format($statistics['avg_amount'], 2); ?></p>
            </div>
        </div>
        
         <?php
// Add this code after your existing statistics fetch

// Get current month's contributions
$month_contributions = $conn->prepare("
    SELECT DAY(contribution_date) as day
    FROM contributions 
    WHERE user_id = ? 
    AND MONTH(contribution_date) = MONTH(CURRENT_DATE)
    AND YEAR(contribution_date) = YEAR(CURRENT_DATE)
");
$month_contributions->bind_param("i", $_SESSION['user_id']);
$month_contributions->execute();
$result = $month_contributions->get_result();

// Create an array of days with contributions
$contribution_days = array();
while ($row = $result->fetch_assoc()) {
    $contribution_days[] = $row['day'];
}

// Get the number of days in current month
$days_in_month = date('t');
$current_month = date('F Y');
$first_day = date('w', strtotime(date('Y-m-1')));
?>

<!-- Add this HTML after your statistics cards section -->
<div class="bg-white rounded-lg shadow p-6 mb-8">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Contribution Calendar - <?php echo $current_month; ?></h2>
    
    <div class="grid grid-cols-7 gap-2 mb-2">
        <?php
        $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        foreach ($days as $day) {
            echo "<div class='text-center text-sm font-medium text-gray-500'>$day</div>";
        }
        ?>
    </div>
    
    <div class="grid grid-cols-7 gap-2">
        <?php
        // Add empty cells for days before the first day of month
        for ($i = 0; $i < $first_day; $i++) {
            echo "<div class='h-10'></div>";
        }
        
        // Generate calendar days
        for ($day = 1; $day <= $days_in_month; $day++) {
            $has_contribution = in_array($day, $contribution_days);
            $is_today = $day == date('j');
            $cell_classes = "h-10 flex items-center justify-center rounded-lg ";
            
            if ($has_contribution) {
                $cell_classes .= "bg-green-100 text-green-800";
            } elseif ($is_today) {
                $cell_classes .= "border-2 border-purple-500 text-purple-500";
            } else {
                $cell_classes .= "bg-gray-50 text-gray-500";
            }
            
            echo "<div class='relative'>";
            echo "<div class='" . $cell_classes . "'>";
            echo "<span class='text-sm'>" . $day . "</span>";
            if ($has_contribution) {
                echo "<div class='absolute -top-1 -right-1'>";
                echo "<svg class='w-4 h-4 text-green-500' fill='currentColor' viewBox='0 0 20 20'>";
                echo "<path fill-rule='evenodd' d='M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z' clip-rule='evenodd'/>";
                echo "</svg>";
                echo "</div>";
            }
            echo "</div>";
            echo "</div>";
        }
        ?>
    </div>
    
    <div class="mt-4 flex items-center justify-end space-x-4">
        <div class="flex items-center">
            <div class="w-4 h-4 bg-green-100 rounded mr-2"></div>
            <span class="text-sm text-gray-600">Contribution made</span>
        </div>
        <div class="flex items-center">
            <div class="w-4 h-4 border-2 border-purple-500 rounded mr-2"></div>
            <span class="text-sm text-gray-600">Today</span>
        </div>
    </div>
</div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- New Contribution Form -->
                        <div class="bg-white rounded-lg shadow">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Make a Contribution</h2>
                    <form action="" method="POST" onsubmit="return validateForm()">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Amount (₦)</label>
                                <input type="number" name="amount" step="0.01" required
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Contribution Date</label>
                                <input type="date" name="contribution_date" required id="contribution_date"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Payment Method</label>
                                <select name="payment_method" required
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="cash">Cash</option>
                                    <option value="check">Check</option>
                                    <option value="credit_card">Credit Card</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Notes (Optional)</label>
                                <textarea name="notes" rows="2"
                                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500"></textarea>
                            </div>
                            
                            <button type="submit" name="submit_contribution"
                                    class="w-full bg-purple-600 text-white py-2 px-4 rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                                Submit Contribution
                            </button>
                        </div>
                    </form>
                </div>
            </div>


            <!-- Recent Contributions -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Contributions</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($recent_contributions as $contribution): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('M d, Y', strtotime($contribution['contribution_date'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        ₦<?php echo number_format($contribution['amount'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo ucwords(str_replace('_', ' ', $contribution['payment_method'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                   <?php echo $contribution['status'] === 'confirmed' ? 'bg-green-100 text-green-800' : 
                                                          ($contribution['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                                           'bg-red-100 text-red-800'); ?>">
                                            <?php echo ucfirst($contribution['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php if (empty($recent_contributions)): ?>
                        <p class="text-center text-gray-500 py-4">No contributions recorded yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
       <script>
document.addEventListener('DOMContentLoaded', function() {
    // Get all contribution dates
    <?php
    $stmt = $conn->prepare("SELECT DATE(contribution_date) as date FROM contributions WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $contributed_dates = array();
    while ($row = $result->fetch_assoc()) {
        $contributed_dates[] = $row['date'];
    }
    ?>
    
    const contributedDates = <?php echo json_encode($contributed_dates); ?>;
    const dateInput = document.getElementById('contribution_date');
    
    // Remove max date restriction
    
    // Only check for duplicate dates
    dateInput.addEventListener('input', function() {
        const selectedDate = this.value;
        if (contributedDates.includes(selectedDate)) {
            alert('You already have a contribution for this date. Please select another date.');
            this.value = '';
        }
    });
});

function validateForm() {
    // Remove future date validation - only check if date is selected
    const dateInput = document.getElementById('contribution_date');
    if (!dateInput.value) {
        alert('Please select a date');
        return false;
    }
    
    return true;
}
</script>
</body>
</html>