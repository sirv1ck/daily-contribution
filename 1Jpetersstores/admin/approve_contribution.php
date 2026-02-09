<?php
// admin/approve_contribution.php
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

$contributionManager = new ContributionManager();
$result = $contributionManager->approveContribution($_POST['contribution_id']);

echo json_encode($result);