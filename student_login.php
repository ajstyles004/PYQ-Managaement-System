<?php
require_once 'db.php';
require_once 'student_auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$error = '';
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'student_dashboard.php';

// Ensure student_user table exists
$createSql = "CREATE TABLE IF NOT EXISTS student_user (
  student_id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  full_name VARCHAR(150) DEFAULT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);";
try { $mysqli->query($createSql); } catch (Throwable $e) { error_log('student_login: ensure table error: '.$e->getMessage()); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($username === '' || $password === '') {
        $error = 'Please provide username and password.';
    } else {
        $stmt = $mysqli->prepare('SELECT student_id, username, password_hash FROM student_user WHERE username = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            if ($row && !empty($row['password_hash']) && password_verify($password, $row['password_hash'])) {
                login_student_session($row['username'], $row['student_id']);
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
        <div class="card-header"><strong>Student Login</strong></div>
        <div class="card-body">
          <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
          <?php endif; ?>
          <form method="post" action="student_login.php<?php echo '?redirect=' . urlencode($redirect); ?>">
            <div class="mb-3"><label class="form-label">Username</label>
              <input name="username" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Password</label>
              <input name="password" type="password" class="form-control" required></div>
            <div class="d-flex justify-content-between align-items-center">
              <button class="btn btn-primary" type="submit">Login</button>
              <a class="btn btn-link" href="student_register.php">Register</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
