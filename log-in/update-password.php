<?php
require '../classes/database.php';

$db = new Database();
$conn = $db->connect();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        die("Passwords do not match!");
    }

    // Validate token again
    $stmt = $conn->prepare("SELECT id, token_expiry FROM users WHERE reset_token = :token");
    $stmt->bindValue(':token', $token, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) die("Invalid or expired token");
    if (strtotime($user['token_expiry']) < time()) die("Token expired");

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Update password and remove token
    $stmt = $conn->prepare("UPDATE users SET password = :password, reset_token = NULL, token_expiry = NULL WHERE id = :id");
    $stmt->bindValue(':password', $hashed_password, PDO::PARAM_STR);
    $stmt->bindValue(':id', $user['id'], PDO::PARAM_INT);
    $stmt->execute();

    echo "✅ Password has been updated! You can now <a href='login.php'>login</a>.";
}
?>
