<?php
require_once 'db.php';
require_once 'student_auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$error = '';
$message = '';

// Ensure student_user table exists (safe to run repeatedly)
$createSql = "CREATE TABLE IF NOT EXISTS student_user (
  student_id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  full_name VARCHAR(150) DEFAULT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);";
try { $mysqli->query($createSql); } catch (Throwable $e) { error_log('student_register: ensure table error: '.$e->getMessage()); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($username === '' || $password === '') {
        $error = 'Please provide username and password.';
    } else {
        $chk = $mysqli->prepare('SELECT student_id FROM student_user WHERE username = ? LIMIT 1');
        $chk->bind_param('s', $username);
        $chk->execute();
        $exists = $chk->get_result()->fetch_assoc();
        $chk->close();
        if ($exists) {
            $error = 'Username already taken.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare('INSERT INTO student_user (username, full_name, password_hash) VALUES (?, ?, ?)');
            if ($stmt) {
                $stmt->bind_param('sss', $username, $full_name, $hash);
                if ($stmt->execute()) {
                    $message = 'Registration successful. You can now login.';
                } else {
                    $error = 'Insert failed: ' . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = 'Database error: ' . $mysqli->error;
            }
        }
    }
}
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
        <div class="card-header"><strong>Student Register</strong></div>
        <div class="card-body">
          <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
          <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
          <form method="post" action="student_register.php">
            <div class="mb-3"><label class="form-label">Username</label><input name="username" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Full name (optional)</label><input name="full_name" class="form-control"></div>
            <div class="mb-3"><label class="form-label">Password</label><input name="password" type="password" class="form-control" required></div>
            <div class="d-flex justify-content-between">
              <button class="btn btn-primary" type="submit">Register</button>
              <a class="btn btn-link" href="student_login.php">Back to login</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
