<?php
session_start();
require_once "../classes/database.php";
require '../vendor/autoload.php'; // PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Only guides can access
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'guide') {
    exit('error');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('error');
}

// Get POST data
$request_id = $_POST['request_id'] ?? null;
$action     = $_POST['action'] ?? null;

if (!$request_id || !in_array($action, ['accept','reject'])) {
    exit('error');
}

// Connect to DB
$db = new Database();
$conn = $db->connect();

// Fetch guide ID
$stmt = $conn->prepare("SELECT id FROM guides WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$guide = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$guide) exit('error');
$guide_id = $guide['id'];

// Determine new status
$new_status = $action === 'accept' ? 'accepted' : 'rejected';
$accepted_by = $action === 'accept' ? $guide_id : null;

// Update custom request (allow unassigned requests)
$stmt = $conn->prepare("
    UPDATE custom_requests
    SET status = ?, accepted_by = ?, guide_id = ?
    WHERE id = ? AND status = 'pending' AND (guide_id IS NULL OR guide_id = 0 OR guide_id = ?)
");
$stmt->execute([$new_status, $accepted_by, $guide_id, $request_id, $guide_id]);

if($stmt->rowCount() === 0){
    // Debug info if no row updated
    $stmt2 = $conn->prepare("SELECT * FROM custom_requests WHERE id = ?");
    $stmt2->execute([$request_id]);
    $request_debug = $stmt2->fetch(PDO::FETCH_ASSOC);
    exit("No rows updated. Current request data: " . json_encode($request_debug));
}

// Send email only if accepted
if($action === 'accept'){
    $stmt2 = $conn->prepare("
        SELECT u.email, u.fname, u.lname 
        FROM custom_requests cr 
        JOIN users u ON cr.user_id = u.id 
        WHERE cr.id = ?
    ");
    $stmt2->execute([$request_id]);
    $user = $stmt2->fetch(PDO::FETCH_ASSOC);

    if($user){
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'ae202403715@wmsu.edu.ph';
            $mail->Password = 'zxhv xzgy wtzb meii'; // app password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('ae202403715@wmsu.edu.ph','Zamboanga City Guide Booking');
            $mail->addAddress($user['email'], $user['fname'].' '.$user['lname']);
            $mail->isHTML(true);
            $mail->Subject = "Your Custom Request Has Been Accepted!";
            $mail->Body = "
                <div style='font-family:Arial,sans-serif;padding:20px;background:#f9f9f9;'>
                    <div style='background:#fff;border-radius:10px;padding:20px;'>
                        <h2>Good news, {$user['fname']}!</h2>
                        <p>Your custom tour request has <b>been accepted</b> by the guide.</p>
                        <p>They will contact you soon for further details.</p>
                        <br>
                        <p style='font-size:13px;color:#666;'>Thank you for using Zamboanga City Guide Booking!</p>
                    </div>
                </div>
            ";
            $mail->send();
        } catch(Exception $e){
            // Email failed, but request status is updated
        }
    }
}

echo 'success';
exit;
?>
