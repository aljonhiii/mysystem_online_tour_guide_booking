<?php
session_start();
require_once "../classes/database.php";

// ✅ Restrict to admin only
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../log-in/login.php");
    exit;
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        $db = new Database();
        $conn = $db->connect();

        // ✅ Soft delete (instead of deleting, we mark user as deleted)
        $stmt = $conn->prepare("UPDATE users SET is_deleted = 1 WHERE id = ?");
        $stmt->execute([$id]);

        $_SESSION['message'] = "User soft deleted successfully.";
        header("Location: see-users.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['message'] = "Error deleting user: " . $e->getMessage();
        header("Location: see-users.php");
        exit;
    }
} else {
    $_SESSION['message'] = "Invalid user ID.";
    header("Location: see-users.php");
    exit;
}
?>
