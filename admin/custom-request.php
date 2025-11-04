<?php
session_start();
require_once "../classes/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../log-in/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    $conn = $db->connect();

    $request_id = $_POST['request_id'] ?? null;
    $action = $_POST['action'] ?? null;

    if ($request_id && $action) {
        if ($action === 'approve') {
            $stmt = $conn->prepare("UPDATE custom_requests SET status='approved' WHERE id=?");
            $stmt->execute([$request_id]);
            $_SESSION['success'] = "Custom request approved.";
        } elseif ($action === 'reject') {
            $stmt = $conn->prepare("UPDATE custom_requests SET status='rejected' WHERE id=?");
            $stmt->execute([$request_id]);
            $_SESSION['success'] = "Custom request rejected.";
        }
    }
}

header("Location: see-all-bookings.php");
exit;
