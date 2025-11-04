<?php
session_start();
require_once "../classes/database.php";
require '../vendor/autoload.php'; // PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ✅ Restrict access
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'guide') {
    header("Location: ../log-in/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['request_id'])) {
    $request_id = $_POST['request_id'];
    $guide_id = $_SESSION['user_id'];

    $db = new Database();
    $conn = $db->connect();

    // ✅ Get user email info
    $stmt = $conn->prepare("
        SELECT u.email, u.fname, u.lname, h.id
        FROM hire_requests h
        JOIN users u ON u.id = h.user_id
        WHERE h.id = :id AND h.guide_id = :guide_id
    ");
    $stmt->bindValue(':id', $request_id, PDO::PARAM_INT);
    $stmt->bindValue(':guide_id', $guide_id, PDO::PARAM_INT);
    $stmt->execute();
    $hire = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$hire) {
        $_SESSION['error'] = "Request not found or unauthorized.";
        header("Location: guide-dashboard.php");
        exit;
    }

    // ✅ Update request status to 'accepted'
    $update = $conn->prepare("
        UPDATE hire_requests 
        SET status = 'accepted', accepted_by = :guide_id 
        WHERE id = :id
    ");
    $update->bindValue(':guide_id', $guide_id, PDO::PARAM_INT);
    $update->bindValue(':id', $request_id, PDO::PARAM_INT);
    $update->execute();

    // ✅ Send notification email to the user
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'ae202403715@wmsu.edu.ph'; // your Gmail
        $mail->Password = 'zxhv xzgy wtzb meii'; // Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('ae202403715@wmsu.edu.ph', 'Zamboanga City Guide Booking');
        $mail->addAddress($hire['email'], $hire['fname'] . ' ' . $hire['lname']);

        $mail->isHTML(true);
        $mail->Subject = 'Your Guide Request Has Been Accepted!';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;'>
                <div style='background: white; padding: 20px; border-radius: 10px;'>
                    <h2 style='color:#2a7ae2;'>Good news, {$hire['fname']}!</h2>
                    <p>Your guide has accepted your hire request. 🎉</p>
                    <p>They will contact you soon for further details about your trip.</p>
                    <hr>
                    <p style='font-size: 13px; color: #555;'>Thank you for using <b>Zamboanga City Guide Booking</b>!</p>
                </div>
            </div>
        ";

        $mail->send();
        $_SESSION['success'] = "Request accepted and email sent successfully!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Accepted, but email failed: {$mail->ErrorInfo}";
    }

    header("Location: guide-dashboard.php");
    exit;
}
?>
