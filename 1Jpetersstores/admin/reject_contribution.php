<?php
session_start();
require_once '../config/database.php';
require_once 'check_admin.php';

// Ensure user is admin
checkAdmin();

header('Content-Type: application/json');

if (!isset($_POST['contribution_id'])) {
    echo json_encode(['success' => false, 'message' => 'Contribution ID is required']);
    exit;
}

$contribution_id = (int)$_POST['contribution_id'];

try {
    $conn = connectDB();
    
    // Start transaction
    $conn->begin_transaction();
    
    // Delete the contribution
    $stmt = $conn->prepare("DELETE FROM contributions WHERE id = ? AND status = 'pending'");
    $stmt->bind_param("i", $contribution_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Contribution rejected successfully']);
        } else {
            throw new Exception("Contribution not found or already processed");
        }
    } else {
        throw new Exception("Failed to reject contribution");
    }
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}