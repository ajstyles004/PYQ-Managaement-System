<?php
// create_admin.php - simple helper to create an admin user (web or CLI)
require_once 'db.php';
require_once 'otp_config.php';
require_once 'admin_auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$message = '';
$step = isset($_SESSION['admin_register_step']) ? $_SESSION['admin_register_step'] : 1;
if (php_sapi_name() === 'cli') {
    // CLI usage: php create_admin.php username password
    global $argv;
    if (!isset($argv[1]) || !isset($argv[2])) {
        echo "Usage: php create_admin.php <username> <password>\n";
        exit(1);
    }
    $username = $argv[1];
    $password = $argv[2];
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $mysqli->prepare('INSERT INTO admin_user (username, password_hash) VALUES (?, ?)');
    $stmt->bind_param('ss', $username, $hash);
    if ($stmt->execute()) echo "Created admin user: $username\n"; else echo "Error: " . $stmt->error . "\n";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? 'create';
  // Ensure `email` column exists (safe attempt)
  try {
    $res = $mysqli->query("SHOW COLUMNS FROM admin_user LIKE 'email'");
    if ($res && $res->num_rows === 0) {
      $mysqli->query("ALTER TABLE admin_user ADD COLUMN email VARCHAR(150) DEFAULT NULL");
    }
  } catch (Throwable $e) { }

  if ($action === 'create') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    if ($username === '' || $password === '' || $email === '') {
      $message = 'Please provide full name, email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $message = 'Please provide a valid email address.';
    } else {
      // Check existing
      $admin_id = null;
      $chkEmail = $mysqli->prepare('SELECT admin_id, is_verified FROM admin_user WHERE email = ? LIMIT 1');
      if ($chkEmail) {
        $chkEmail->bind_param('s', $email);
        $chkEmail->execute();
        $existsEmail = $chkEmail->get_result()->fetch_assoc();
        $chkEmail->close();
      } else { $existsEmail = null; }

      if ($existsEmail && $existsEmail['is_verified']) {
        $message = 'Email already registered.';
      } else {
        $chkUser = $mysqli->prepare('SELECT admin_id, is_verified, email FROM admin_user WHERE username = ? LIMIT 1');
        if ($chkUser) {
          $chkUser->bind_param('s', $username);
          $chkUser->execute();
          $existsUser = $chkUser->get_result()->fetch_assoc();
          $chkUser->close();
        } else { $existsUser = null; }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        if ($existsUser) {
          if ($existsUser['is_verified']) {
            $message = 'Username already taken. Please choose a different name.';
          } else {
            $admin_id = (int)$existsUser['admin_id'];
            $upd = $mysqli->prepare('UPDATE admin_user SET email = ?, password_hash = ?, is_verified = 0 WHERE admin_id = ? LIMIT 1');
            if ($upd) {
              $upd->bind_param('ssi', $email, $hash, $admin_id);
              if ($upd->execute()) {
                // send OTP after update
                $otp = generate_otp();
                $expires_at = date('Y-m-d H:i:s', time() + OTP_EXPIRY_MINUTES * 60);
                $u2 = $mysqli->prepare('UPDATE admin_user SET otp_code = ?, otp_expires_at = ? WHERE admin_id = ? LIMIT 1');
                if ($u2) { $u2->bind_param('ssi', $otp, $expires_at, $admin_id); $u2->execute(); $u2->close(); }
                $res = send_otp_email($email, $otp);
                if ($res['success']) {
                  $_SESSION['admin_register_step'] = 2;
                  $_SESSION['admin_register_email'] = $email;
                  $_SESSION['admin_register_id'] = $admin_id;
                  $_SESSION['admin_register_sent_at'] = time();
                  $message = 'Admin user updated. OTP sent to the provided email for verification.';
                  $step = 2;
                } else {
                  $message = 'Admin updated but failed to send OTP: ' . $res['message'];
                }
              } else { $message = 'Update failed: ' . $upd->error; }
              $upd->close();
            } else { $message = 'Database error: ' . $mysqli->error; }
          }
        } else {
          if ($existsEmail && !$existsEmail['is_verified']) {
            $admin_id = (int)$existsEmail['admin_id'];
            $upd = $mysqli->prepare('UPDATE admin_user SET username = ?, password_hash = ?, is_verified = 0 WHERE admin_id = ? LIMIT 1');
            if ($upd) {
              $upd->bind_param('ssi', $username, $hash, $admin_id);
              if ($upd->execute()) {
                // send OTP after update
                $otp = generate_otp();
                $expires_at = date('Y-m-d H:i:s', time() + OTP_EXPIRY_MINUTES * 60);
                $u2 = $mysqli->prepare('UPDATE admin_user SET otp_code = ?, otp_expires_at = ? WHERE admin_id = ? LIMIT 1');
                if ($u2) { $u2->bind_param('ssi', $otp, $expires_at, $admin_id); $u2->execute(); $u2->close(); }
                $res = send_otp_email($email, $otp);
                if ($res['success']) {
                  $_SESSION['admin_register_step'] = 2;
                  $_SESSION['admin_register_email'] = $email;
                  $_SESSION['admin_register_id'] = $admin_id;
                  $_SESSION['admin_register_sent_at'] = time();
                  $message = 'Admin user updated. OTP sent to the provided email for verification.';
                  $step = 2;
                } else {
                  $message = 'Admin updated but failed to send OTP: ' . $res['message'];
                }
              } else { $message = 'Update failed: ' . $upd->error; }
              $upd->close();
            } else { $message = 'Database error: ' . $mysqli->error; }
          } else {
            $stmt = $mysqli->prepare('INSERT INTO admin_user (username, email, password_hash, is_verified) VALUES (?, ?, ?, 0)');
            if ($stmt) {
              $stmt->bind_param('sss', $username, $email, $hash);
              if ($stmt->execute()) {
                $admin_id = $stmt->insert_id;
                // generate and send OTP
                $otp = generate_otp();
                $expires_at = date('Y-m-d H:i:s', time() + OTP_EXPIRY_MINUTES * 60);
                $upd2 = $mysqli->prepare('UPDATE admin_user SET otp_code = ?, otp_expires_at = ? WHERE admin_id = ? LIMIT 1');
                if ($upd2) { $upd2->bind_param('ssi', $otp, $expires_at, $admin_id); $upd2->execute(); $upd2->close(); }
                $res = send_otp_email($email, $otp);
                if ($res['success']) {
                  $_SESSION['admin_register_step'] = 2;
                  $_SESSION['admin_register_email'] = $email;
                  $_SESSION['admin_register_id'] = $admin_id;
                  $_SESSION['admin_register_sent_at'] = time();
                  $message = 'Admin user created. OTP sent to the provided email for verification.';
                  $step = 2;
                } else {
                  $message = 'Admin created but failed to send OTP: ' . $res['message'];
                }
              } else { $message = 'Insert failed: ' . $stmt->error; }
              $stmt->close();
            } else { $message = 'Database error: ' . $mysqli->error; }
          }
        }
      }
    }
  }
  elseif ($action === 'verify_otp') {
    $otp = isset($_POST['otp']) ? trim($_POST['otp']) : '';
    if (!isset($_SESSION['admin_register_email']) || !isset($_SESSION['admin_register_id'])) {
      $message = 'Session expired. Please create admin again.';
      session_unset();
      $step = 1;
    } elseif ($otp === '') {
      $message = 'Please enter the OTP.';
      $step = 2;
    } else {
      $email = $_SESSION['admin_register_email'];
      $stmt = $mysqli->prepare('SELECT admin_id, username, otp_code, otp_expires_at FROM admin_user WHERE email = ? LIMIT 1');
      if ($stmt) {
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) {
          $message = 'Admin record not found.';
          $step = 1;
        } elseif ($row['otp_code'] !== $otp) {
          $message = 'Invalid OTP code.';
          $step = 2;
        } elseif (strtotime($row['otp_expires_at']) < time()) {
          $message = 'OTP expired. Please resend OTP.';
          $step = 2;
        } else {
          $up = $mysqli->prepare('UPDATE admin_user SET is_verified = 1, otp_code = NULL, otp_expires_at = NULL WHERE admin_id = ? LIMIT 1');
          if ($up) { $up->bind_param('i', $row['admin_id']); $up->execute(); $up->close(); }
          login_admin_session($row['username']);
          unset($_SESSION['admin_register_step'], $_SESSION['admin_register_email'], $_SESSION['admin_register_id'], $_SESSION['admin_register_sent_at']);
          header('Location: manage.php');
          exit;
        }
      } else { $message = 'Database error.'; }
    }
  }
  elseif ($action === 'resend_otp') {
    if (!isset($_SESSION['admin_register_email'])) {
      $message = 'Session expired. Please create admin again.';
      session_unset();
      $step = 1;
    } else {
      $email = $_SESSION['admin_register_email'];
      $otp = generate_otp();
      $expires_at = date('Y-m-d H:i:s', time() + OTP_EXPIRY_MINUTES * 60);
      $upd2 = $mysqli->prepare('UPDATE admin_user SET otp_code = ?, otp_expires_at = ?, is_verified = 0 WHERE email = ? LIMIT 1');
      if ($upd2) { $upd2->bind_param('sss', $otp, $expires_at, $email); $upd2->execute(); $upd2->close(); }
      $res = send_otp_email($email, $otp);
      if ($res['success']) { $_SESSION['admin_register_sent_at'] = time(); $message = 'New OTP sent to admin email.'; $_SESSION['admin_register_step'] = 2; $step = 2; } else { $message = 'Failed to resend OTP: ' . $res['message']; }
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Create Admin User</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card">
        <div class="card-header"><strong>Create Admin User</strong></div>
        <div class="card-body">
          <?php if ($message): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
          <?php endif; ?>
          <?php if ($step == 2): ?>
            <form method="post">
              <input type="hidden" name="action" value="verify_otp">
              <div class="mb-3"><label class="form-label">Enter OTP sent to <?php echo htmlspecialchars($_SESSION['admin_register_email'] ?? 'your email'); ?></label><input name="otp" class="form-control" required></div>
              <div class="d-flex justify-content-between">
                <div>
                  <button class="btn btn-success" type="submit">Verify OTP</button>
                  <button class="btn btn-secondary" type="submit" name="action" value="resend_otp">Resend OTP</button>
                </div>
                <a class="btn btn-link" href="admin_login.php">Cancel</a>
              </div>
            </form>
          <?php else: ?>
            <form method="post">
              <input type="hidden" name="action" value="create">
              <div class="mb-3"><label class="form-label">Full name</label><input name="username" class="form-control" required></div>
              <div class="mb-3"><label class="form-label">Email (for OTP)</label><input name="email" type="email" class="form-control" required></div>
              <div class="mb-3"><label class="form-label">Password</label><input name="password" type="password" class="form-control" required></div>
              <div class="d-flex justify-content-between">
                <button class="btn btn-primary" type="submit">Create Admin</button>
                <a class="btn btn-link" href="admin_login.php">Back</a>
              </div>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
