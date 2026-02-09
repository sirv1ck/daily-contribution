<?php
session_start();
require_once '../config/database.php';
require_once 'check_admin.php';

// Set proper JSON header
header('Content-Type: application/json');

// Verify admin privileges
checkAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $conn = connectDB();
    $user_id = (int)$_POST['user_id'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update user status to active
        $sql = "UPDATE users SET status = 'active' WHERE id = ? AND status = 'inactive'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'User approved successfully']);
        } else {
            throw new Exception('No user was updated');
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to approve user: ' . $e->getMessage()]);
    }
    
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>