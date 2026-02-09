<?php
session_start();
require_once '../config/database.php';
require_once 'check_admin.php';
checkAdmin();

class AdminContributionManager {
    private $conn;
    
    public function __construct() {
        $this->conn = connectDB();
    }
    
    public function getContributions($search = '') {
        $sql = "SELECT c.*, u.fullname, u.email, u.cash as current_balance 
                FROM contributions c 
                JOIN users u ON c.user_id = u.id 
                WHERE u.fullname LIKE ? OR u.email LIKE ?
                ORDER BY c.contribution_date DESC";
        
        $search = "%$search%";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $search, $search);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getContributionStats() {
        $sql = "SELECT 
                COUNT(*) as total_contributions,
                SUM(amount) as total_amount,
                AVG(amount) as avg_amount,
                COUNT(DISTINCT user_id) as contributing_members
                FROM contributions 
                WHERE YEAR(contribution_date) = YEAR(CURRENT_DATE)
                AND status = 'confirmed'";
        
        $result = $this->conn->query($sql);
        return $result->fetch_assoc();
    }
    
    private function generateTransactionReference() {
        return 'CONT-' . time() . '-' . rand(1000, 9999);
    }
    
    private function updateUserBalance($userId, $amount) {
        $sql = "UPDATE users SET cash = cash + ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("di", $amount, $userId);
        return $stmt->execute();
    }
    
    public function addContribution($data) {
        $this->conn->begin_transaction();
        
        try {
            // Set default status to confirmed for admin contributions
            $status = 'confirmed';
            $transactionRef = $this->generateTransactionReference();
            
            // Format the contribution date
            $contributionDate = !empty($data['contribution_date']) 
                ? date('Y-m-d H:i:s', strtotime($data['contribution_date']))
                : date('Y-m-d H:i:s');
            
            $sql = "INSERT INTO contributions (
                user_id, amount, payment_method, notes, 
                contribution_date, status, transaction_reference, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("idsssss", 
                $data['user_id'],
                $data['amount'],
                $data['payment_method'],
                $data['notes'],
                $contributionDate,
                $status,
                $transactionRef
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Error adding contribution");
            }
            
            // Update user's balance since it's automatically confirmed
            if (!$this->updateUserBalance($data['user_id'], $data['amount'])) {
                throw new Exception("Error updating user balance");
            }
            
            $this->conn->commit();
            return [
                'success' => true, 
                'message' => 'Contribution added successfully and user balance updated'
            ];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return [
                'success' => false, 
                'message' => 'Error processing contribution: ' . $e->getMessage()
            ];
        }
    }
    
    public function updateContribution($id, $data) {
        $this->conn->begin_transaction();
        
        try {
            // Get the original contribution details
            $sql = "SELECT amount, user_id, status FROM contributions WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $original = $stmt->get_result()->fetch_assoc();
            
            if (!$original) {
                throw new Exception("Contribution not found");
            }
            
            // Format the contribution date
            $contributionDate = !empty($data['contribution_date']) 
                ? date('Y-m-d H:i:s', strtotime($data['contribution_date']))
                : date('Y-m-d H:i:s');
            
            // Update the contribution
            $sql = "UPDATE contributions 
                    SET amount = ?, 
                        payment_method = ?, 
                        notes = ?,
                        status = ?,
                        contribution_date = ?
                    WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("dssssi", 
                $data['amount'],
                $data['payment_method'],
                $data['notes'],
                $data['status'],
                $contributionDate,
                $id
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Error updating contribution");
            }
            
            // Handle balance updates if amount changed or status changed
            if ($original['status'] !== 'confirmed' && $data['status'] === 'confirmed') {
                // New confirmation - add full amount
                if (!$this->updateUserBalance($original['user_id'], $data['amount'])) {
                    throw new Exception("Error updating user balance");
                }
            } elseif ($original['status'] === 'confirmed' && $data['status'] !== 'confirmed') {
                // Unconfirming - remove full amount
                if (!$this->updateUserBalance($original['user_id'], -$original['amount'])) {
                    throw new Exception("Error updating user balance");
                }
            } elseif ($original['status'] === 'confirmed' && $data['status'] === 'confirmed' 
                     && $original['amount'] != $data['amount']) {
                // Amount changed while confirmed - adjust difference
                $difference = $data['amount'] - $original['amount'];
                if (!$this->updateUserBalance($original['user_id'], $difference)) {
                    throw new Exception("Error updating user balance");
                }
            }
            
            $this->conn->commit();
            return [
                'success' => true, 
                'message' => 'Contribution and user balance updated successfully'
            ];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return [
                'success' => false, 
                'message' => 'Error updating contribution: ' . $e->getMessage()
            ];
        }
    }
    
