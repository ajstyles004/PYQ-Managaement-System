<?php
require_once 'db.php';
require_once 'admin_auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$error = '';
$message = '';
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'manage.php';

// check if any admin exists (handle missing table gracefully)
$adminCount = 0;
try {
  $res = $mysqli->query("SELECT COUNT(*) AS c FROM admin_user");
  if ($res) { $r = $res->fetch_assoc(); $adminCount = intval($r['c']); }
} catch (mysqli_sql_exception $e) {
  // If table doesn't exist, create it so user can create initial admin via the UI
  $msg = $e->getMessage();
  if (stripos($msg, "doesn't exist") !== false || stripos($msg, 'does not exist') !== false || stripos($msg, 'no such table') !== false) {
    $createSql = "CREATE TABLE IF NOT EXISTS admin_user (
  admin_id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);";
    try {
      $mysqli->query($createSql);
      // table created; adminCount remains 0
      $adminCount = 0;
    } catch (Throwable $e2) {
      // leave adminCount = 0 and show a generic error later; log the error
      error_log('Failed to create admin_user table: ' . $e2->getMessage());
    }
  } else {
    // rethrow unexpected DB errors
    throw $e;
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['act'] ?? 'login';

    if ($action === 'create') {
        // create new admin
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        if ($username === '' || $password === '') {
            $error = 'Please provide username and password.';
        } else {
            // check if username already exists
            $chk = $mysqli->prepare('SELECT admin_id FROM admin_user WHERE username = ? LIMIT 1');
            $chk->bind_param('s', $username);
            $chk->execute();
            $exists = $chk->get_result()->fetch_assoc();
            $chk->close();
            if ($exists) {
                $error = 'Username already taken.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $mysqli->prepare('INSERT INTO admin_user (username, password_hash) VALUES (?, ?)');
                if ($stmt) {
                    $stmt->bind_param('ss', $username, $hash);
                    if ($stmt->execute()) {
                        // auto-login after creation
                        login_admin_session($username);
                        header('Location: ' . $redirect);
                        exit;
                    } else {
                        $error = 'Insert failed: ' . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $error = 'Database error: ' . $mysqli->error;
                }
            }
        }

    } else {
        // login attempt
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        if ($username === '' || $password === '') {
            $error = 'Please provide username and password.';
        } else {
            $stmt = $mysqli->prepare('SELECT admin_id, username, password_hash FROM admin_user WHERE username = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('s', $username);
                $stmt->execute();
                $res = $stmt->get_result();
                $row = $res->fetch_assoc();
                if ($row && !empty($row['password_hash']) && password_verify($password, $row['password_hash'])) {
                    login_admin_session($row['username']);
                    header('Location: ' . $redirect);
                    exit;
                } else {
                    $error = 'Invalid username or password.';
                }
            } else {
                $error = 'Database error.';
            }
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
            <div class="mb-3"><label class="form-label">Username</label>
              <input name="username" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Password</label>
              <input name="password" type="password" class="form-control" required></div>
            <div class="d-flex justify-content-between align-items-center">
              <button class="btn btn-primary" type="submit">Login</button>
              <a class="btn btn-link" href="choose_role.php">Back to site</a>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <?php if ($adminCount === 0): ?>
      <div class="card">
        <div class="card-header"><strong>Create Admin</strong></div>
        <div class="card-body">
          <p class="small-muted">No admin user found. Create the initial administrator account.</p>
          <form method="post" action="admin_login.php<?php echo '?redirect=' . urlencode($redirect); ?>">
            <input type="hidden" name="act" value="create">
            <div class="mb-3"><label class="form-label">Username</label>
              <input name="username" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Password</label>
              <input name="password" type="password" class="form-control" required></div>
            <div class="d-flex justify-content-between">
              <button class="btn btn-success" type="submit">Create Admin</button>
              <a class="btn btn-link" href="index.php">Back to site</a>
            </div>
          </form>
        </div>
      </div>
      <?php else: ?>
      <div class="card">
        <div class="card-header"><strong>Create Admin</strong></div>
        <div class="card-body">
          <p class="small-muted">An admin account already exists. To add another administrator use <a href="create_admin.php">Create Admin</a> (requires existing admin session).</p>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
