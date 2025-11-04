<?php
require_once "../classes/database.php";

$db = new Database();
$conn = $db->connect();

$fname = "Admin";
$mname = "";
$lname = "User";
$email = "admin@example.com";
$password = password_hash("admin123", PASSWORD_DEFAULT); // hash it!
$user_type = "admin";

$stmt = $conn->prepare("INSERT INTO users (fname, mname, lname, email, password, user_type) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute([$fname, $mname, $lname, $email, $password, $user_type]);

echo "Admin created successfully!";
