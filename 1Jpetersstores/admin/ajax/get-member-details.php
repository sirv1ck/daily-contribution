<?php
// ajax/get-member-details.php
session_start();
require_once '../../config/database.php';
require_once '../check_admin.php';

// Verify CSRF token
if (!isset($_SERVER['HTTP_X_CSRF_TOKEN']) || !hash_equals($_SESSION['csrf_token'], $_SERVER['HTTP_X_CSRF_TOKEN'])) {
    header('HTTP/1.1 403 Forbidden');
    exit(json_encode(['success' => false, 'error' => 'Invalid CSRF token']));
}

// Check admin authentication
$admin_id = checkAdmin();
if (!$admin_id) {
    header('HTTP/1.1 403 Forbidden');
    exit(json_encode(['success' => false, 'error' => 'Unauthorized access']));
}

// Validate member_id
$member_id = filter_var($_GET['member_id'] ?? null, FILTER_VALIDATE_INT);
if (!$member_id) {
    header('HTTP/1.1 400 Bad Request');
    exit(json_encode(['success' => false, 'error' => 'Invalid member ID']));
}

try {
    $conn = connectDB();
    $memberManager = new MemberManager($conn, $admin_id);

    // Get member details and contributions
    $details = $memberManager->getMemberDetails($member_id);
    $contributions = $memberManager->getMemberContributions($member_id, 5); // Get last 5 contributions
    $stats = $memberManager->getMemberStats($member_id);

    // Combine all data
    $response = [
        'success' => true,
        'details' => array_merge($details, $stats),
        'contributions' => $contributions
    ];

    header('Content-Type: application/json');
    echo json_encode($response);

} catch (Exception $e) {
    error_log("AJAX Error: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while fetching member details'
    ]);
}
?>