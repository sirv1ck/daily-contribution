<?php
// config/database.php
define('DB_HOST', 'localhost');
define('DB_USER', 'earnquesthub_jpeters');
define('DB_PASS', 'Jpetersstores1$$');
define('DB_NAME', 'earnquesthub_jpeters');

// Database connection
function connectDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

// User registration process
function registerUser($data) {
    $conn = connectDB();
    
    // Validate and sanitize input
    $fullname = $conn->real_escape_string($data['fullname']);
    $email = $conn->real_escape_string($data['email']);
    $phone = $conn->real_escape_string($data['phone']);
    $password = password_hash($data['password'], PASSWORD_DEFAULT);
    $address = $conn->real_escape_string($data['address']);
    
    // Check if email already exists
    $check = $conn->query("SELECT id FROM users WHERE email = '$email'");
    if ($check->num_rows > 0) {
        return ['success' => false, 'message' => 'Email already registered'];
    }
    
    // Set initial status as inactive
    $sql = "INSERT INTO users (fullname, email, phone, password, address, join_date, status, created_at) 
            VALUES ('$fullname', '$email', '$phone', '$password', '$address', NOW(), 'inactive', NOW())";
    
    if ($conn->query($sql)) {
        return ['success' => true, 'message' => 'Registration successful. Your account is pending activation. Please contact administrator.'];
    } else {
        return ['success' => false, 'message' => 'Registration failed: ' . $conn->error];
    }
}

// Modify the loginUser function:
function loginUser($email, $password) {
    $conn = connectDB();
    
    $email = $conn->real_escape_string($email);
    $sql = "SELECT id, password, fullname, status FROM users WHERE email = '$email'";
    $result = $conn->query($sql);
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Check account status
            switch ($user['status']) {
                case 'inactive':
                    return [
                        'success' => false, 
                        'message' => 'Your account is not yet activated. Please contact the administrator.'
                    ];
                case 'suspended':
                    return [
                        'success' => false, 
                        'message' => 'Your account has been suspended. Please contact the administrator for more information.'
                    ];
                case 'active':
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['fullname'] = $user['fullname'];
                    
                    // Update last login
                    $update_sql = "UPDATE users SET last_login = NOW() WHERE id = " . $user['id'];
                    $conn->query($update_sql);
                    
                    return ['success' => true, 'message' => 'Login successful'];
                default:
                    return ['success' => false, 'message' => 'Invalid account status'];
            }
        }
    }
    return ['success' => false, 'message' => 'Invalid email or password'];
}

// Admin functions for managing user status
function activateUser($user_id) {
    $conn = connectDB();
    
    $user_id = (int)$user_id;
    $sql = "UPDATE users SET status = 'active' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'User activated successfully'];
    }
    return ['success' => false, 'message' => 'Failed to activate user'];
}

function suspendUser($user_id) {
    $conn = connectDB();
    
    $user_id = (int)$user_id;
    $sql = "UPDATE users SET status = 'suspended' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'User suspended successfully'];
    }
    return ['success' => false, 'message' => 'Failed to suspend user'];
}

