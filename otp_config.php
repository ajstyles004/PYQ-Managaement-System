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
        
        // Create connection to SMTP server (Gmail)
        $socket = fsockopen($host, $port, $errno, $errstr, 30);
        
        if (!$socket) {
            return ['success' => false, 'message' => "SMTP connection failed: $errstr ($errno)"];
        }
        
        // Read SMTP greeting
        $response = fgets($socket, 1024);
        if (strpos($response, '220') === false) {
            fclose($socket);
            return ['success' => false, 'message' => 'SMTP server did not respond correctly'];
        }
        
        // Send EHLO
        fputs($socket, "EHLO localhost\r\n");
        $response = fgets($socket, 1024);
        
        // Read all EHLO responses
        while (substr($response, 3, 1) == '-') {
            $response = fgets($socket, 1024);
        }
        
        // Start TLS encryption
        fputs($socket, "STARTTLS\r\n");
        $response = fgets($socket, 1024);
        
        if (strpos($response, '220') === false) {
            fclose($socket);
            return ['success' => false, 'message' => 'STARTTLS not available'];
        }
        
        // Enable TLS on the connection
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            return ['success' => false, 'message' => 'Failed to enable TLS encryption'];
        }
        
        // Send EHLO again after TLS
        fputs($socket, "EHLO localhost\r\n");
        $response = fgets($socket, 1024);
        
        // Read all EHLO responses
        while (substr($response, 3, 1) == '-') {
            $response = fgets($socket, 1024);
        }
        
        // Authenticate
        fputs($socket, "AUTH LOGIN\r\n");
        $response = fgets($socket, 1024);
        
        if (strpos($response, '334') === false) {
            fclose($socket);
            return ['success' => false, 'message' => 'AUTH LOGIN not available'];
        }
        
        // Send username (base64 encoded)
        fputs($socket, base64_encode($username) . "\r\n");
        $response = fgets($socket, 1024);
        
        if (strpos($response, '334') === false) {
            fclose($socket);
            return ['success' => false, 'message' => 'Username rejected'];
        }
        
        // Send password (base64 encoded)
        fputs($socket, base64_encode($password) . "\r\n");
        $response = fgets($socket, 1024);
        
        if (strpos($response, '235') === false) {
            fclose($socket);
            error_log("Gmail Auth Failed: $response");
            return ['success' => false, 'message' => 'Authentication failed - check credentials'];
        }
        
        // Now send email
        fputs($socket, "MAIL FROM: <$from>\r\n");
        $response = fgets($socket, 1024);
        
        fputs($socket, "RCPT TO: <$email>\r\n");
        $response = fgets($socket, 1024);
        
        if (strpos($response, '250') === false) {
            fclose($socket);
            return ['success' => false, 'message' => 'Recipient rejected'];
        }
        
        // Send message data
        fputs($socket, "DATA\r\n");
        $response = fgets($socket, 1024);
        
        $subject = 'Your OTP Code - Question Bank System';
        $body = "Your OTP code is: $otp\n\nThis code will expire in " . OTP_EXPIRY_MINUTES . " minutes.\n\nIf you did not request this, ignore this email.";
        
        $message = "From: $from\r\n";
        $message .= "To: $email\r\n";
        $message .= "Subject: $subject\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-type: text/plain; charset=UTF-8\r\n";
        $message .= "\r\n";
        $message .= $body;
        $message .= "\r\n.\r\n";
        
        fputs($socket, $message);
        $response = fgets($socket, 1024);
        
        // Close connection
        fputs($socket, "QUIT\r\n");
        fclose($socket);
        
        if (strpos($response, '250') !== false) {
            return ['success' => true, 'message' => 'OTP sent to your email'];
        } else {
            return ['success' => false, 'message' => 'Failed to queue email for sending'];
        }
        
    } catch (Exception $e) {
        error_log('SMTP error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Email service error: ' . $e->getMessage()];
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
