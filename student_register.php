<?php
require_once 'db.php';
require_once 'student_auth.php';
require_once 'otp_config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$error = '';
$message = '';
$step = isset($_SESSION['register_step']) ? $_SESSION['register_step'] : 1;  // Step 1: Form, Step 2: OTP Verification

// Ensure student_user table exists with new columns
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
  error_log('student_register: ensure table error: '.$e->getMessage()); 
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'register') {
        // Step 1: Register and send OTP
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';

        if ($email === '' || $password === '') {
            $error = 'Please provide email and password.';
        } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else if ($password !== $password_confirm) {
            $error = 'Passwords do not match.';
        } else if (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } else {
            // Check if email already exists
            $chk = $mysqli->prepare('SELECT student_id FROM student_user WHERE email = ? LIMIT 1');
            $chk->bind_param('s', $email);
            $chk->execute();
            $exists = $chk->get_result()->fetch_assoc();
            $chk->close();
            
            if ($exists) {
                $error = 'Email already registered.';
            } else {
                // Create account (unverified)
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $mysqli->prepare('
                    INSERT INTO student_user (email, full_name, password_hash, is_verified) 
                    VALUES (?, ?, ?, 0)
                ');
                
                if ($stmt) {
                    $stmt->bind_param('sss', $email, $full_name, $hash);
                    if ($stmt->execute()) {
                        $student_id = $stmt->insert_id;
                        $stmt->close();
                        
                        // Generate and send OTP
                        $otp = generate_otp();
                        store_otp_in_db($email, $otp);
                        $result = send_otp_email($email, $otp);
                        
                        if ($result['success']) {
                            $_SESSION['register_step'] = 2;
                            $_SESSION['register_email'] = $email;
                            $_SESSION['register_student_id'] = $student_id;
                            $message = 'Account created! OTP sent to your email for verification.';
                            $step = 2;
                        } else {
                            // Delete the account if email sending fails
                            $del = $mysqli->prepare('DELETE FROM student_user WHERE student_id = ?');
                            if ($del) {
                                $del->bind_param('i', $student_id);
                                $del->execute();
                                $del->close();
                            }
                            $error = 'Email sending failed: ' . $result['message'];
                        }
                    } else {
                        $error = 'Registration failed. Please try again.';
                        $stmt->close();
                    }
                } else {
                    $error = 'Database error: ' . $mysqli->error;
                }
            }
        }
    }
    
    else if ($action === 'verify_otp') {
        // Step 2: Verify OTP
        $otp = isset($_POST['otp']) ? trim($_POST['otp']) : '';
        
        if (!isset($_SESSION['register_email']) || !isset($_SESSION['register_student_id'])) {
            $error = 'Session expired. Please register again.';
            session_unset();
        } else if ($otp === '') {
            $error = 'Please enter the OTP.';
        } else {
            $email = $_SESSION['register_email'];
            
            // Verify OTP
            $verify_result = verify_otp($email, $otp);
            
            if ($verify_result['verified']) {
                // Clear registration session data
                unset($_SESSION['register_step'], $_SESSION['register_email'], $_SESSION['register_student_id']);
                $message = 'Email verified! Your account is active. You can now login.';
                $step = 3;  // Success step
                
                // Auto-redirect after 3 seconds
                header('Refresh: 3; url=student_login.php');
            } else {
                $error = $verify_result['message'];
            }
        }
    }
    
    else if ($action === 'resend_otp') {
        // Resend OTP
        if (!isset($_SESSION['register_email'])) {
            $error = 'Session expired. Please register again.';
            session_unset();
            $step = 1;
        } else {
            $email = $_SESSION['register_email'];
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
if (isset($_SESSION['register_step'])) {
    $step = $_SESSION['register_step'];
}
?>
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Student Register</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card">
        <div class="card-header"><strong>Student Registration - Email & OTP Verification</strong></div>
        <div class="card-body">
          
          <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
          <?php endif; ?>
          
          <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
          <?php endif; ?>
          
          <!-- Step 1: Registration Form -->
          <?php if ($step === 1): ?>
          <form method="post" action="student_register.php">
            <input type="hidden" name="action" value="register">
            
            <div class="mb-3">
              <label class="form-label">Email Address *</label>
              <input name="email" type="email" class="form-control" placeholder="your@email.com" required autofocus>
              <small class="form-text text-muted">You'll use this email to login</small>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Full Name (optional)</label>
              <input name="full_name" class="form-control" placeholder="John Doe">
            </div>
            
            <div class="mb-3">
              <label class="form-label">Password *</label>
              <input name="password" type="password" class="form-control" placeholder="At least 6 characters" required>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Confirm Password *</label>
              <input name="password_confirm" type="password" class="form-control" placeholder="Re-enter password" required>
            </div>
            
            <div class="d-grid gap-2 mb-3">
              <button class="btn btn-primary btn-lg" type="submit">Register & Send OTP</button>
            </div>
            
            <div class="text-center">
              <p class="text-muted">Already have an account? <a href="student_login.php">Login here</a></p>
            </div>
          </form>
          <?php endif; ?>
          
          <!-- Step 2: OTP Verification -->
          <?php if ($step === 2): ?>
          <form method="post" action="student_register.php">
            <input type="hidden" name="action" value="verify_otp">
            
            <div class="alert alert-info mb-3">
              <strong>📧 Verify Your Email</strong><br>
              We've sent a 6-digit code to your email address.
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
                <button type="submit" class="btn btn-link btn-sm">Didn't receive? Resend OTP</button>
              </form>
              <br>
              <a href="student_register.php" class="btn btn-link btn-sm">← Back to Registration</a>
            </div>
          </form>
          <?php endif; ?>
          
          <!-- Step 3: Success -->
          <?php if ($step === 3): ?>
          <div class="text-center py-4">
            <div style="font-size: 3rem; color: #28a745; margin-bottom: 20px;">✓</div>
            <h4>Email Verified!</h4>
            <p>Your account is ready to use.</p>
            <div class="alert alert-success mb-3">
              <strong>Success!</strong> Redirecting to login page in 3 seconds...
            </div>
            <a href="student_login.php" class="btn btn-primary btn-lg">Go to Login Now</a>
            <script>
              setTimeout(function() {
                window.location.href = 'student_login.php';
              }, 3000);
            </script>
          </div>
          <?php endif; ?>
          
        </div>
      </div>
    </div>
  </div>
</body>
</html>
