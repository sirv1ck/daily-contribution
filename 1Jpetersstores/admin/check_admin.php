<?php
// admin/check_admin.php
function checkAdmin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit;
    }
    
    try {
        $conn = connectDB();
        $stmt = $conn->prepare("SELECT id, is_admin FROM users WHERE id = ?");
        
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }
        
        $stmt->bind_param("i", $_SESSION['user_id']);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute statement: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (!$user || $user['is_admin'] != 1) {
            header('Location: ../dashboard.php');
            exit;
        }
        
        $stmt->close();
        return $user['id'];
        
    } catch (Exception $e) {
        error_log("Admin check error: " . $e->getMessage());
        header('Location: ../index.php');
        exit;
    }
}

// admin/manage_foodstuff.php
class AdminFoodstuffManager {
    private $conn;
    
    public function __construct() {
        $this->conn = connectDB();
    }
    
    public function getAllFoodstuff() {
        $sql = "SELECT * FROM foodstuff ORDER BY name";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function addFoodstuff($data) {
        $sql = "INSERT INTO foodstuff 
                (name, description, quantity, unit, status, year) 
                VALUES (?, ?, ?, ?, ?, YEAR(CURRENT_DATE))";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssiss", 
            $data['name'],
            $data['description'],
            $data['quantity'],
            $data['unit'],
            $data['status']
        );
        return $stmt->execute();
    }
    
    public function updateFoodstuff($id, $data) {
        $sql = "UPDATE foodstuff SET 
                name = ?, description = ?, quantity = ?, 
                unit = ?, status = ? 
                WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssissi", 
            $data['name'],
            $data['description'],
            $data['quantity'],
            $data['unit'],
            $data['status'],
            $id
        );
        return $stmt->execute();
    }
    
    public function deleteFoodstuff($id) {
        $stmt = $this->conn->prepare("DELETE FROM foodstuff WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}

// admin/manage_events.php
class AdminEventManager {
    private $conn;
    
    public function __construct() {
        $this->conn = connectDB();
    }
    
    public function addEvent($data) {
        $sql = "INSERT INTO events 
                (title, description, event_date, location, event_type, 
                status, max_participants) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
                
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            return ['success' => false, 
                   'message' => 'Database error: ' . $this->conn->error];
        }
        
        $event_date = date('Y-m-d H:i:s', strtotime($data['event_date']));
        $max_participants = intval($data['max_participants']);
        
        $stmt->bind_param("ssssssi", 
            $data['title'],
            $data['description'],
            $event_date,
            $data['location'],
            $data['event_type'],
            $data['status'],
            $max_participants
        );
        
        if ($stmt->execute()) {
            return ['success' => true, 
                   'message' => 'Event added successfully'];
        } else {
            return ['success' => false, 
                   'message' => 'Error adding event: ' . $stmt->error];
        }
    }
    
    public function getEvents($search = '') {
        $sql = "SELECT e.*, 
                (SELECT COUNT(*) FROM event_registrations 
                 WHERE event_id = e.id) as registrations 
                FROM events e 
                WHERE title LIKE ? OR description LIKE ? 
                ORDER BY event_date DESC";
                
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            return [];
        }
        
        $searchParam = "%$search%";
        $stmt->bind_param("ss", $searchParam, $searchParam);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getEvent($id) {
        $sql = "SELECT * FROM events WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            return null;
        }
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function getRegistrations($event_id) {
        $sql = "SELECT r.*, u.fullname, u.email, u.phone 
                FROM event_registrations r 
                JOIN users u ON r.user_id = u.id 
                WHERE r.event_id = ?";
                
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            return [];
        }
        
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}


?>