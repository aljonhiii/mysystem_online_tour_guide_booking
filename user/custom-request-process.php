<?php
session_start();
require_once "../classes/database.php";

// Only logged-in users can access
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: ../log-in/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    $conn = $db->connect();

    $user_id = $_POST['user_id'];
    $guide_id = $_POST['guide_id'];
    $place = trim($_POST['place']);
    $day = $_POST['day'];
    $hour = $_POST['hour'];
    $description = trim($_POST['description']);
    $payment = $_POST['payment'];
    $payment_mode = $_POST['payment_mode'];

    // Validate required fields
    if (empty($guide_id) || empty($place) || empty($day) || empty($hour) || empty($description) || empty($payment) || empty($payment_mode)) {
        $_SESSION['error'] = "Please fill in all required fields and select a guide.";
        header("Location: custom-request.php");
        exit;
    }

    // Validate date: cannot be in the past
    $today = date('Y-m-d');
    if ($day < $today) {
        $_SESSION['error'] = "The date cannot be in the past. Please select today or a future date.";
        header("Location: custom-request.php");
        exit;
    }

    try {
        // Check if guide already has a booking on this day
        $stmt = $conn->prepare("SELECT COUNT(*) FROM custom_requests WHERE guide_id=? AND day=? AND status='accepted'");
        $stmt->execute([$guide_id, $day]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['error'] = "This guide is already booked on $day. Please choose another date or guide.";
            header("Location: custom-request.php");
            exit;
        }

        // Insert custom request into DB
        $stmt = $conn->prepare("
            INSERT INTO custom_requests
            (user_id, guide_id, place, day, hour, description, payment, payment_mode, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt->execute([$user_id, $guide_id, $place, $day, $hour, $description, $payment, $payment_mode]);

        $_SESSION['success'] = "Your request has been sent to the selected guide successfully!";
        header("Location: custom-request.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error submitting request: " . $e->getMessage();
        header("Location: custom-request.php");
        exit;
    }
} else {
    // Redirect if not POST
    header("Location: custom-request.php");
    exit;
}
?>
