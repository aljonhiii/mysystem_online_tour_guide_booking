<?php
session_start();
require_once "../classes/database.php";

// ✅ Only logged-in users can cancel their own requests
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: ../log-in/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'])) {
    $db = new Database();
    $conn = $db->connect();

    $user_id = $_SESSION['user_id'];
    $request_id = $_POST['request_id'];

    // ✅ Update only if the request belongs to the user and is still pending
    $stmt = $conn->prepare("
        UPDATE hire_requests
        SET status = 'cancelled'
        WHERE id = ? AND user_id = ? AND status = 'pending'
    ");
    $stmt->execute([$request_id, $user_id]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['success'] = "Your hire request has been cancelled successfully.";
    } else {
        $_SESSION['error'] = "Unable to cancel. It may have already been processed or does not exist.";
    }

    header("Location: my-bookings.php");
    exit;
} else {
    header("Location: my-bookings.php");
    exit;
}
?>
