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

    $guide_id = $_POST['guide_id'];
    $action = $_POST['action'];

    if ($action === 'approve') {
        $status = 'approved';
    } elseif ($action === 'reject') {
        $status = 'rejected';
    } else {
        $_SESSION['error'] = "Invalid action.";
        header("Location: index.php");
        exit;
    }

    $stmt = $conn->prepare("UPDATE guides SET status = ? WHERE id = ?");
    $stmt->execute([$status, $guide_id]);

    $_SESSION['success'] = "Guide request has been " . ucfirst($status) . ".";
    header("Location: admin-dashboard.php");
    exit;
}
