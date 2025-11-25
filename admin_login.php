<?php
require_once 'db.php';
require_once 'admin_auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$error = '';
$message = $message ?? '';
$redirect = $_GET['redirect'] ?? 'manage.php';
$adminCount = 0;
try {
  $r = $mysqli->query('SELECT COUNT(*) AS c FROM admin_user');
  if ($r) {
    $arow = $r->fetch_assoc();
    $adminCount = isset($arow['c']) ? (int)$arow['c'] : 0;
  }
} catch (Throwable $e) { }

$show_otp = isset($_GET['otp']) || (isset($_SESSION['admin_register_step']) && $_SESSION['admin_register_step'] == 2) || (isset($_SESSION['admin_login_step']) && $_SESSION['admin_login_step'] == 2);

// Handle admin create/login/OTP verify actions
// Auto-expire admin registration OTP after 5 minutes
if (isset($_SESSION['admin_register_step']) && $_SESSION['admin_register_step'] == 2) {
  $sent = isset($_SESSION['admin_register_sent_at']) ? (int)$_SESSION['admin_register_sent_at'] : 0;
  if ($sent > 0 && (time() - $sent) > 300) {
    unset($_SESSION['admin_register_step'], $_SESSION['admin_register_email'], $_SESSION['admin_register_id'], $_SESSION['admin_register_sent_at']);
    $message = 'Admin registration session expired due to inactivity. Please create again.';
  }
}
// Auto-expire admin login OTP after 5 minutes
if (isset($_SESSION['admin_login_step']) && $_SESSION['admin_login_step'] == 2) {
  $sent = isset($_SESSION['admin_login_sent_at']) ? (int)$_SESSION['admin_login_sent_at'] : 0;
  if ($sent > 0 && (time() - $sent) > 300) {
    unset($_SESSION['admin_login_step'], $_SESSION['admin_login_email'], $_SESSION['admin_login_id'], $_SESSION['admin_login_sent_at']);
    $message = 'Login OTP session expired. Please login again.';
  }
}

// Handle resend/cancel via GET
if (isset($_GET['action']) && $_GET['action'] === 'resend_admin_otp') {
  if (!isset($_SESSION['admin_register_email'])) {
    $error = 'Session expired. Please create admin again.';
  } else {
    $email = $_SESSION['admin_register_email'];
    require_once 'otp_config.php';
    $otp = generate_otp();
    $expires_at = date('Y-m-d H:i:s', time() + OTP_EXPIRY_MINUTES * 60);
    $upd2 = $mysqli->prepare('UPDATE admin_user SET otp_code = ?, otp_expires_at = ?, is_verified = 0 WHERE email = ? LIMIT 1');
    if ($upd2) {
      $upd2->bind_param('sss', $otp, $expires_at, $email);
      $upd2->execute();
      $upd2->close();
    }
    $res = send_otp_email($email, $otp);
    if ($res['success']) {
      $_SESSION['admin_register_sent_at'] = time();
      $message = 'New OTP sent to admin email.';
      $_SESSION['admin_register_step'] = 2;
    } else {
      $error = 'Failed to resend OTP: ' . $res['message'];
    }
  }
}

if (isset($_GET['action']) && $_GET['action'] === 'cancel_admin_registration') {
  unset($_SESSION['admin_register_step'], $_SESSION['admin_register_email'], $_SESSION['admin_register_id'], $_SESSION['admin_register_sent_at']);
  header('Location: admin_login.php');
  exit;
}

