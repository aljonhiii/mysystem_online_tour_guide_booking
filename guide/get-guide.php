<?php
session_start();
require_once "../classes/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'guide') exit;

$db = new Database();
$conn = $db->connect();

// Get guide ID
$stmt = $conn->prepare("SELECT id FROM guides WHERE user_id=?");
$stmt->execute([$_SESSION['user_id']]);
$guide = $stmt->fetch(PDO::FETCH_ASSOC);
$guide_id = $guide['id'] ?? 0;

// Fetch all accepted hire and custom request dates
$stmt = $conn->prepare("
    SELECT tour_date AS date FROM hire_requests WHERE guide_id=? AND status='accepted'
    UNION
    SELECT day AS date FROM custom_requests WHERE guide_id=? AND status='accepted'
");
$stmt->execute([$guide_id, $guide_id]);
$dates = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode($dates);
