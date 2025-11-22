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
define('SMTP_USERNAME', 'qbank0290@gmail.com');
define('SMTP_PASSWORD', 'xwjm ayxq wzzp jobc');
define('SENDER_EMAIL', 'qbank0290@gmail.com');
define('SENDER_NAME', 'Question Bank System');

/**
 * Generate a random OTP code
 * @return string OTP code (e.g., "123456")
 */
function generate_otp() {
    return str_pad(random_int(0, pow(10, OTP_LENGTH) - 1), OTP_LENGTH, '0', STR_PAD_LEFT);
}

/**
 * Send OTP to email using direct SMTP connection to Gmail
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
    
    // Try using PHP's SMTP stream context for Gmail
    return send_otp_via_stream_socket($email, $otp);
}

/**
 * Send email via direct SMTP stream socket connection
 * This method works on Windows without needing mail() function
 */
function send_otp_via_stream_socket($email, $otp) {
    try {
        $host = SMTP_HOST;
        $port = SMTP_PORT;
        $username = SMTP_USERNAME;
        $password = SMTP_PASSWORD;
        $from = SENDER_EMAIL;
        $fromName = SENDER_NAME;
        
        // Create SMTP connection
        $fp = @fsockopen('ssl://' . $host, $port, $errno, $errstr, 30);
        
        if (!$fp) {
            // Try without SSL if SSL fails
            $fp = @fsockopen($host, $port, $errno, $errstr, 30);
            if (!$fp) {
                return ['success' => false, 'message' => "Failed to connect to SMTP: $errstr"];
            }
        }
        
        // Helper function to send SMTP command
        function smtp_send_command($fp, $cmd) {
            fputs($fp, $cmd . "\r\n");
            $response = fgets($fp, 1024);
            return $response;
        }
        
        // Read server greeting
        $greeting = fgets($fp, 1024);
        
        // EHLO
        $resp = smtp_send_command($fp, 'EHLO ' . gethostname());
        
        // STARTTLS
        $resp = smtp_send_command($fp, 'STARTTLS');
        stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);
        
        // EHLO again after TLS
        $resp = smtp_send_command($fp, 'EHLO ' . gethostname());
        
        // AUTH LOGIN
        $resp = smtp_send_command($fp, 'AUTH LOGIN');
        $resp = smtp_send_command($fp, base64_encode($username));
        $resp = smtp_send_command($fp, base64_encode($password));
        
        // Check authentication
        if (strpos($resp, '235') === false) {
            fclose($fp);
            return ['success' => false, 'message' => 'SMTP authentication failed'];
        }
        
        // Build email message
        $subject = 'Your OTP Code - Question Bank System';
        $body = "
<html>
<head><style>body { font-family: Arial, sans-serif; }</style></head>
<body>
    <h2>Your One-Time Password (OTP)</h2>
    <p>Your OTP code is:</p>
    <h1 style=\"color: #007bff; letter-spacing: 5px;\">$otp</h1>
    <p>This code will expire in " . OTP_EXPIRY_MINUTES . " minutes.</p>
    <p>If you did not request this code, please ignore this email.</p>
    <hr>
    <p><small>Question Bank System - Secure Login</small></p>
</body>
</html>
        ";
        
        // Prepare headers
        $headers = "From: $from\r\n";
        $headers .= "To: $email\r\n";
        $headers .= "Subject: $subject\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        
        // MAIL FROM
        smtp_send_command($fp, "MAIL FROM: <$from>");
        
        // RCPT TO
        $resp = smtp_send_command($fp, "RCPT TO: <$email>");
        if (strpos($resp, '250') === false && strpos($resp, '251') === false) {
            fclose($fp);
            return ['success' => false, 'message' => 'RCPT TO failed'];
        }
        
        // DATA
        smtp_send_command($fp, 'DATA');
        
        // Send message
        $messageBody = "From: $from\r\n";
        $messageBody .= "To: $email\r\n";
        $messageBody .= "Subject: $subject\r\n";
        $messageBody .= "MIME-Version: 1.0\r\n";
        $messageBody .= "Content-type: text/html; charset=UTF-8\r\n";
        $messageBody .= "\r\n";
        $messageBody .= $body;
        $messageBody .= "\r\n.\r\n";
        
        fputs($fp, $messageBody);
        $resp = fgets($fp, 1024);
        
        // QUIT
        smtp_send_command($fp, 'QUIT');
        fclose($fp);
        
        if (strpos($resp, '250') !== false) {
            return ['success' => true, 'message' => 'OTP sent to your email'];
        } else {
            return ['success' => false, 'message' => 'Failed to send email'];
        }
        
    } catch (Exception $e) {
        error_log('SMTP error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Email service error'];
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
