<?php
session_start();
header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// Rate Limiting (Basic)
if (!isset($_SESSION['last_submit'])) {
    $_SESSION['last_submit'] = 0;
}
if (time() - $_SESSION['last_submit'] < 10) {
    http_response_code(429);
    echo json_encode(["status" => "error", "message" => "Please wait before submitting again."]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed."]);
    exit;
}

// CSRF Protection (Check token if passed, though for static sites this is usually handled via hidden inputs or strict CORS)
// We'll enforce a strict check on required fields first.

// Load configuration
$config = require 'mail_config.php';

// Sanitize inputs and protect against Header Injection / XSS
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    // Remove newlines to prevent header injection in some basic implementations
    $data = str_replace(array("\r", "\n", "%0a", "%0d"), '', $data);
    return $data;
}

$name = sanitize_input($_POST["name"] ?? '');
$company = sanitize_input($_POST["company"] ?? '');
$email = filter_var($_POST["email"] ?? '', FILTER_SANITIZE_EMAIL);
$phone = sanitize_input($_POST["phone"] ?? '');
$service = sanitize_input($_POST["service"] ?? '');
$subject_input = sanitize_input($_POST["subject"] ?? '');
// Message can contain newlines, so we only htmlspecialchars it
$message = htmlspecialchars(trim($_POST["message"] ?? ''), ENT_QUOTES, 'UTF-8');

// Validate required fields
if (empty($name) || empty($email) || empty($message) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Please complete all required fields correctly."]);
    exit;
}

$_SESSION['last_submit'] = time();

$mail = new PHPMailer(true);

try {
    // Server settings
    // $mail->SMTPDebug = 2; // Enable for debugging
    $mail->isSMTP();
    $mail->Host       = $config['smtp_host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $config['smtp_username'];
    $mail->Password   = $config['smtp_password'];
    if (strtolower($config['smtp_encryption']) === 'ssl') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } else {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    }
    $mail->Port       = $config['smtp_port'];

    // Recipients
    $mail->setFrom($config['from_email'], $config['from_name']);
    $mail->addAddress($config['recipient_email']);
    $mail->addReplyTo($email, $name);

    // Content formatting exactly as requested
    $date_time = date('Y-m-d H:i:s');
    
    $body = "----------------------------------------\n\n";
    $body .= "New Website Enquiry\n\n";
    $body .= "Name:\n$name\n\n";
    $body .= "Company:\n$company\n\n";
    $body .= "Email:\n$email\n\n";
    $body .= "Phone:\n$phone\n\n";
    $body .= "Service:\n$service\n\n";
    $body .= "Subject:\n$subject_input\n\n";
    $body .= "Message:\n$message\n\n";
    $body .= "Submitted On:\n$date_time\n\n";
    $body .= "Website:\nPrajai Technology\n\n";
    $body .= "----------------------------------------\n";

    $mail->isHTML(false); // Plain text exactly as requested
    $mail->Subject = "New Website Enquiry from $name";
    if (!empty($subject_input)) {
        $mail->Subject = "New Enquiry: $subject_input";
    }
    
    $mail->Body = $body;

    $mail->send();
    http_response_code(200);
    echo json_encode(["status" => "success", "message" => "Thank You! Your message has been sent successfully."]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}"]);
}
?>