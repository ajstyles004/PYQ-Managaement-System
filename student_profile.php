<?php
require_once 'db.php';
require_once 'student_auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();
ensure_student();

$student_id = $_SESSION['student_id'] ?? null;
if (!$student_id) {
    header('Location: student_login.php');
    exit;
}

$error = '';
$message = '';

// load current data
$stmt = $mysqli->prepare('SELECT username, full_name FROM student_user WHERE student_id = ? LIMIT 1');
if ($stmt) {
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $cur = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} else {
    $cur = ['username' => $_SESSION['student_username'] ?? '', 'full_name' => ''];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($password !== '' && strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        if ($password === '') {
            $u = $mysqli->prepare('UPDATE student_user SET full_name = ? WHERE student_id = ?');
            $u->bind_param('si', $full_name, $student_id);
            if ($u->execute()) { $message = 'Profile updated.'; $cur['full_name'] = $full_name; } else { $error = 'Update failed: ' . $u->error; }
            $u->close();
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $u = $mysqli->prepare('UPDATE student_user SET full_name = ?, password_hash = ? WHERE student_id = ?');
            $u->bind_param('ssi', $full_name, $hash, $student_id);
            if ($u->execute()) { $message = 'Profile and password updated.'; $cur['full_name'] = $full_name; } else { $error = 'Update failed: ' . $u->error; }
            $u->close();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>My Profile</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4>My Profile</h4>
    <div>
      <a class="btn btn-sm btn-outline-secondary" href="student_dashboard.php">Back to Papers</a>
      <a class="btn btn-sm btn-outline-danger" href="student_logout.php">Logout</a>
    </div>
  </div>

  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card">
        <div class="card-body">
          <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
          <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
          <form method="post">
            <div class="mb-3"><label class="form-label">Username</label>
              <input class="form-control" value="<?php echo htmlspecialchars($cur['username'] ?? ''); ?>" readonly></div>
            <div class="mb-3"><label class="form-label">Full name</label>
              <input name="full_name" class="form-control" value="<?php echo htmlspecialchars($cur['full_name'] ?? ''); ?>"></div>
            <div class="mb-3"><label class="form-label">New password (leave blank to keep current)</label>
              <input name="password" type="password" class="form-control"></div>
            <div class="d-flex justify-content-between">
              <button class="btn btn-primary" type="submit">Save</button>
              <a class="btn btn-link" href="student_dashboard.php">Cancel</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
