<?php
session_start();
require_once "../classes/database.php";

// Only logged-in guides can access
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'guide') {
    exit('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('Invalid request method');
}

$request_id = $_POST['request_id'] ?? null;
$type = $_POST['type'] ?? null; // hire or custom
$action = $_POST['action'] ?? null; // accept, reject, start, complete

if (!$request_id || !$type || !in_array($type, ['hire', 'custom']) || !in_array($action, ['accept', 'reject', 'start', 'complete'])) {
    exit('Invalid request');
}

$db = new Database();
$conn = $db->connect();

// Get guide ID
$stmt = $conn->prepare("SELECT id FROM guides WHERE user_id=?");
$stmt->execute([$_SESSION['user_id']]);
$guide = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$guide) exit('Guide not found');
$guide_id = $guide['id'];

// Map current and new statuses
$status_map = [
    'accept' => 'pending',
    'reject' => 'pending',
    'start' => 'accepted',
    'complete' => 'ongoing'
];
$new_status_map = [
    'accept' => 'accepted',
    'reject' => 'rejected',
    'start' => 'ongoing',
    'complete' => 'completed'
];

$current_status = $status_map[$action];
$new_status = $new_status_map[$action];

// ✅ Function: Check if guide already has accepted/ongoing tours for that day
function isDateBooked($conn, $guide_id, $date)
{
    // Hire requests
    $stmt = $conn->prepare("SELECT COUNT(*) FROM hire_requests WHERE guide_id=? AND tour_date=? AND status IN ('accepted', 'ongoing')");
    $stmt->execute([$guide_id, $date]);
    $hireCount = $stmt->fetchColumn();

    // Custom requests
    $stmt = $conn->prepare("SELECT COUNT(*) FROM custom_requests WHERE guide_id=? AND day=? AND status IN ('accepted', 'ongoing')");
    $stmt->execute([$guide_id, $date]);
    $customCount = $stmt->fetchColumn();

    return ($hireCount + $customCount) > 0;
}

// --- HIRE REQUEST ---
if ($type === 'hire') {
    $stmt = $conn->prepare("SELECT * FROM hire_requests WHERE id=? AND guide_id=? AND status=?");
    $stmt->execute([$request_id, $guide_id, $current_status]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        exit($action === 'start' ? 'Cannot start tour: request not accepted yet' :
            ($action === 'complete' ? 'Cannot complete tour: tour not started yet' : 'Hire request not found or already processed'));
    }

    if ($action === 'accept') {
        $tour_date = $request['tour_date'];
        if (isDateBooked($conn, $guide_id, $tour_date)) {
            exit('already booked for this day');
        }
    }

    $stmt = $conn->prepare("UPDATE hire_requests SET status=? WHERE id=?");
    $updated = $stmt->execute([$new_status, $request_id]);

}
// --- CUSTOM REQUEST ---
else {
    $stmt = $conn->prepare("SELECT * FROM custom_requests WHERE id=? AND (guide_id IS NULL OR guide_id=?) AND status=?");
    $stmt->execute([$request_id, $guide_id, $current_status]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request && in_array($action, ['start', 'complete'])) {
        $stmt = $conn->prepare("SELECT * FROM custom_requests WHERE id=? AND guide_id=? AND status=?");
        $stmt->execute([$request_id, $guide_id, $current_status]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$request) {
        exit($action === 'start' ? 'Cannot start tour: request not accepted yet' :
            ($action === 'complete' ? 'Cannot complete tour: tour not started yet' : 'Custom request not found or unauthorized'));
    }

    if ($action === 'accept') {
        $tour_date = $request['day']; // use 'day' field
        if (isDateBooked($conn, $guide_id, $tour_date)) {
            exit('already booked for this day');
        }
    }

    $assigned_guide = in_array($action, ['accept', 'start', 'complete']) ? $guide_id : null;
    $accepted_by = in_array($action, ['accept', 'start', 'complete']) ? $guide_id : null;

    $stmt = $conn->prepare("UPDATE custom_requests SET status=?, guide_id=?, accepted_by=? WHERE id=?");
    $updated = $stmt->execute([$new_status, $assigned_guide, $accepted_by, $request_id]);
}

echo $updated ? 'success' : 'Failed to update';
exit;
?>
