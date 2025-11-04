<?php
session_start();
require_once "../classes/database.php";
require '../vendor/autoload.php'; // PHPMailer autoload
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Only logged-in users
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: ../log-in/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    $conn = $db->connect();

    $user_id = $_SESSION['user_id'];
    $guide_id = $_POST['guide_id'] ?? null;
    $message = trim($_POST['message'] ?? '');
    $payment = $_POST['payment'] ?? null;
    $payment_mode = $_POST['payment_mode'] ?? null;
    $tour_date = $_POST['tour_date'] ?? null;

    $errors = [];

    // Validate required fields
    if (!$guide_id || !$message || !$payment || !$payment_mode || !$tour_date) {
        $errors[] = "Please fill in all required fields including the Tour Date.";
    }

    // Validate tour date (today or future)
    $today = date('Y-m-d');
    if ($tour_date && $tour_date < $today) {
        $errors[] = "Tour Date cannot be in the past. Please select today or a future date.";
    }

    // Validate payment
    if (!is_numeric($payment) || $payment <= 0) {
        $errors[] = "Payment must be a positive number.";
    }

    // If there are validation errors
    if (!empty($errors)) {
        $_SESSION['error'] = implode(" ", $errors);
        header("Location: hire-a-guide.php");
        exit;
    }

    try {
        // Insert hire request including tour_date
        $stmt = $conn->prepare("
            INSERT INTO hire_requests (user_id, guide_id, message, payment, payment_mode, tour_date, status)
            VALUES (?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$user_id, $guide_id, $message, $payment, $payment_mode, $tour_date]);

        // Fetch guide email
        $stmt = $conn->prepare("
            SELECT u.email, u.fname, u.lname 
            FROM guides g
            JOIN users u ON g.user_id = u.id
            WHERE g.id = ?
        ");
        $stmt->execute([$guide_id]);
        $guide = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($guide) {
            // Send email to guide
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'ae202403715@wmsu.edu.ph'; // Your Gmail
                $mail->Password = 'uobxbdncxuntchbb';       // Your Gmail App Password
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('ae202403715@wmsu.edu.ph', 'Zamboanga Tour');
                $mail->addAddress($guide['email'], $guide['fname'].' '.$guide['lname']);

                $mail->isHTML(true);
                $mail->Subject = 'New Hire Request from a User';
                $mail->Body = "
                    <h3>New Hire Request</h3>
                    <p>You have received a new hire request from a user.</p>
                    <p><strong>Message:</strong> {$message}</p>
                    <p><strong>Payment:</strong> ₱{$payment} ({$payment_mode})</p>
                    <p><strong>Tour Date:</strong> ".date("M d, Y", strtotime($tour_date))."</p>
                    <p>Please log in to your account to accept or reject this request.</p>
                ";

                $mail->send();
            } catch (Exception $e) {
                $_SESSION['error'] = "Request sent but email could not be delivered.";
            }
        }

        $_SESSION['success'] = "Your request has been sent successfully! The guide has been notified.";

    } catch (PDOException $e) {
        $_SESSION['error'] = "Error submitting request: " . $e->getMessage();
    }

    header("Location: hire-a-guide.php");
    exit;
}
?>
