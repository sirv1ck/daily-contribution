<?php
// get_foodstuff.php
session_start();
require_once '../config/database.php';
require_once 'check_admin.php';
checkAdmin();

if (isset($_GET['id'])) {
    $conn = connectDB();
    $id = (int)$_GET['id'];
    
    $sql = "SELECT id, name, description, quantity, unit, status 
            FROM foodstuff 
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode($row);
    } else {
        echo json_encode(['error' => 'Foodstuff not found']);
    }
    exit;
}

echo json_encode(['error' => 'Invalid request']);
?>