    public function getMembers() {
        $sql = "SELECT id, fullname, email, cash as current_balance 
                FROM users 
                ORDER BY fullname";
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getContribution($id) {
        $sql = "SELECT c.*, u.fullname, u.email 
                FROM contributions c 
                JOIN users u ON c.user_id = u.id 
                WHERE c.id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $contributionManager = new AdminContributionManager();
    $response = ['success' => false, 'message' => 'Invalid action'];
    
    switch ($_POST['action']) {
        case 'add':
            $response = $contributionManager->addContribution($_POST);
            break;
            
        case 'update':
            if (isset($_POST['id'])) {
                $response = $contributionManager->updateContribution($_POST['id'], $_POST);
            }
            break;
            
        case 'search':
            $contributions = $contributionManager->getContributions($_POST['search'] ?? '');
            // Return the HTML for the table rows
            foreach ($contributions as $contribution) {
                echo '<tr>';
                echo '<td class="px-6 py-4">' . htmlspecialchars($contribution['fullname']) . '</td>';
                echo '<td class="px-6 py-4">$' . number_format($contribution['amount'], 2) . '</td>';
                echo '<td class="px-6 py-4">' . date('M d, Y', strtotime($contribution['contribution_date'])) . '</td>';
                echo '<td class="px-6 py-4">' . ucwords(str_replace('_', ' ', $contribution['payment_method'])) . '</td>';
                echo '<td class="px-6 py-4">';
                echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . 
                     ($contribution['status'] === 'confirmed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800') . '">';
                echo ucfirst($contribution['status']);
                echo '</span>';
                echo '</td>';
                echo '<td class="px-6 py-4">';
                echo '<button onclick="showModal(\'edit\', ' . $contribution['id'] . ')" class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>';
                echo '</td>';
                echo '</tr>';
            }
            exit;
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

$contributionManager = new AdminContributionManager();
$stats = $contributionManager->getContributionStats();
$members = $contributionManager->getMembers();
$contributions = $contributionManager->getContributions();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Contributions - Admin Panel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <style>
    .relative {
        transition: all 0.3s ease-out;
    }
    
    #contributionModal .relative {
        transform: translateY(4px);
        opacity: 0;
    }
    
    #contributionModal:not(.hidden) .relative {
        transform: translateY(0);
        opacity: 1;
    }
</style>
</head>
<body class="bg-gray-50">
    <?php include 'header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-gray-500 text-sm font-medium">Total Contributions</h3>
                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_contributions']); ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-gray-500 text-sm font-medium">Total Amount</h3>
                <p class="text-2xl font-bold text-gray-900">₦<?php echo number_format($stats['total_amount'], 2); ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-gray-500 text-sm font-medium">Average Contribution</h3>
                <p class="text-2xl font-bold text-gray-900">₦<?php echo number_format($stats['avg_amount'], 2); ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-gray-500 text-sm font-medium">Contributing Members</h3>
                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['contributing_members']); ?></p>
            </div>
        </div>

        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Manage Contributions</h2>
            <button onclick="showModal('add')" 
                    class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">
                Add Contribution
            </button>
        </div>

        <!-- Search Box -->
        <div class="mb-6">
            <input type="text" id="searchInput" placeholder="Search contributions..." 
                   class="w-full p-3 rounded-lg border border-gray-300 shadow-sm">
        </div>

