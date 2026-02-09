<?php
// process_foodstuff.php
session_start();
require_once '../config/database.php';
require_once 'check_admin.php';
checkAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = connectDB();
    $response = ['success' => false, 'message' => ''];

    // Sanitize input
    $name = $conn->real_escape_string($_POST['name']);
    $description = $conn->real_escape_string($_POST['description']);
    $quantity = (int)$_POST['quantity'];
    $unit = $conn->real_escape_string($_POST['unit']);
    $status = $conn->real_escape_string($_POST['status']);
    
    if ($_POST['action'] === 'add') {
        $sql = "INSERT INTO foodstuff (name, description, quantity, unit, status, year) 
                VALUES (?, ?, ?, ?, ?, YEAR(CURRENT_DATE))";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssiss", $name, $description, $quantity, $unit, $status);
    } else {
        $id = (int)$_POST['foodstuff_id'];
        $sql = "UPDATE foodstuff 
                SET name = ?, description = ?, quantity = ?, unit = ?, status = ? 
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssissi", $name, $description, $quantity, $unit, $status, $id);
    }

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Foodstuff ' . ($_POST['action'] === 'add' ? 'added' : 'updated') . ' successfully';
    } else {
        $response['message'] = 'Error: ' . $conn->error;
    }

    echo json_encode($response);
    exit;
}
?>