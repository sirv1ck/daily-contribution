<?php
session_start();
require_once '../config/database.php';
require_once 'check_admin.php';
checkAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    
    $conn = connectDB();
    $sql = "SELECT * FROM contributions WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($contribution = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'contribution' => $contribution
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Contribution not found'
        ]);
    }
    
    $stmt->close();
    $conn->close();
}
?>