// resend/cancel for login OTP
if (isset($_GET['action']) && $_GET['action'] === 'resend_login_otp') {
  if (!isset($_SESSION['admin_login_email'])) {
    $error = 'Session expired. Please login again.';
  } else {
    $email = $_SESSION['admin_login_email'];
    require_once 'otp_config.php';
    $otp = generate_otp();
    $expires_at = date('Y-m-d H:i:s', time() + OTP_EXPIRY_MINUTES * 60);
    $upd2 = $mysqli->prepare('UPDATE admin_user SET otp_code = ?, otp_expires_at = ? WHERE email = ? LIMIT 1');
    if ($upd2) { $upd2->bind_param('sss', $otp, $expires_at, $email); $upd2->execute(); $upd2->close(); }
    $res = send_otp_email($email, $otp);
    if ($res['success']) { $_SESSION['admin_login_sent_at'] = time(); $_SESSION['admin_login_step'] = 2; $message = 'New OTP sent for login.'; } else { $error = 'Failed to resend OTP: ' . $res['message']; }
  }
}

if (isset($_GET['action']) && $_GET['action'] === 'cancel_login_otp') {
  unset($_SESSION['admin_login_step'], $_SESSION['admin_login_email'], $_SESSION['admin_login_id'], $_SESSION['admin_login_sent_at']);
  header('Location: admin_login.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['act'] ?? $_GET['action'] ?? 'login';

    if ($action === 'create') {
      // create new admin (now supports OTP verification)
      $username = isset($_POST['username']) ? trim($_POST['username']) : '';
      $password = isset($_POST['password']) ? $_POST['password'] : '';
      $email = isset($_POST['email']) ? trim($_POST['email']) : '';

      if ($username === '' || $password === '' || $email === '') {
        $error = 'Please provide full name, email and password.';
      } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address.';
      } else {
        // Check existing by email
        $chk = $mysqli->prepare('SELECT admin_id, is_verified FROM admin_user WHERE email = ? LIMIT 1');
        $chk->bind_param('s', $email);
        $chk->execute();
        $exists = $chk->get_result()->fetch_assoc();
        $chk->close();
        if ($exists && $exists['is_verified']) {
          $error = 'Email already registered.';
        } else {
          $hash = password_hash($password, PASSWORD_DEFAULT);

          if ($exists && !$exists['is_verified']) {
            // Update existing unverified admin (no department)
            $admin_id = (int)$exists['admin_id'];
            $upd = $mysqli->prepare('UPDATE admin_user SET username = ?, password_hash = ?, is_verified = 0 WHERE admin_id = ? LIMIT 1');
            if ($upd) {
              $upd->bind_param('ssi', $username, $hash, $admin_id);
              if (!$upd->execute()) {
                $error = 'Failed to update admin: ' . $mysqli->error;
              }
              $upd->close();
            } else {
              $error = 'Database error: ' . $mysqli->error;
            }
          } else {
            // Insert new admin as unverified (no department)
            $stmt = $mysqli->prepare('INSERT INTO admin_user (username, email, password_hash, is_verified) VALUES (?, ?, ?, 0)');
            if ($stmt) {
              $stmt->bind_param('sss', $username, $email, $hash);
              if ($stmt->execute()) {
                $admin_id = $stmt->insert_id;
                $stmt->close();
              } else {
                $error = 'Insert failed: ' . $stmt->error;
                $stmt->close();
              }
            } else {
              $error = 'Database error: ' . $mysqli->error;
            }
          }

          // If no error, generate and store OTP, send email
          if (!$error) {
            require_once 'otp_config.php';
            $otp = generate_otp();
            $expires_at = date('Y-m-d H:i:s', time() + OTP_EXPIRY_MINUTES * 60);
            // store otp in admin_user by email
            $upd2 = $mysqli->prepare('UPDATE admin_user SET otp_code = ?, otp_expires_at = ?, is_verified = 0 WHERE email = ? LIMIT 1');
            if ($upd2) {
              $upd2->bind_param('sss', $otp, $expires_at, $email);
              $upd2->execute();
              $upd2->close();
            }
            $result = send_otp_email($email, $otp);
            if ($result['success']) {
              $_SESSION['admin_register_step'] = 2;
              $_SESSION['admin_register_email'] = $email;
              $_SESSION['admin_register_id'] = $admin_id;
              $_SESSION['admin_register_sent_at'] = time();
              $message = 'Admin created. OTP sent to the email for verification.';
            } else {
              $error = 'Failed to send OTP: ' . $result['message'];
            }
          }
        }
      }
    } elseif ($action === 'verify_admin_otp') {
      // Verify OTP submitted for admin creation
      $otp = isset($_POST['otp']) ? trim($_POST['otp']) : '';
      if (!isset($_SESSION['admin_register_email'])) {
        $error = 'Session expired. Please create admin again.';
      } elseif ($otp === '') {
        $error = 'Please enter the OTP.';
      } else {
        $email = $_SESSION['admin_register_email'];
        $stmt = $mysqli->prepare('SELECT admin_id, username, otp_code, otp_expires_at FROM admin_user WHERE email = ? LIMIT 1');
        if ($stmt) {
          $stmt->bind_param('s', $email);
          $stmt->execute();
          $row = $stmt->get_result()->fetch_assoc();
          $stmt->close();
          if (!$row) {
            $error = 'Admin record not found.';
          } elseif ($row['otp_code'] !== $otp) {
            $error = 'Invalid OTP code.';
          } elseif (strtotime($row['otp_expires_at']) < time()) {
            $error = 'OTP expired. Please resend OTP.';
          } else {
            $up = $mysqli->prepare('UPDATE admin_user SET is_verified = 1, otp_code = NULL, otp_expires_at = NULL WHERE admin_id = ? LIMIT 1');
            if ($up) {
              $up->bind_param('i', $row['admin_id']);
              $up->execute();
              $up->close();
            }
            // login admin and redirect (registration flow)
            login_admin_session($row['username']);
            unset($_SESSION['admin_register_step'], $_SESSION['admin_register_email'], $_SESSION['admin_register_id'], $_SESSION['admin_register_sent_at']);
            header('Location: ' . $redirect);
            exit;
          }
        } else {
          $error = 'Database error.';
        }
      }
    }
    elseif ($action === 'verify_admin_login_otp') {
      // Verify OTP for login session
      $otp = isset($_POST['otp']) ? trim($_POST['otp']) : '';
      if (!isset($_SESSION['admin_login_email'])) {
        $error = 'Login session expired. Please login again.';
      } elseif ($otp === '') {
        $error = 'Please enter the OTP.';
      } else {
        $email = $_SESSION['admin_login_email'];
        $stmt = $mysqli->prepare('SELECT admin_id, username, otp_code, otp_expires_at FROM admin_user WHERE email = ? LIMIT 1');
        if ($stmt) {
          $stmt->bind_param('s', $email);
          $stmt->execute();
          $row = $stmt->get_result()->fetch_assoc();
          $stmt->close();
          if (!$row) {
            $error = 'Admin record not found.';
          } elseif ($row['otp_code'] !== $otp) {
            $error = 'Invalid OTP code.';
          } elseif (strtotime($row['otp_expires_at']) < time()) {
            $error = 'OTP expired. Please resend OTP.';
          } else {
            $up = $mysqli->prepare('UPDATE admin_user SET otp_code = NULL, otp_expires_at = NULL WHERE admin_id = ? LIMIT 1');
            if ($up) { $up->bind_param('i', $row['admin_id']); $up->execute(); $up->close(); }
            login_admin_session($row['username']);
            unset($_SESSION['admin_login_step'], $_SESSION['admin_login_email'], $_SESSION['admin_login_id'], $_SESSION['admin_login_sent_at']);
            header('Location: ' . $redirect);
            exit;
          }
        } else { $error = 'Database error.'; }
      }
    } elseif ($action === 'login') {
      // login attempt - use email instead of username
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        if ($email === '' || $password === '') {
            $error = 'Please provide email and password.';
        } else {
            $stmt = $mysqli->prepare('SELECT admin_id, username, password_hash, email FROM admin_user WHERE email = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $res = $stmt->get_result();
                $row = $res->fetch_assoc();
                if ($row && !empty($row['password_hash']) && password_verify($password, $row['password_hash'])) {
                    // Credentials valid — generate and send OTP to stored email
                    require_once 'otp_config.php';
                    $otp = generate_otp();
                    $expires_at = date('Y-m-d H:i:s', time() + OTP_EXPIRY_MINUTES * 60);
                    $upd2 = $mysqli->prepare('UPDATE admin_user SET otp_code = ?, otp_expires_at = ? WHERE admin_id = ? LIMIT 1');
                    if ($upd2) { $upd2->bind_param('ssi', $otp, $expires_at, $row['admin_id']); $upd2->execute(); $upd2->close(); }
                    $res = send_otp_email($row['email'], $otp);
                    if ($res['success']) {
                      $_SESSION['admin_login_step'] = 2;
                      $_SESSION['admin_login_email'] = $row['email'];
                      $_SESSION['admin_login_id'] = $row['admin_id'];
                      $_SESSION['admin_login_sent_at'] = time();
                      $message = 'OTP sent to your email. Please enter the code below to complete login.';
                      $show_otp = true;
                    } else {
                      $error = 'Failed to send OTP: ' . $res['message'];
                    }
                } else {
                    $error = 'Invalid email or password.';
                }
            } else {
                $error = 'Database error.';
            }
        }
    }
    elseif ($action === 'verify_admin_otp_manual') {
      // manual verify (email + otp) without relying on session
      $email = isset($_POST['email']) ? trim($_POST['email']) : '';
      $otp = isset($_POST['otp']) ? trim($_POST['otp']) : '';
      if ($email === '' || $otp === '') {
        $error = 'Please provide email and OTP.';
      } else {
        $stmt = $mysqli->prepare('SELECT admin_id, username, otp_code, otp_expires_at FROM admin_user WHERE email = ? LIMIT 1');
        if ($stmt) {
          $stmt->bind_param('s', $email);
          $stmt->execute();
          $row = $stmt->get_result()->fetch_assoc();
          $stmt->close();
          if (!$row) {
            $error = 'No admin found with that email.';
          } elseif ($row['otp_code'] !== $otp) {
            $error = 'Invalid OTP code.';
          } elseif (strtotime($row['otp_expires_at']) < time()) {
            $error = 'OTP expired. Please resend OTP.';
          } else {
            $up = $mysqli->prepare('UPDATE admin_user SET is_verified = 1, otp_code = NULL, otp_expires_at = NULL WHERE admin_id = ? LIMIT 1');
            if ($up) { $up->bind_param('i', $row['admin_id']); $up->execute(); $up->close(); }
            login_admin_session($row['username']);
            unset($_SESSION['admin_register_step'], $_SESSION['admin_register_email'], $_SESSION['admin_register_id'], $_SESSION['admin_register_sent_at']);
            header('Location: ' . $redirect);
            exit;
          }
        } else { $error = 'Database error.'; }
      }
    }
    elseif ($action === 'resend_admin_otp_manual') {
      $email = isset($_POST['email']) ? trim($_POST['email']) : '';
      if ($email === '') {
        $error = 'Please provide email to resend OTP.';
      } else {
        require_once 'otp_config.php';
        $otp = generate_otp();
        $expires_at = date('Y-m-d H:i:s', time() + OTP_EXPIRY_MINUTES * 60);
        $upd2 = $mysqli->prepare('UPDATE admin_user SET otp_code = ?, otp_expires_at = ?, is_verified = 0 WHERE email = ? LIMIT 1');
        if ($upd2) {
          $upd2->bind_param('sss', $otp, $expires_at, $email);
          $upd2->execute();
          $upd2->close();
        }
        $res = send_otp_email($email, $otp);
        if ($res['success']) { $message = 'New OTP sent to admin email.'; } else { $error = 'Failed to resend OTP: ' . $res['message']; }
      }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin Login</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">
  <div class="row">
    <div class="col-md-6">
      <div class="card mb-3">
        <div class="card-header"><strong>Admin Login</strong></div>
        <div class="card-body">
          <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
          <?php endif; ?>
          <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
          <?php endif; ?>
          <form method="post" action="admin_login.php<?php echo '?redirect=' . urlencode($redirect); ?>">
            <input type="hidden" name="act" value="login">
            <div class="mb-3"><label class="form-label">Email (registered)</label>
              <input name="email" type="email" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Password</label>
              <input name="password" type="password" class="form-control" required></div>
            <div class="d-flex align-items-center gap-2">
              <button class="btn btn-outline-primary" type="submit">Login</button>
              <a class="btn btn-link ms-auto" href="choose_role.php">Back to site</a>
            </div>
          </form>
          <hr class="my-3">
          <p class="text-center mb-0"><small>Don't have an account? <a href="create_admin.php">Register here</a></small></p>
        </div>
      </div>
    </div>
  </div>

  <?php if ($show_otp): ?>
  <div class="row mt-3">
    <div class="col-md-6">
      <div class="card">
        <div class="card-header"><strong>Verify Admin Email (OTP)</strong></div>
        <div class="card-body">
          <?php if ((isset($_SESSION['admin_register_step']) && $_SESSION['admin_register_step'] == 2) || (isset($_SESSION['admin_login_step']) && $_SESSION['admin_login_step'] == 2)): ?>
            <?php $otp_email = $_SESSION['admin_register_email'] ?? $_SESSION['admin_login_email'] ?? ''; ?>
            <p class="small-muted">We've sent a verification code to <strong><?php echo htmlspecialchars($otp_email); ?></strong></p>
            <form method="post" action="admin_login.php<?php echo '?redirect=' . urlencode($redirect); ?>">
              <input type="hidden" name="act" value="<?php echo (isset($_SESSION['admin_login_step']) && $_SESSION['admin_login_step']==2) ? 'verify_admin_login_otp' : 'verify_admin_otp'; ?>">
              <div class="mb-3">
                <label class="form-label">Enter OTP Code</label>
                <input name="otp" type="text" class="form-control form-control-lg text-center" placeholder="000000" maxlength="6" required autofocus style="letter-spacing: 10px; font-size: 1.5rem; font-family: monospace;">
                <small class="form-text text-muted">6-digit code sent to your email</small>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <button class="btn btn-success" type="submit">Verify OTP</button>
                <a class="btn btn-link" href="admin_login.php?action=<?php echo (isset($_SESSION['admin_login_step']) && $_SESSION['admin_login_step']==2) ? 'cancel_login_otp' : 'cancel_admin_registration'; ?>">← Back</a>
              </div>
              <div>
                <a class="btn btn-link btn-sm" href="admin_login.php?action=<?php echo (isset($_SESSION['admin_login_step']) && $_SESSION['admin_login_step']==2) ? 'resend_login_otp' : 'resend_admin_otp'; ?>">Didn't receive? Resend OTP</a>
              </div>
            </form>
          <?php else: ?>
            <p class="small-muted">Enter your email and the OTP sent to it (or request a new OTP).</p>
            <form method="post" action="admin_login.php<?php echo '?redirect=' . urlencode($redirect); ?>">
              <input type="hidden" name="act" value="verify_admin_otp_manual">
              <div class="mb-3"><label class="form-label">Email</label><input name="email" type="email" class="form-control" required></div>
              <div class="mb-3">
                <label class="form-label">Enter OTP Code</label>
                <input name="otp" type="text" class="form-control form-control-lg text-center" placeholder="000000" maxlength="6" required style="letter-spacing: 10px; font-size: 1.2rem; font-family: monospace;">
              </div>
              <div class="d-flex justify-content-between mb-2">
                <div>
                  <button class="btn btn-success" type="submit">Verify OTP</button>
                  <button class="btn btn-secondary" type="submit" name="act" value="resend_admin_otp_manual">Resend OTP</button>
                </div>
                <a class="btn btn-link" href="admin_login.php">← Back</a>
              </div>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>
</body>
</html>
