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
        // Accept / Reject hire request
        if ($action === 'accept') {
            $stmt = $conn->prepare("UPDATE hire_requests SET status='accepted' WHERE id=?");
            $stmt->execute([$request_id]);
            $_SESSION['success'] = "Hire request accepted.";
        } elseif ($action === 'reject') {
            $stmt = $conn->prepare("UPDATE hire_requests SET status='rejected' WHERE id=?");
            $stmt->execute([$request_id]);
            $_SESSION['success'] = "Hire request rejected.";
        } elseif ($action === 'complete') {
            // Move hire request to bookings table
            $stmt = $conn->prepare("SELECT * FROM hire_requests WHERE id=?");
            $stmt->execute([$request_id]);
            $hr = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($hr) {
                $stmtInsert = $conn->prepare("INSERT INTO bookings (user_id, guide_id, payment, payment_mode, status, booking_date) VALUES (?, ?, ?, ?, 'completed', NOW())");
                $stmtInsert->execute([$hr['user_id'], $hr['guide_id'], $hr['payment'], $hr['payment_mode']]);
                // Update hire_request status to completed
                $stmtUpdate = $conn->prepare("UPDATE hire_requests SET status='completed' WHERE id=?");
                $stmtUpdate->execute([$request_id]);
                $_SESSION['success'] = "Booking marked as completed.";
            }
        }
    }
}

header("Location: see-all-bookings.php");
exit;