function getInactiveUsers() {
    $conn = connectDB();
    
    $sql = "SELECT id, fullname, email, phone, address, join_date 
            FROM users 
            WHERE status = 'inactive' 
            ORDER BY join_date DESC";
    
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Profile management
class ProfileManager {
    private $conn;
    private $user_id;
    private $upload_dir = 'uploads/profile_images/';
    private $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    private $max_size = 5242880; // 5MB
    
    public function __construct($user_id) {
        $this->conn = connectDB();
        $this->user_id = $user_id;
        
        // Create upload directory if it doesn't exist
        if (!file_exists($this->upload_dir)) {
            mkdir($this->upload_dir, 0777, true);
        }
    }
    
    public function getUserProfile() {
    $sql = "SELECT fullname, email, phone, address, join_date, status, last_login, profile_image, is_admin, bank_name, account_name, account_number 
            FROM users 
            WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $this->user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}
    
    public function uploadProfileImage($file) {
        // Validate file
        if (!in_array($file['type'], $this->allowed_types)) {
            return [
                'success' => false,
                'message' => 'Invalid file type. Please upload a JPEG, PNG, or GIF image.'
            ];
        }
        
        if ($file['size'] > $this->max_size) {
            return [
                'success' => false,
                'message' => 'File is too large. Maximum size is 5MB.'
            ];
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $this->user_id . '_' . time() . '.' . $extension;
        $filepath = $this->upload_dir . $filename;
        
        // Remove old profile image if exists
        $oldImage = $this->getUserProfile()['profile_image'];
        if ($oldImage && file_exists($this->upload_dir . $oldImage)) {
            unlink($this->upload_dir . $oldImage);
        }
        
        // Upload new image
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return [
                'success' => true,
                'path' => $filename
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to upload image. Please try again.'
        ];
    }
    
        public function updateProfile($data) {
        $sql = "UPDATE users 
                SET fullname = ?, 
                    phone = ?, 
                    address = ?,
                    bank_name = ?,
                    account_name = ?,
                    account_number = ?,
                    last_login = CURRENT_TIMESTAMP";
        
        $params = [
            $data['fullname'], 
            $data['phone'], 
            $data['address'],
            $data['bank_name'],
            $data['account_name'],
            $data['account_number']
        ];
        $types = "ssssss";
        
        if (isset($data['profile_image'])) {
            $sql .= ", profile_image = ?";
            $params[] = $data['profile_image'];
            $types .= "s";
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $this->user_id;
        $types .= "i";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        return $stmt->execute();
    }
    
    // changePassword method remains the same
    public function changePassword($current_password, $new_password) {
        // Verify current password
        $sql = "SELECT password FROM users WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $this->user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if (password_verify($current_password, $result['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE users 
                          SET password = ?,
                              last_login = CURRENT_TIMESTAMP
                          WHERE id = ?";
            $stmt = $this->conn->prepare($update_sql);
            $stmt->bind_param("si", $hashed_password, $this->user_id);
            return $stmt->execute();
        }
        return false;
    }
}

// Contributions management
class ContributionManager {
    private $conn;
    private $user_id;
    
    public function __construct($user_id = null) {
        $this->conn = connectDB();
        $this->user_id = $user_id;
    }
    
    public function addContributionWithDate($amount, $payment_method, $notes = '', $contribution_date) {
        $sql = "INSERT INTO contributions (user_id, amount, payment_method, notes, contribution_date, status) 
                VALUES (?, ?, ?, ?, ?, 'pending')";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("idsss", $this->user_id, $amount, $payment_method, $notes, $contribution_date);
        return $stmt->execute();
    }
    
    public function approveContribution($contribution_id) {
        $this->conn->begin_transaction();
        
        try {
            // Get contribution details
            $stmt = $this->conn->prepare("SELECT user_id, amount FROM contributions WHERE id = ?");
            $stmt->bind_param("i", $contribution_id);
            $stmt->execute();
            $contribution = $stmt->get_result()->fetch_assoc();
            
            if (!$contribution) {
                throw new Exception("Contribution not found");
            }
            
            // Update contribution status
            $stmt = $this->conn->prepare("UPDATE contributions SET status = 'confirmed' WHERE id = ?");
            $stmt->bind_param("i", $contribution_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update contribution status");
            }
            
            // Update user's cash balance
            $stmt = $this->conn->prepare("UPDATE users SET cash = cash + ? WHERE id = ?");
            $stmt->bind_param("di", $contribution['amount'], $contribution['user_id']);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update user balance");
            }
            
            $this->conn->commit();
            return ['success' => true, 'message' => 'Contribution approved successfully'];
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function getPendingContributions() {
        $sql = "SELECT c.*, u.fullname, u.email 
                FROM contributions c 
                JOIN users u ON c.user_id = u.id 
                WHERE c.status = 'pending' 
                ORDER BY c.contribution_date DESC";
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getContributions($limit = 10) {
        if ($this->user_id) {
            $sql = "SELECT c.*, u.cash as current_balance 
                    FROM contributions c 
                    JOIN users u ON c.user_id = u.id 
                    WHERE c.user_id = ? 
                    ORDER BY c.contribution_date DESC 
                    LIMIT ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $this->user_id, $limit);
        } else {
            $sql = "SELECT c.*, u.fullname, u.email, u.cash as current_balance 
                    FROM contributions c 
                    JOIN users u ON c.user_id = u.id 
                    ORDER BY c.contribution_date DESC 
                    LIMIT ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $limit);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
        public function getYearlyTotal() {
        $sql = "SELECT COALESCE(SUM(amount), 0) as yearly_total 
                FROM contributions 
                WHERE user_id = ? 
                AND YEAR(contribution_date) = YEAR(CURRENT_DATE)
                AND status = 'confirmed'";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $this->user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return floatval($result['yearly_total']);
    }
    
}

// Rewards/Foodstuff management
class RewardManager {
    private $conn;
    private $user_id;
    
    public function __construct($user_id) {
        $this->conn = connectDB();
        $this->user_id = $user_id;
    }
    
    public function getAvailableRewards() {
        $sql = "SELECT f.id, f.name, f.description, f.quantity, f.unit 
                FROM foodstuff f 
                WHERE f.status = 'available' 
                AND f.year = YEAR(CURRENT_DATE)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function claimReward($foodstuff_id, $quantity) {
        // Start transaction
        $this->conn->begin_transaction();
        try {
            // Check if enough quantity available
            $check_sql = "SELECT quantity FROM foodstuff 
                         WHERE id = ? AND status = 'available'";
            $stmt = $this->conn->prepare($check_sql);
            $stmt->bind_param("i", $foodstuff_id);
            $stmt->execute();
            $available = $stmt->get_result()->fetch_assoc();
            
            if ($available['quantity'] >= $quantity) {
                // Create distribution record
                $dist_sql = "INSERT INTO foodstuff_distribution 
                            (user_id, foodstuff_id, quantity, distribution_date, status) 
                            VALUES (?, ?, ?, NOW(), 'pending')";
                $stmt = $this->conn->prepare($dist_sql);
                $stmt->bind_param("iii", $this->user_id, $foodstuff_id, $quantity);
                $stmt->execute();
                
                // Update foodstuff quantity
                $update_sql = "UPDATE foodstuff 
                              SET quantity = quantity - ? 
                              WHERE id = ?";
                $stmt = $this->conn->prepare($update_sql);
                $stmt->bind_param("ii", $quantity, $foodstuff_id);
                $stmt->execute();
                
                $this->conn->commit();
                return true;
            }
            throw new Exception("Insufficient quantity available");
        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }
}

// Events management
class EventManager {
    private $conn;
    
    public function __construct() {
        $this->conn = connectDB();
    }
    
    public function getUpcomingEvents() {
        $sql = "SELECT id, title, description, event_date, location, event_type 
                FROM events 
                WHERE event_date > NOW() 
                AND status = 'upcoming' 
                ORDER BY event_date 
                LIMIT 5";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } 
    public function getEvents($search = '') {
        $sql = "SELECT id, title, description, event_date, location, event_type, 
                       status, created_at,
                       (SELECT COUNT(*) FROM event_registrations WHERE event_id = events.id) as registrations
                FROM events 
                WHERE title LIKE ? OR description LIKE ?
                ORDER BY event_date DESC";
        
        $search = "%$search%";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $search, $search);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getEvent($id) {
        $stmt = $this->conn->prepare("SELECT * FROM events WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function addEvent($data) {
        $sql = "INSERT INTO events (title, description, event_date, location, event_type, 
                                  status, max_participants, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssssssi", 
            $data['title'],
            $data['description'],
            $data['event_date'],
            $data['location'],
            $data['event_type'],
            $data['status'],
            $data['max_participants']
        );
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Event added successfully'];
        }
        return ['success' => false, 'message' => 'Error adding event: ' . $this->conn->error];
    }
    
    public function updateEvent($id, $data) {
        $sql = "UPDATE events SET 
                title = ?, description = ?, event_date = ?,
                location = ?, event_type = ?, status = ?,
                max_participants = ?
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssssssii", 
            $data['title'],
            $data['description'],
            $data['event_date'],
            $data['location'],
            $data['event_type'],
            $data['status'],
            $data['max_participants'],
            $id
        );
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Event updated successfully'];
        }
        return ['success' => false, 'message' => 'Error updating event: ' . $this->conn->error];
    }
    
    public function deleteEvent($id) {
        // Check if there are any registrations
        $check = $this->conn->prepare("SELECT COUNT(*) as count FROM event_registrations WHERE event_id = ?");
        $check->bind_param("i", $id);
        $check->execute();
        $result = $check->get_result()->fetch_assoc();
        
        if ($result['count'] > 0) {
            return ['success' => false, 'message' => 'Cannot delete event with existing registrations'];
        }
        
        $stmt = $this->conn->prepare("DELETE FROM events WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Event deleted successfully'];
        }
        return ['success' => false, 'message' => 'Error deleting event'];
    }
    
    public function getRegistrations($event_id) {
        $sql = "SELECT u.fullname, u.email, u.phone, er.registration_date, er.attendance_status
                FROM event_registrations er
                JOIN users u ON er.user_id = u.id
                WHERE er.event_id = ?
                ORDER BY er.registration_date DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}


// StoreManager class 
class StoreManager {
    private $conn;
    private $items_per_page = 12;
    
    public function __construct() {
        $this->conn = connectDB();
    }
    
    public function getAllProducts($page = 1, $category = null) {
        $offset = ($page - 1) * $this->items_per_page;
        
        if ($category) {
            $sql = "SELECT p.*, u.fullname as seller_name 
                   FROM store_products p
                   LEFT JOIN users u ON p.user_id = u.id
                   WHERE p.status = 'available' AND p.category = ?
                   ORDER BY p.created_at DESC 
                   LIMIT ? OFFSET ?";
            
            if ($stmt = $this->conn->prepare($sql)) {
                $stmt->bind_param("sii", $category, $this->items_per_page, $offset);
            } else {
                error_log("Failed to prepare category statement: " . $this->conn->error);
                return [];
            }
        } else {
            $sql = "SELECT p.*, u.fullname as seller_name 
                   FROM store_products p
                   LEFT JOIN users u ON p.user_id = u.id
                   WHERE p.status = 'available'
                   ORDER BY p.created_at DESC 
                   LIMIT ? OFFSET ?";
            
            if ($stmt = $this->conn->prepare($sql)) {
                $stmt->bind_param("ii", $this->items_per_page, $offset);
            } else {
                error_log("Failed to prepare statement: " . $this->conn->error);
                return [];
            }
        }
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } else {
            error_log("Failed to execute statement: " . $stmt->error);
            return [];
        }
    }
    
    public function getTotalPages($category = null) {
        if ($category) {
            $sql = "SELECT COUNT(*) as total FROM store_products WHERE status = 'available' AND category = ?";
            if ($stmt = $this->conn->prepare($sql)) {
                $stmt->bind_param("s", $category);
            } else {
                return 1;
            }
        } else {
            $sql = "SELECT COUNT(*) as total FROM store_products WHERE status = 'available'";
            if (!($stmt = $this->conn->prepare($sql))) {
                return 1;
            }
        }
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            return ceil($row['total'] / $this->items_per_page);
        }
        return 1;
    }
    
    public function getFeaturedProducts($limit = 3) {
        $sql = "SELECT p.*, u.fullname as seller_name 
                FROM store_products p
                LEFT JOIN users u ON p.user_id = u.id
                WHERE p.status = 'available' 
                ORDER BY p.created_at DESC 
                LIMIT ?";
        
        if ($stmt = $this->conn->prepare($sql)) {
            $stmt->bind_param("i", $limit);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                return $result->fetch_all(MYSQLI_ASSOC);
            }
        }
        return [];
    }
    
    public function addProduct($data, $user_id) {
        // Handle image upload first
        $image_url = null;
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === 0) {
            $upload_dir = 'uploads/products/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                $image_url = $upload_path;
            }
        }
        
        $sql = "INSERT INTO store_products (
                    name, description, price, category, image_url,
                    phone_number, whatsapp_number, user_id, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
        
        if ($stmt = $this->conn->prepare($sql)) {
            $stmt->bind_param("ssdssssi",
                $data['name'],
                $data['description'],
                $data['price'],
                $data['category'],
                $image_url,
                $data['phone_number'],
                $data['whatsapp_number'],
                $user_id
            );
            
            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Product added successfully and pending approval'
                ];
            }
        }
        
        return [
            'success' => false,
            'message' => 'Error adding product: ' . $this->conn->error
        ];
    }
}

// Example usage in a controller file:
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'update_profile':
            $profile = new ProfileManager($_SESSION['user_id']);
            $result = $profile->updateProfile($_POST);
            echo json_encode(['success' => $result]);
            break;
            
        case 'add_contribution':
            $contributions = new ContributionManager($_SESSION['user_id']);
            $result = $contributions->addContribution(
                $_POST['amount'],
                $_POST['payment_method']
            );
            echo json_encode(['success' => $result]);
            break;
            
        case 'claim_reward':
            $rewards = new RewardManager($_SESSION['user_id']);
            $result = $rewards->claimReward(
                $_POST['foodstuff_id'],
                $_POST['quantity']
            );
            echo json_encode(['success' => $result]);
            break;
            
        case 'register_event':
            $events = new EventManager($_SESSION['user_id']);
            $result = $events->registerForEvent($_POST['event_id']);
            echo json_encode(['success' => $result]);
            break;
    }
}
?>