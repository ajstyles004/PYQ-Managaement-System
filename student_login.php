<?php
require_once 'db.php';
require_once 'student_auth.php';
require_once 'otp_config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$error = '';
$message = '';
$step = isset($_SESSION['login_step']) ? $_SESSION['login_step'] : 1;  // Step 1: Email+Password, Step 2: OTP
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'student_dashboard.php';

// Ensure student_user table has new columns
$createSql = "CREATE TABLE IF NOT EXISTS student_user (
  student_id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) UNIQUE,
  email VARCHAR(150) NOT NULL UNIQUE,
  full_name VARCHAR(150) DEFAULT NULL,
  password_hash VARCHAR(255) NOT NULL,
  otp_code VARCHAR(10) DEFAULT NULL,
  otp_expires_at DATETIME DEFAULT NULL,
  is_verified TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);";
try { 
  $mysqli->query($createSql); 
} catch (Throwable $e) { 
  error_log('student_login: ensure table error: '.$e->getMessage()); 
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'send_otp') {
        // Step 1: Email + Password verification, then send OTP
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        if ($email === '' || $password === '') {
            $error = 'Please provide email and password.';
        } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            // Check if email exists and password matches
            $stmt = $mysqli->prepare('
                SELECT student_id, username, password_hash, is_verified 
                FROM student_user 
                WHERE email = ? 
                LIMIT 1
            ');
            
            if ($stmt) {
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $res = $stmt->get_result();
                $row = $res->fetch_assoc();
                $stmt->close();
                
                if ($row && password_verify($password, $row['password_hash'])) {
                    // Check if account is verified (for registration accounts)
                    if (!$row['is_verified']) {
                        $error = 'Your account is not yet verified. Check your email for the verification OTP.';
                    } else {
                        // Generate and send OTP
                        $otp = generate_otp();
                        store_otp_in_db($email, $otp);
                        $result = send_otp_email($email, $otp);
                        
                        if ($result['success']) {
                            $_SESSION['login_step'] = 2;
                            $_SESSION['login_email'] = $email;
                            $_SESSION['login_student_id'] = $row['student_id'];
                            $message = 'OTP sent to your email. Please enter it to complete login.';
                            $step = 2;
                        } else {
                            $error = $result['message'];
                        }
                    }
                } else {
                    $error = 'Invalid email or password.';
                }
            } else {
                $error = 'Database error.';
            }
        }
    }
    
    else if ($action === 'verify_otp') {
        // Step 2: Verify OTP
        $otp = isset($_POST['otp']) ? trim($_POST['otp']) : '';
        
        if (!isset($_SESSION['login_email']) || !isset($_SESSION['login_student_id'])) {
            $error = 'Session expired. Please start over.';
            session_unset();
        } else if ($otp === '') {
            $error = 'Please enter the OTP.';
        } else {
            $email = $_SESSION['login_email'];
            $student_id = $_SESSION['login_student_id'];
            
            // Verify OTP
            $verify_result = verify_otp($email, $otp);
            
            if ($verify_result['verified']) {
                // Get student username for session
                $stmt = $mysqli->prepare('SELECT username FROM student_user WHERE student_id = ? LIMIT 1');
                if ($stmt) {
                    $stmt->bind_param('i', $student_id);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    $row = $res->fetch_assoc();
                    $stmt->close();
                    
                    $username = $row ? $row['username'] : "student_$student_id";
                    
                    // Create session
                    login_student_session($username, $student_id);
                    
                    // Clear login session data
                    unset($_SESSION['login_step'], $_SESSION['login_email'], $_SESSION['login_student_id']);
                    
                    header('Location: ' . $redirect);
                    exit;
                }
            } else {
                $error = $verify_result['message'];
            }
        }
    }
    
    else if ($action === 'resend_otp') {
        // Resend OTP
        if (!isset($_SESSION['login_email'])) {
            $error = 'Session expired. Please start over.';
            session_unset();
            $step = 1;
        } else {
            $email = $_SESSION['login_email'];
            $otp = generate_otp();
            store_otp_in_db($email, $otp);
            $result = send_otp_email($email, $otp);
            
            if ($result['success']) {
                $message = 'New OTP sent to your email.';
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Set step based on session
if (isset($_SESSION['login_step'])) {
    $step = $_SESSION['login_step'];
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Student Login</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card">
        <div class="card-header"><strong>Student Login - Email & OTP Verification</strong></div>
        <div class="card-body">
          
          <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
          <?php endif; ?>
          
          <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
          <?php endif; ?>
          
          <!-- Step 1: Email + Password -->
          <?php if ($step === 1): ?>
          <form method="post" action="student_login.php<?php echo '?redirect=' . urlencode($redirect); ?>">
            <input type="hidden" name="action" value="send_otp">
            
            <div class="mb-3">
              <label class="form-label">Email Address</label>
              <input name="email" type="email" class="form-control" placeholder="your@email.com" required autofocus>
              <small class="form-text text-muted">Your registered email address</small>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Password</label>
              <input name="password" type="password" class="form-control" placeholder="Your password" required>
            </div>
            
            <div class="d-grid gap-2 mb-3">
              <button class="btn btn-primary btn-lg" type="submit">Send OTP</button>
            </div>
            
            <div class="text-center">
              <p class="text-muted">Don't have an account? <a href="student_register.php">Register here</a></p>
            </div>
          </form>
          <?php endif; ?>
          
          <!-- Step 2: OTP Verification -->
          <?php if ($step === 2): ?>
          <form method="post" action="student_login.php<?php echo '?redirect=' . urlencode($redirect); ?>">
            <input type="hidden" name="action" value="verify_otp">
            
            <div class="alert alert-info mb-3">
              <strong>📧 OTP Sent</strong><br>
              We've sent a 6-digit code to your registered email address.
            </div>
            
            <div class="mb-3">
              <label class="form-label">Enter OTP Code</label>
              <input name="otp" type="text" class="form-control form-control-lg text-center" 
                     placeholder="000000" maxlength="6" required autofocus 
                     style="letter-spacing: 10px; font-size: 1.5rem; font-family: monospace;">
              <small class="form-text text-muted">6-digit code sent to your email</small>
            </div>
            
            <div class="d-grid gap-2 mb-3">
              <button class="btn btn-success btn-lg" type="submit">Verify OTP</button>
            </div>
            
            <div class="text-center">
              <form method="post" class="d-inline">
                <input type="hidden" name="action" value="resend_otp">
                <button type="submit" class="btn btn-link btn-sm">Didn't receive the code? Resend OTP</button>
              </form>
              <br>
              <a href="student_login.php" class="btn btn-link btn-sm">← Back to Login</a>
            </div>
          </form>
          <?php endif; ?>
          
        </div>
      </div>
    </div>
  </div>
</body>
</html>
