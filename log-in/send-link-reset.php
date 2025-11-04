<?php
require '../classes/database.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$db = new Database();
$conn = $db->connect(); // PDO connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    // Check if user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->bindValue(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Generate token
        $token = bin2hex(random_bytes(50));
        $expiry = date("Y-m-d H:i:s", strtotime('+1 hour'));

        // Update user with token
        $stmt = $conn->prepare("UPDATE users SET reset_token = :token, token_expiry = :expiry WHERE id = :id");
        $stmt->bindValue(':token', $token, PDO::PARAM_STR);
        $stmt->bindValue(':expiry', $expiry, PDO::PARAM_STR);
        $stmt->bindValue(':id', $user['id'], PDO::PARAM_INT);
        $stmt->execute();

        // Prepare reset link
        $reset_link = "http://localhost/mysystem/log-in/reset-password.php?token=$token";

        // Send email via PHPMailer
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'ae202403715@wmsu.edu.ph'; // your Gmail
            $mail->Password = 'uobxbdncxuntchbb';       // your 16-char app password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('ae202403715@wmsu.edu.ph', 'Zamboanga Tour System');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body = "
                <h3>Password Reset Request</h3>
                <p>Click the link below to reset your password (expires in 1 hour):</p>
                <a href='$reset_link'>$reset_link</a>
            ";

            $mail->send();
            echo "✅ Reset link sent! Check your email.";
        } catch (Exception $e) {
            echo "❌ Could not send email: {$mail->ErrorInfo}";
        }
    } else {
        echo "❌ Email not found!";
    }
}
?>
