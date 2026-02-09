<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'add_product') {
    $storeManager = new StoreManager();
    
    $result = $storeManager->addProduct($_POST, $_SESSION['user_id']);
    
    if ($result['success']) {
        $_SESSION['message'] = $result['message'];
        header('Location: store.php');
    } else {
        $_SESSION['error'] = $result['message'];
        header('Location: store.php');
    }
    exit;
}