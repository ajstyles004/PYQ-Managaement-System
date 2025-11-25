<?php
// create_user.php - simple helper to create an uploader (user) record
require_once 'db.php';
require_once 'admin_auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();
ensure_admin();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    if ($name === '' || $email === '') {
        $message = 'Please provide both name and email.';
    } else {
        $stmt = $mysqli->prepare('INSERT INTO uploader (name, email) VALUES (?, ?)');
        if ($stmt) {
            $stmt->bind_param('ss', $name, $email);
            if ($stmt->execute()) {
                $message = 'User created successfully.';
            } else {
                $message = 'Insert failed: ' . $stmt->error;
            }
            $stmt->close();
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
  <title>Create User</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card">
        <div class="card-header"><strong>Create User (Uploader)</strong></div>
        <div class="card-body">
          <?php if ($message): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
          <?php endif; ?>
          <form method="post">
            <div class="mb-3"><label class="form-label">Full name</label><input name="name" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Email</label><input name="email" type="email" class="form-control" required></div>
            <div class="d-flex justify-content-between">
              <button class="btn btn-primary" type="submit">Create User</button>
              <a class="btn btn-link" href="manage.php">Back to Admin</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
