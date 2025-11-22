<?php
// migrate_to_email_otp.php - Migrate student_user table to support email + OTP login
// Run this once to update your existing database schema
// Then delete or disable this file for security

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['confirm'])) {
    ?>
    <!doctype html>
    <html lang="en">
    <head>
      <meta charset="utf-8">
      <title>Database Migration - Email + OTP</title>
      <meta name="viewport" content="width=device-width,initial-scale=1">
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="container py-5">
      <div class="row justify-content-center">
        <div class="col-md-8">
          <div class="card">
            <div class="card-header bg-warning text-dark"><strong>⚠️ Database Migration Required</strong></div>
            <div class="card-body">
              <h5>This will add email and OTP columns to the student_user table.</h5>
              <p class="text-muted">Changes:</p>
              <ul>
                <li>Add <code>email VARCHAR(150) NOT NULL UNIQUE</code></li>
                <li>Add <code>otp_code VARCHAR(10) DEFAULT NULL</code></li>
                <li>Add <code>otp_expires_at DATETIME DEFAULT NULL</code></li>
                <li>Add <code>is_verified TINYINT(1) DEFAULT 0</code></li>
                <li>Make <code>username</code> optional (for migration)</li>
              </ul>
              <p class="alert alert-info"><strong>⚠️ Important:</strong> Backup your database before running this migration!</p>
              <form method="POST">
                <input type="hidden" name="confirm" value="1">
                <button type="submit" class="btn btn-danger">Run Migration</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
              </form>
            </div>
          </div>
        </div>
      </div>
    </body>
    </html>
    <?php
    exit;
}

// Perform migration
$migrations = [
    // Check if email column exists, if not add it
    "ALTER TABLE student_user ADD COLUMN email VARCHAR(150) UNIQUE DEFAULT '' AFTER username",
    "ALTER TABLE student_user ADD COLUMN otp_code VARCHAR(10) DEFAULT NULL AFTER password_hash",
    "ALTER TABLE student_user ADD COLUMN otp_expires_at DATETIME DEFAULT NULL AFTER otp_code",
    "ALTER TABLE student_user ADD COLUMN is_verified TINYINT(1) DEFAULT 0 AFTER otp_expires_at",
    // Make username optional
    "ALTER TABLE student_user MODIFY username VARCHAR(100) UNIQUE DEFAULT NULL",
];

$errors = [];
$success = [];

foreach ($migrations as $sql) {
    try {
        $mysqli->query($sql);
        $success[] = "✓ " . substr($sql, 0, 50) . "...";
    } catch (Exception $e) {
        // Column might already exist; skip silently
        if (stripos($e->getMessage(), 'Duplicate column') === false && 
            stripos($e->getMessage(), 'already exists') === false) {
            $errors[] = "⚠️ " . $e->getMessage();
        } else {
            $success[] = "✓ Column already exists (skipped)";
        }
    }
}

// If no email value set, generate one from username for existing records
try {
    $mysqli->query("UPDATE student_user SET email = CONCAT(username, '_', student_id, '@auto.local') WHERE email = ''");
} catch (Exception $e) {
    $errors[] = "Could not auto-generate emails: " . $e->getMessage();
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Migration Results</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-8">
      <div class="card">
        <div class="card-header bg-success text-white"><strong>Migration Complete</strong></div>
        <div class="card-body">
          <?php if (!empty($success)): ?>
            <h5 class="text-success">✓ Successful Changes:</h5>
            <ul>
              <?php foreach ($success as $msg): ?>
                <li><?php echo htmlspecialchars($msg); ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
          
          <?php if (!empty($errors)): ?>
            <h5 class="text-warning">⚠️ Warnings:</h5>
            <ul>
              <?php foreach ($errors as $msg): ?>
                <li><?php echo htmlspecialchars($msg); ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
          
          <p class="alert alert-info mt-3">
            <strong>Next Steps:</strong><br>
            1. Update <code>otp_config.php</code> with your SMTP settings (Gmail, SendGrid, etc.)<br>
            2. Replace <code>student_login.php</code> with the new email + OTP version<br>
            3. Replace <code>student_register.php</code> with the new version<br>
            4. Delete this file (<code>migrate_to_email_otp.php</code>) for security
          </p>
          
          <a href="index.php" class="btn btn-primary">Back to Home</a>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
