<?php
// config/database.php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'jpetersmbs');

// Include database connection
function connectDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

// User class for managing members
class User {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function register($username, $password, $email, $fullname) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, password, email, fullname, join_date) 
                VALUES (?, ?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssss", $username, $hashed_password, $email, $fullname);
        return $stmt->execute();
    }
    
    public function login($username, $password) {
        $sql = "SELECT id, password FROM users WHERE username = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            return password_verify($password, $row['password']) ? $row['id'] : false;
        }
        return false;
    }
}

// Contribution tracking class
class Contributions {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function recordContribution($user_id, $amount) {
        $sql = "INSERT INTO contributions (user_id, amount, contribution_date) 
                VALUES (?, ?, NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("id", $user_id, $amount);
        return $stmt->execute();
    }
    
    public function getYearlyTotal($user_id) {
        $sql = "SELECT SUM(amount) as total FROM contributions 
                WHERE user_id = ? AND YEAR(contribution_date) = YEAR(CURRENT_DATE)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['total'] ?? 0;
    }
}

// Rewards/Foodstuff management class
class Rewards {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function addFoodstuffItem($name, $quantity, $year) {
        $sql = "INSERT INTO foodstuff (name, quantity, year) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sii", $name, $quantity, $year);
        return $stmt->execute();
    }
    
    public function checkEligibility($user_id) {
        $contributions = new Contributions($this->conn);
        $yearly_total = $contributions->getYearlyTotal($user_id);
        // Set minimum contribution requirement
        return $yearly_total >= 1000; // Adjust threshold as needed
    }
}

// Training/Events management class
class Events {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function createEvent($title, $description, $date, $location) {
        $sql = "INSERT INTO events (title, description, event_date, location) 
                VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssss", $title, $description, $date, $location);
        return $stmt->execute();
    }
    
    public function registerForEvent($user_id, $event_id) {
        $sql = "INSERT INTO event_registrations (user_id, event_id) VALUES (?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $event_id);
        return $stmt->execute();
    }
}