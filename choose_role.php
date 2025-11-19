<?php
// choose_role.php - simple landing page to pick Student or Admin
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'student_auth.php';
require_once 'admin_auth.php';

$student_target = is_student_logged_in() ? 'student_dashboard.php' : 'student_login.php';
$admin_target = (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) ? 'manage.php' : 'admin_login.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Sign in - Choose Role</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>body{background:#f6f8fa} .choice-card{min-height:200px}</style>
</head>
<body class="container py-5">
  <div class="text-center mb-4">
    <h1 class="h4">Sign in</h1>
    <p class="small-muted">Choose whether you are a Student (view only) or an Admin (manage content)</p>
  </div>
  <div class="row">
    <div class="col-md-6">
      <div class="card choice-card text-center p-4">
        <h4>Student</h4>
        <p class="small-muted">View and download question papers (no editing rights)</p>
        <a class="btn btn-primary btn-lg" href="<?php echo htmlspecialchars($student_target); ?>">I am a Student</a>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card choice-card text-center p-4">
        <h4>Admin</h4>
        <p class="small-muted">Manage departments, subjects and upload papers</p>
        <a class="btn btn-outline-dark btn-lg" href="<?php echo htmlspecialchars($admin_target); ?>">I am an Admin</a>
      </div>
    </div>
  </div>
</body>
</html>
