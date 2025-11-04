<?php
require_once "../classes/database.php";
session_start();

// ✅ Only admin can delete
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    exit("Unauthorized access");
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    exit("Invalid request");
}

$guide_id = intval($_GET['id']);
$db = new Database();
$conn = $db->connect();

try {
    $conn->beginTransaction();

    // ✅ Get user id from guide
    $stmt = $conn->prepare("SELECT user_id FROM guides WHERE id = ?");
    $stmt->execute([$guide_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("Guide not found");
    }

    // ✅ Soft delete guide
    $stmt = $conn->prepare("UPDATE guides SET is_deleted = 1 WHERE id = ?");
    $stmt->execute([$guide_id]);

    // ✅ Soft delete corresponding user
    $stmt = $conn->prepare("UPDATE users SET is_deleted = 1 WHERE id = ?");
    $stmt->execute([$user['user_id']]);

    $conn->commit();
    echo "Guide soft deleted successfully!";
} catch (Exception $e) {
    $conn->rollBack();
    echo "Error: " . $e->getMessage();
}
?>
