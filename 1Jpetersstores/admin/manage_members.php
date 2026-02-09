<?php
session_start();
require_once '../config/database.php';
require_once 'check_admin.php';
checkAdmin();

function getStatusClass($status) {
    switch ($status) {
        case 'active':
            return 'bg-green-100 text-green-800';
        case 'inactive':
            return 'bg-gray-100 text-gray-800';
        case 'suspended':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

                        
class MemberManager {
    private $conn;
    
    public function __construct() {
        $this->conn = connectDB();
    }
    
    public function getMembers($search = '') {
        $sql = "SELECT id, fullname, email, phone, address, join_date, status, 
                       is_admin, last_login, profile_image, bank_name, 
                       account_name, account_number 
                FROM users 
                WHERE fullname LIKE ? OR email LIKE ? OR phone LIKE ?
                ORDER BY created_at DESC";
        $search = "%$search%";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sss", $search, $search, $search);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getMember($id) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function addMember($data) {
        // Validate email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }
        
        // Check if email exists
        $check = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $data['email']);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            return ['success' => false, 'message' => 'Email already exists'];
        }
        
        $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (
                    fullname, email, phone, password, address, status,
                    is_admin, join_date, created_at, profile_image,
                    bank_name, account_name, account_number
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?, ?, ?, ?
                )";
        
        $stmt = $this->conn->prepare($sql);
        $status = $data['status'] ?? 'active';
        $profile_image = $data['profile_image'] ?? null;
        
        $stmt->bind_param("ssssssisssss", 
            $data['fullname'],
            $data['email'],
            $data['phone'],
            $hashed_password,
            $data['address'],
            $status,
            $data['is_admin'],
            $profile_image,
            $data['bank_name'],
            $data['account_name'],
            $data['account_number']
        );
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Member added successfully'];
        }
        return ['success' => false, 'message' => 'Error adding member: ' . $this->conn->error];
    }
    
    public function updateMember($id, $data) {
        // Validate email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }
        
        // Check if email exists for other users
        $check = $this->conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check->bind_param("si", $data['email'], $id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            return ['success' => false, 'message' => 'Email already exists'];
        }
        
        $sql = "UPDATE users SET 
                fullname = ?, 
                email = ?, 
                phone = ?, 
                address = ?, 
                status = ?,
                is_admin = ?,
                profile_image = ?,
                bank_name = ?,
                account_name = ?,
                account_number = ?";
        
        $params = [
            $data['fullname'],
            $data['email'],
            $data['phone'],
            $data['address'],
            $data['status'],
            $data['is_admin'],
            $data['profile_image'],
            $data['bank_name'],
            $data['account_name'],
            $data['account_number']
        ];
        $types = "sssssissss";
        
        // Add password to update if provided
        if (!empty($data['password'])) {
            $sql .= ", password = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
            $types .= "s";
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $id;
        $types .= "i";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Member updated successfully'];
        }
        return ['success' => false, 'message' => 'Error updating member: ' . $this->conn->error];
    }
    
    public function updateProfileImage($id, $image_path) {
        $stmt = $this->conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
        $stmt->bind_param("si", $image_path, $id);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Profile image updated successfully'];
        }
        return ['success' => false, 'message' => 'Error updating profile image'];
    }
    
    public function updateLastLogin($id) {
        $stmt = $this->conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    public function deleteMember($id) {
        // Check if it's the last admin
        $check = $this->conn->prepare("SELECT COUNT(*) as admin_count FROM users WHERE is_admin = 1");
        $check->execute();
        $result = $check->get_result()->fetch_assoc();
        
        $member = $this->getMember($id);
        if ($member['is_admin'] == 1 && $result['admin_count'] <= 1) {
            return ['success' => false, 'message' => 'Cannot delete the last admin user'];
        }
        
        // Delete profile image if exists
        if (!empty($member['profile_image'])) {
            $image_path = $_SERVER['DOCUMENT_ROOT'] . '/uploads/profile_images/' . $member['profile_image'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        $stmt = $this->conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Member deleted successfully'];
        }
        return ['success' => false, 'message' => 'Error deleting member'];
    }
}

// Initialize MemberManager before using it
$memberManager = new MemberManager();
$members = $memberManager->getMembers();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $memberManager = new MemberManager();
    $response = ['success' => false, 'message' => 'Invalid action'];
    
    switch ($_POST['action']) {
        case 'add':
            $response = $memberManager->addMember($_POST);
            break;
            
        case 'update':
            if (isset($_POST['id'])) {
                $response = $memberManager->updateMember($_POST['id'], $_POST);
            }
            break;
            
        case 'delete':
            if (isset($_POST['id'])) {
                $response = $memberManager->deleteMember($_POST['id']);
            }
            break;
            
        case 'get':
            if (isset($_POST['id'])) {
                $member = $memberManager->getMember($_POST['id']);
                if ($member) {
                    $response = ['success' => true, 'data' => $member];
                }
            }
            break;
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Members - Admin Panel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
</head>
<body class="bg-gray-50">
    <?php include 'header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Manage Members</h2>
            <button onclick="showModal('add')" 
                    class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">
                Add Member
            </button>
        </div>

        <!-- Search Box -->
        <div class="mb-6">
            <input type="text" id="searchInput" placeholder="Search members..." 
                   class="w-full p-3 rounded-lg border border-gray-300 shadow-sm">
        </div>

        <!-- Members Table -->
          <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Join Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="membersTableBody">
                        <?php foreach ($members as $member): ?>
                        <tr>
                            <td class="px-6 py-4 flex items-center">
                                <?php if (!empty($member['profile_image'])): ?>
                                    <img src="/uploads/profile_images/<?php echo htmlspecialchars($member['profile_image']); ?>" 
                                         alt="Profile" class="w-10 h-10 rounded-full mr-3">
                                <?php else: ?>
                                    <div class="w-10 h-10 rounded-full bg-gray-200 mr-3 flex items-center justify-center">
                                        <i class="fas fa-user text-gray-400"></i>
                                    </div>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($member['fullname']); ?>
                            </td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($member['email']); ?></td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($member['phone']); ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php echo getStatusClass($member['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($member['status'])); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4"><?php echo date('M d, Y', strtotime($member['join_date'])); ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php echo $member['is_admin'] ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800'; ?>">
                                    <?php echo $member['is_admin'] ? 'Admin' : 'Member'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-3">
                                    <button onclick="showModal('edit', <?php echo $member['id']; ?>)" 
                                            class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteMember(<?php echo $member['id']; ?>)" 
                                            class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php if (!empty($member['last_login'])): ?>
                                    <span class="text-xs text-gray-500" title="Last Login">
                                        <i class="fas fa-clock mr-1"></i>
                                        <?php echo date('M d, Y H:i', strtotime($member['last_login'])); ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Member Modal -->
    <div id="memberModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4" id="modalTitle">Add Member</h3>
                <form id="memberForm" onsubmit="return handleSubmit(event)">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="memberId">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input type="text" name="fullname" required 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" required 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Phone</label>
                        <input type="tel" name="phone" required 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Address</label>
                        <textarea name="address" required 
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" name="password" id="passwordField"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <p class="text-sm text-gray-500 mt-1" id="passwordNote">
                            Leave blank to keep current password when editing
                        </p>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Role</label>
                        <select name="is_admin" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="0">Member</option>
                            <option value="1">Admin</option>
                        </select>
                    </div>
                    
                   <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Profile Image</label>
                    <input type="file" name="profile_image" accept="image/*" 
                           onchange="handleImagePreview(this)"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm">
                    <!-- Add this image preview div -->
                    <div class="mt-2">
                        <img id="imagePreview" src="" alt="Profile Preview" 
                             class="hidden w-32 h-32 object-cover rounded-lg border border-gray-300">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Bank Name</label>
                    <input type="text" name="bank_name" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Account Name</label>
                    <input type="text" name="account_name" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Account Number</label>
                    <input type="text" name="account_number" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
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
        // Show/hide modal
        // Global variable to store the current member's profile image
                let currentProfileImage = '';
                
                // Show/hide modal with enhanced functionality
                function showModal(action, id = null) {
                    const modal = document.getElementById('memberModal');
                    const form = document.getElementById('memberForm');
                    const passwordField = document.getElementById('passwordField');
                    const passwordNote = document.getElementById('passwordNote');
                    const imagePreview = document.getElementById('imagePreview');
                    
                    form.reset();
                    document.getElementById('formAction').value = action;
                    document.getElementById('modalTitle').textContent = 
                        action === 'add' ? 'Add Member' : 'Edit Member';
                    
                    if (action === 'edit') {
                        passwordField.required = false;
                        passwordNote.classList.remove('hidden');
                        getMember(id);
                    } else {
                        passwordField.required = true;
                        passwordNote.classList.add('hidden');
                        if (imagePreview) {
                            imagePreview.src = '';
                            imagePreview.classList.add('hidden');
                        }
                    }
                    
                    modal.classList.remove('hidden');
                }
                
                // Get member data for editing with enhanced fields
                async function getMember(id) {
                    try {
                        const formData = new FormData();
                        formData.append('action', 'get');
                        formData.append('id', id);
                        
                        const response = await fetch('manage_members.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.json();
                        if (result.success) {
                            const form = document.getElementById('memberForm');
                            const member = result.data;
                            
                            // Store member ID
                            document.getElementById('memberId').value = member.id;
                            
                            // Basic Information
                            form.fullname.value = member.fullname;
                            form.email.value = member.email;
                            form.phone.value = member.phone;
                            form.address.value = member.address;
                            form.status.value = member.status;
                            form.is_admin.value = member.is_admin;
                            
                            // Bank Details
                            if(form.bank_name) form.bank_name.value = member.bank_name || '';
                            if(form.account_name) form.account_name.value = member.account_name || '';
                            if(form.account_number) form.account_number.value = member.account_number || '';
                            
                            // Profile Image
                            currentProfileImage = member.profile_image;
                            const imagePreview = document.getElementById('imagePreview');
                            if (imagePreview) {
                                if (member.profile_image) {
                                    imagePreview.src = `/uploads/profile_images/${member.profile_image}`;
                                    imagePreview.classList.remove('hidden');
                                } else {
                                    imagePreview.src = '';
                                    imagePreview.classList.add('hidden');
                                }
                            }
                            
                            // Show last login if available
                            const lastLoginElement = document.getElementById('lastLoginInfo');
                            if (lastLoginElement && member.last_login) {
                                lastLoginElement.textContent = `Last Login: ${formatDate(member.last_login)}`;
                                lastLoginElement.classList.remove('hidden');
                            }
                        } else {
                            showAlert('Error loading member data', 'error');
                        }
                    } catch (error) {
                        showAlert('Error loading member data: ' + error.message, 'error');
                    }
                }
                
                // Add this function to handle image preview
                function handleImagePreview(input) {
                    const imagePreview = document.getElementById('imagePreview');
                    if (!imagePreview) return;
                
                    if (input.files && input.files[0]) {
                        const reader = new FileReader();
                        
                        reader.onload = function(e) {
                            imagePreview.src = e.target.result;
                            imagePreview.classList.remove('hidden');
                        }
                        
                        reader.readAsDataURL(input.files[0]);
                    } else {
                        imagePreview.src = '';
                        imagePreview.classList.add('hidden');
                    }
                }
                
                // Handle form submission with file upload
                async function handleSubmit(event) {
                    event.preventDefault();
                    const form = event.target;
                    const formData = new FormData(form);
                    
                    try {
                        // Handle profile image
                        const profileImage = form.profile_image.files[0];
                        if (profileImage) {
                            // Validate file type and size
                            if (!validateImage(profileImage)) {
                                return;
                            }
                        } else if (currentProfileImage) {
                            formData.append('current_profile_image', currentProfileImage);
                        }
                        
                        const response = await fetch('manage_members.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.json();
                        if (result.success) {
                            showAlert(result.message, 'success');
                            closeModal();
                            refreshTable();
                        } else {
                            showAlert(result.message, 'error');
                        }
                    } catch (error) {
                        showAlert('Error saving member: ' + error.message, 'error');
                    }
                }
                
                // Validate image file
                function validateImage(file) {
                    const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    const maxSize = 5 * 1024 * 1024; // 5MB
                    
                    if (!validTypes.includes(file.type)) {
                        showAlert('Please upload a valid image file (JPEG, PNG, or GIF)', 'error');
                        return false;
                    }
                    
                    if (file.size > maxSize) {
                        showAlert('Image file size must be less than 5MB', 'error');
                        return false;
                    }
                    
                    return true;
                }
                
                // Handle profile image preview
                function handleImagePreview(input) {
                    const imagePreview = document.getElementById('imagePreview');
                    if (input.files && input.files[0]) {
                        const reader = new FileReader();
                        
                        reader.onload = function(e) {
                            imagePreview.src = e.target.result;
                            imagePreview.classList.remove('hidden');
                        }
                        
                        reader.readAsDataURL(input.files[0]);
                    }
                }
                
                // Format date helper function
                function formatDate(dateString) {
                    const date = new Date(dateString);
                    return new Intl.DateTimeFormat('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    }).format(date);
                }
                
                // Refresh members table with enhanced data
                async function refreshTable(search = '') {
                    try {
                        const formData = new FormData();
                        formData.append('action', 'search');
                        formData.append('search', search);
                        
                        const response = await fetch('manage_members.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.text();
                        document.getElementById('membersTableBody').innerHTML = result;
                        
                        // Initialize any tooltips or other UI enhancements
                        initializeTooltips();
                    } catch (error) {
                        showAlert('Error refreshing table: ' + error.message, 'error');
                    }
                }
                
                // Initialize tooltips and other UI enhancements
                function initializeTooltips() {
                    // Add tooltip initialization code here if needed
                    // This is a placeholder for any UI enhancement initialization
                }
                
                // Show alert message with enhanced styling
                function showAlert(message, type) {
                    const alertDiv = document.createElement('div');
                    alertDiv.className = `fixed top-4 right-4 p-4 rounded-lg ${
                        type === 'success' ? 'bg-green-500' : 'bg-red-500'
                    } text-white shadow-lg transform transition-transform duration-300 ease-in-out`;
                    
                    const iconClass = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
                    alertDiv.innerHTML = `
                        <div class="flex items-center">
                            <i class="${iconClass} mr-2"></i>
                            <span>${message}</span>
                        </div>
                    `;
                    
                    document.body.appendChild(alertDiv);
                    
                    // Animate in
                    setTimeout(() => {
                        alertDiv.style.transform = 'translateY(10px)';
                    }, 100);
                    
                    // Animate out and remove
                    setTimeout(() => {
                        alertDiv.style.transform = 'translateY(-100%)';
                        setTimeout(() => {
                            alertDiv.remove();
                        }, 300);
                    }, 3000);
                }
    </script>
</body>
</html>