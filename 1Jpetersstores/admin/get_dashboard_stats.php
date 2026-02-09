<?php
// admin/get_dashboard_stats.php
session_start();
require_once '../config/database.php';
require_once 'check_admin.php';

// Ensure user is admin
checkAdmin();

header('Content-Type: application/json');

$dashboard = new DashboardStats();
$stats = [
    'contributions' => $dashboard->getContributionStats(),
    'users' => [
        'total' => $dashboard->getTotalUsers(),
        'new' => $dashboard->getNewUsersToday()
    ]
];

echo json_encode($stats);