<?php
session_start();
require_once "../classes/database.php";

// Only guides can access
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'guide') {
    exit('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('Invalid request method');
}

// Get POST values
$request_id = $_POST['request_id'] ?? null;
$action = $_POST['action'] ?? null; // accept, reject, start, complete

if (!$request_id || !in_array($action, ['accept','reject','start','complete'])) {
    exit('Invalid request');
}

$db = new Database();
$conn = $db->connect();

// Get guide internal ID
$stmt = $conn->prepare("SELECT id FROM guides WHERE user_id=?");
$stmt->execute([$_SESSION['user_id']]);
$guide = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$guide) exit('Guide profile not found');
$guide_id = $guide['id'];

// Determine current status needed for action
$current_status_map = [
    'accept' => 'pending',
    'reject' => 'pending',
    'start' => 'accepted',
    'complete' => 'ongoing'
];

// Determine new status after action
$new_status_map = [
    'accept' => 'accepted',
    'reject' => 'rejected',
    'start' => 'ongoing',
    'complete' => 'completed'
];

$current_status = $current_status_map[$action];
$new_status = $new_status_map[$action];

// Fetch request and check authorization
if ($action === 'accept' || $action === 'reject') {
    $stmt = $conn->prepare("SELECT * FROM custom_requests WHERE id=? AND (guide_id IS NULL OR guide_id=?) AND status=?");
    $stmt->execute([$request_id, $guide_id, $current_status]);
} else {
    // start or complete
    $stmt = $conn->prepare("SELECT * FROM custom_requests WHERE id=? AND guide_id=? AND status=?");
    $stmt->execute([$request_id, $guide_id, $current_status]);
}

$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    exit(match($action) {
        'start' => 'Cannot start tour: request not accepted yet',
        'complete' => 'Cannot complete tour: tour not started yet',
        default => 'Custom request not found or unauthorized',
    });
}

// Determine guide assignment and accepted_by
$assigned_guide = match($action) {
    'accept', 'start', 'complete' => $guide_id,
    'reject' => $request['guide_id']
};

$accepted_by = match($action) {
    'accept', 'start', 'complete' => $guide_id,
    'reject' => null
};

// Update the custom request
$stmt = $conn->prepare("UPDATE custom_requests SET status=?, guide_id=?, accepted_by=? WHERE id=?");
$stmt->execute([$new_status, $assigned_guide, $accepted_by, $request_id]);

echo $stmt->rowCount() > 0 ? 'success' : 'Failed to update';
exit;
?>
