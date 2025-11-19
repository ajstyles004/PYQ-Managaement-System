<?php
// create_admin.php - simple helper to create an admin user (web or CLI)
require_once 'db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$message = '';
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
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    if ($username === '' || $password === '') {
        $message = 'Please provide both username and password.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare('INSERT INTO admin_user (username, password_hash) VALUES (?, ?)');
        if ($stmt) {
            $stmt->bind_param('ss', $username, $hash);
            if ($stmt->execute()) {
                $message = 'Admin user created successfully.';
            } else {
                $message = 'Insert failed: ' . $stmt->error;
            }
        } else {
            $message = 'Database error: ' . $mysqli->error;
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
          <form method="post">
            <div class="mb-3"><label class="form-label">Username</label><input name="username" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Password</label><input name="password" type="password" class="form-control" required></div>
            <div class="d-flex justify-content-between">
              <button class="btn btn-primary" type="submit">Create Admin</button>
              <a class="btn btn-link" href="index.php">Back</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
