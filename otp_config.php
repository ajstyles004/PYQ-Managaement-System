<?php
// otp_config.php - Email and OTP utility functions
// Supports both real mail (PHPMailer) and file-based testing

require_once 'db.php';

// Email configuration
define('ENABLE_EMAIL_OTP', true);  // Set to true for real email, false for testing
define('OTP_EXPIRY_MINUTES', 10);  // OTP valid for 10 minutes
define('OTP_LENGTH', 6);            // 6-digit OTP

// SMTP Configuration (update with your email service details)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SENDER_EMAIL', 'your-email@gmail.com');
define('SENDER_NAME', 'Question Bank System');

/**
 * Generate a random OTP code
 * @return string OTP code (e.g., "123456")
 */
function generate_otp() {
    return str_pad(random_int(0, pow(10, OTP_LENGTH) - 1), OTP_LENGTH, '0', STR_PAD_LEFT);
}

/**
 * Send OTP to email using PHPMailer or file-based testing
 * @param string $email Email address
 * @param string $otp OTP code to send
 * @return array ['success' => bool, 'message' => string]
 */
function send_otp_email($email, $otp) {
    global $mysqli;
    
    if (!ENABLE_EMAIL_OTP) {
        // Testing mode: log OTP to file for development
        $log = "Email: {$email}, OTP: {$otp}, Time: " . date('Y-m-d H:i:s') . "\n";
        file_put_contents(__DIR__ . '/otp_log.txt', $log, FILE_APPEND);
        return ['success' => true, 'message' => 'OTP logged (development mode)'];
    }
    
    // Real email mode: use PHPMailer
    try {
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            // PHPMailer not installed; fallback to mail()
            return send_otp_via_php_mail($email, $otp);
        }
        
        $mail = new PHPMailer\PHPMailer\PHPMailer();
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->Port = SMTP_PORT;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        
        $mail->From = SENDER_EMAIL;
        $mail->FromName = SENDER_NAME;
        $mail->addAddress($email);
        
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Code - Question Bank System';
        $mail->Body = '
            <html>
            <head><style>body { font-family: Arial, sans-serif; }</style></head>
            <body>
                <h2>Your One-Time Password (OTP)</h2>
                <p>Your OTP code is:</p>
                <h1 style="color: #007bff; letter-spacing: 5px;">' . htmlspecialchars($otp) . '</h1>
                <p>This code will expire in ' . OTP_EXPIRY_MINUTES . ' minutes.</p>
                <p>If you did not request this code, please ignore this email.</p>
                <hr>
                <p><small>Question Bank System - Secure Login</small></p>
            </body>
            </html>
        ';
        
        if ($mail->send()) {
            return ['success' => true, 'message' => 'OTP sent to your email'];
        } else {
            return ['success' => false, 'message' => 'Failed to send OTP: ' . $mail->ErrorInfo];
        }
    } catch (Exception $e) {
        error_log('OTP email error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Email service unavailable'];
    }
}

/**
 * Fallback: send OTP using PHP's mail() function
 * @param string $email Email address
 * @param string $otp OTP code
 * @return array ['success' => bool, 'message' => string]
 */
function send_otp_via_php_mail($email, $otp) {
    $to = $email;
    $subject = 'Your OTP Code - Question Bank System';
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . SENDER_EMAIL . "\r\n";
    
    $body = '
        <html>
        <head><style>body { font-family: Arial, sans-serif; }</style></head>
        <body>
            <h2>Your One-Time Password (OTP)</h2>
            <p>Your OTP code is:</p>
            <h1 style="color: #007bff; letter-spacing: 5px;">' . htmlspecialchars($otp) . '</h1>
            <p>This code will expire in ' . OTP_EXPIRY_MINUTES . ' minutes.</p>
            <p>If you did not request this code, please ignore this email.</p>
            <hr>
            <p><small>Question Bank System - Secure Login</small></p>
        </body>
        </html>
    ';
    
    if (mail($to, $subject, $body, $headers)) {
        return ['success' => true, 'message' => 'OTP sent to your email'];
    } else {
        return ['success' => false, 'message' => 'Failed to send OTP'];
    }
}

/**
 * Store OTP in database for a student email
 * @param string $email Student email
 * @param string $otp OTP code
 * @return bool Success
 */
function store_otp_in_db($email, $otp) {
    global $mysqli;
    
    $expires_at = date('Y-m-d H:i:s', time() + OTP_EXPIRY_MINUTES * 60);
    
    // Use REPLACE to handle if email already has pending OTP
    $stmt = $mysqli->prepare('
        UPDATE student_user 
        SET otp_code = ?, otp_expires_at = ?, is_verified = 0
        WHERE email = ?
        LIMIT 1
    ');
    
    if ($stmt) {
        $stmt->bind_param('sss', $otp, $expires_at, $email);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    return false;
}

/**
 * Verify OTP code for an email
 * @param string $email Student email
 * @param string $otp OTP code submitted by user
 * @return array ['verified' => bool, 'message' => string]
 */
function verify_otp($email, $otp) {
    global $mysqli;
    
    $stmt = $mysqli->prepare('
        SELECT student_id, otp_code, otp_expires_at 
        FROM student_user 
        WHERE email = ? 
        LIMIT 1
    ');
    
    if (!$stmt) {
        return ['verified' => false, 'message' => 'Database error'];
    }
    
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if (!$row) {
        return ['verified' => false, 'message' => 'Email not found'];
    }
    
    // Check if OTP matches
    if ($row['otp_code'] !== $otp) {
        return ['verified' => false, 'message' => 'Invalid OTP code'];
    }
    
    // Check if OTP is expired
    if (strtotime($row['otp_expires_at']) < time()) {
        return ['verified' => false, 'message' => 'OTP has expired. Request a new one.'];
    }
    
    // Mark as verified in database
    $update_stmt = $mysqli->prepare('
        UPDATE student_user 
        SET is_verified = 1, otp_code = NULL, otp_expires_at = NULL 
        WHERE email = ? 
        LIMIT 1
    ');
    
    if ($update_stmt) {
        $update_stmt->bind_param('s', $email);
        $update_stmt->execute();
        $update_stmt->close();
    }
    
    return ['verified' => true, 'message' => 'Email verified successfully'];
}

/**
 * Check if student email is verified
 * @param string $email Student email
 * @return bool
 */
function is_email_verified($email) {
    global $mysqli;
    
    $stmt = $mysqli->prepare('
        SELECT is_verified 
        FROM student_user 
        WHERE email = ? 
        LIMIT 1
    ');
    
    if (!$stmt) return false;
    
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row && $row['is_verified'] == 1;
}

?>