        <!-- Contributions Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Member</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="contributionsTableBody">
                        <?php foreach ($contributions as $contribution): ?>
                        <tr>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($contribution['fullname']); ?></td>
                            <td class="px-6 py-4">₦<?php echo number_format($contribution['amount'], 2); ?></td>
                            <td class="px-6 py-4"><?php echo date('M d, Y', strtotime($contribution['contribution_date'])); ?></td>
                            <td class="px-6 py-4"><?php echo ucwords(str_replace('_', ' ', $contribution['payment_method'])); ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                           <?php echo $contribution['status'] === 'confirmed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                    <?php echo ucfirst($contribution['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <button onclick="showModal('edit', <?php echo $contribution['id']; ?>)" 
                                        class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Contribution Modal -->
    <div id="contributionModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4" id="modalTitle">Add Contribution</h3>
                <form id="contributionForm" onsubmit="return handleSubmit(event)">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="contributionId">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Member</label>
                        <select name="user_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <?php foreach ($members as $member): ?>
                            <option value="<?php echo $member['id']; ?>">
                                <?php echo htmlspecialchars($member['fullname']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Amount (₦)</label>
                        <input type="number" name="amount" step="0.01" required 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Contribution Date</label>
                        <input type="datetime-local" name="contribution_date" required 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                               value="<?php echo date('Y-m-d\TH:i'); ?>">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Payment Method</label>
                        <select name="payment_method" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cash">Cash</option>
                            <option value="check">Check</option>
                            <option value="credit_card">Credit Card</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="confirmed">Confirmed</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea name="notes" rows="2" 
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="button" onclick="closeModal()" 
                                class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 mr-2">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
// Function to show and populate the contribution modal
function showModal(action, id = null) {
    const modal = document.getElementById('contributionModal');
    const form = document.getElementById('contributionForm');
    const formAction = document.getElementById('formAction');
    const modalTitle = document.getElementById('modalTitle');
    const contributionId = document.getElementById('contributionId');
    
    // Reset form and set initial values
    form.reset();
    formAction.value = action;
    modalTitle.textContent = action === 'add' ? 'Add Contribution' : 'Edit Contribution';
    
    // If editing, fetch and populate contribution data
    if (action === 'edit' && id) {
        contributionId.value = id;
        getContribution(id);
    } else {
        contributionId.value = '';
    }
    
    if (action === 'add') {
        // Set current date and time as default for new contributions
        const now = new Date();
        const formattedDate = now.toISOString().slice(0, 16); // Format: YYYY-MM-DDTHH:mm
        document.querySelector('input[name="contribution_date"]').value = formattedDate;
    }
    
    // Show modal with fade-in effect
    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.querySelector('.relative').classList.add('transform', 'translate-y-0', 'opacity-100');
    }, 50);
    
    // Add click outside to close
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });
    
    // Add escape key to close
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeModal();
        }
    });
}

// Enhanced close modal function with fade-out effect
function closeModal() {
    const modal = document.getElementById('contributionModal');
    const modalContent = modal.querySelector('.relative');
    
    // Add fade-out effect
    modalContent.classList.add('transform', 'translate-y-4', 'opacity-0');
    
    // Hide modal after animation
    setTimeout(() => {
        modal.classList.add('hidden');
        modalContent.classList.remove('transform', 'translate-y-4', 'opacity-0');
    }, 300);
}

// Function to get contribution details for editing
async function getContribution(id) {
    try {
        const response = await fetch('get_contribution.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}`
        });
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('contributionId').value = data.contribution.id;
            document.querySelector('select[name="user_id"]').value = data.contribution.user_id;
            document.querySelector('input[name="amount"]').value = data.contribution.amount;
            document.querySelector('select[name="payment_method"]').value = data.contribution.payment_method;
            document.querySelector('select[name="status"]').value = data.contribution.status;
            document.querySelector('textarea[name="notes"]').value = data.contribution.notes;
            
            // Format date for datetime-local input
            const date = new Date(data.contribution.contribution_date);
            const formattedDate = date.toISOString().slice(0, 16); // Format: YYYY-MM-DDTHH:mm
            document.querySelector('input[name="contribution_date"]').value = formattedDate;
        }
    } catch (error) {
        console.error('Error fetching contribution:', error);
    }
}

// Handle form submission
async function handleSubmit(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    
    try {
        const response = await fetch('track_contributions.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            closeModal();
            // Refresh the contributions table
            searchContributions();
        }
        
        // Show notification
        alert(result.message);
    } catch (error) {
        console.error('Error submitting form:', error);
        alert('An error occurred while saving the contribution.');
    }
    
    return false;
}

// Search functionality
let searchTimeout;
const searchInput = document.getElementById('searchInput');

searchInput.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(searchContributions, 300);
});

async function searchContributions() {
    const searchTerm = searchInput.value;
    const formData = new FormData();
    formData.append('action', 'search');
    formData.append('search', searchTerm);
    
    try {
        const response = await fetch('admin_contributions.php', {
            method: 'POST',
            body: formData
        });
        const html = await response.text();
        document.getElementById('contributionsTableBody').innerHTML = html;
    } catch (error) {
        console.error('Error searching contributions:', error);
    }
}
</script>
</body>
</html>