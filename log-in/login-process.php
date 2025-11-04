<?php
session_start();
require_once "../classes/database.php";

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Invalid request."]);
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($email) || empty($password)) {
    echo json_encode(["success" => false, "message" => "Please enter both email and password."]);
    exit;
}

try {
    $db = new Database();
    $conn = $db->connect();

    // Fetch user by email
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(["success" => false, "message" => "Invalid email or password."]);
        exit;
    }

    $isPasswordValid = false;

    // For admin: plain text check
    if ($user['user_type'] === 'admin') {
        $isPasswordValid = ($password === $user['password']);
    } else {
        // For users and guides: hashed password
        $isPasswordValid = password_verify($password, $user['password']);
    }

    if (!$isPasswordValid) {
        echo json_encode(["success" => false, "message" => "Invalid email or password."]);
        exit;
    }

    // For guides, check guide table status
    if ($user['user_type'] === 'guide') {
        $stmt = $conn->prepare("SELECT status FROM guides WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $guide = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$guide) {
            echo json_encode(["success" => false, "message" => "Guide profile not found."]);
            exit;
        }

        if ($guide['status'] === 'pending') {
            echo json_encode(["success" => false, "message" => "Your guide account is still pending approval."]);
            exit;
        } elseif ($guide['status'] === 'rejected') {
            echo json_encode(["success" => false, "message" => "Your guide account has been rejected."]);
            exit;
        }
    }

    // Login success
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_type'] = $user['user_type'];
    $_SESSION['name'] = $user['fname'] .
                        (!empty($user['mname']) ? ' '.$user['mname'] : '') .
                        ' ' . $user['lname'];

    echo json_encode(["success" => true, "user_type" => $user['user_type']]);
    exit;

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    exit;
}
?>
