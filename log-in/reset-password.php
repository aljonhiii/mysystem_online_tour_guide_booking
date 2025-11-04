<?php
require '../classes/database.php'; // your Database class

$db = new Database();
$conn = $db->connect(); // get PDO connection

if (!isset($_GET['token'])) die("Invalid request");

$token = $_GET['token'];

// Prepare PDO statement
$stmt = $conn->prepare("SELECT id, token_expiry FROM users WHERE reset_token = :token");
$stmt->bindValue(':token', $token, PDO::PARAM_STR);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) die("Invalid or expired token");

// Check token expiry
if (strtotime($user['token_expiry']) < time()) die("Token expired");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password</title>
<style>
    body {
        font-family: 'Poppins', sans-serif;
        background: #f0f2f5;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
    }

    form {
        background: #fff;
        padding: 35px 30px;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        width: 100%;
        max-width: 400px;
        text-align: center;
    }

    form input[type="password"] {
        width: 100%;
        padding: 12px 15px;
        margin-bottom: 20px;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-size: 14px;
    }

    form button {
        width: 100%;
        padding: 12px;
        border: none;
        border-radius: 8px;
        background: #2563eb;
        color: #fff;
        font-size: 16px;
        cursor: pointer;
        transition: background 0.3s;
    }

    form button:hover {
        background: #1e40af;
    }

    form h2 {
        margin-bottom: 25px;
        color: #212832;
        font-weight: 600;
    }
</style>
</head>
<body>

<form action="update-password.php" method="POST">
    <h2>Reset Password</h2>
    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
    <input type="password" name="password" placeholder="New Password" required>
    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
    <button type="submit">Reset Password</button>
</form>

</body>
</html>
