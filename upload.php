<?php
// upload.php
require 'db.php';

$errors = [];
$success = false;

// fetch departments for dropdown
$departments = [];
$res = $mysqli->query("SELECT department_id, department_code, department_name FROM department ORDER BY department_name");
while ($row = $res->fetch_assoc()) $departments[] = $row;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // basic fields
    $title = trim($_POST['title'] ?? '');
    $department_id = intval($_POST['department'] ?? 0);
    $subject_id = intval($_POST['subject'] ?? 0); // you can later replace with dropdown
    $year = intval($_POST['year'] ?? 0);
    $semester = intval($_POST['semester'] ?? 0);
    $exam_type = trim($_POST['exam_type'] ?? '');

    if ($title === '') $errors[] = "Title is required.";
    if ($department_id <= 0) $errors[] = "Select department.";
    if ($subject_id <= 0) $errors[] = "Provide subject id (create subject in DB first).";
    if ($year <= 1900) $errors[] = "Provide valid year.";

    if (!isset($_FILES['paper_file'])) $errors[] = "Select a PDF file.";

    if (empty($errors)) {
        $file = $_FILES['paper_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Upload error code: " . $file['error'];
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            if ($mime !== 'application/pdf' && $mime !== 'application/x-pdf') {
                $errors[] = "Only PDF files allowed. Detected: $mime";
            } else {
                $uploadDir = __DIR__ . '/uploads';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $origName = basename($file['name']);
                $ext = pathinfo($origName, PATHINFO_EXTENSION);
                $stored = 'paper_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $dest = $uploadDir . '/' . $stored;
                if (!move_uploaded_file($file['tmp_name'], $dest)) {
                    $errors[] = "Failed saving uploaded file.";
                } else {
                    $filepath = 'uploads/' . $stored;
                    $stmt = $mysqli->prepare("INSERT INTO paper (title, department_id, subject_id, year, semester, exam_type, filename, filepath) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param('siiissss', $title, $department_id, $subject_id, $year, $semester, $exam_type, $origName, $filepath);
                    if ($stmt->execute()) {
                        $success = true;
                    } else {
                        $errors[] = "DB insert error: " . $stmt->error;
                        @unlink($dest);
                    }
                    $stmt->close();
                }
            }
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Upload Paper</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container py-4">
  <h2>Upload Question Paper</h2>

  <?php if ($success): ?>
    <div class="alert alert-success">Uploaded successfully. <a href="list.php">View Papers</a></div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
      <ul><?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?></ul>
    </div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data">
    <div class="mb-3">
      <label class="form-label">Title</label>
      <input name="title" class="form-control" required value="<?=htmlspecialchars($_POST['title'] ?? '')?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Department</label>
      <select name="department" class="form-select" required>
        <option value="">-- Select department --</option>
        <?php foreach ($departments as $d): ?>
          <option value="<?=$d['department_id']?>" <?= (isset($_POST['department']) && $_POST['department']==$d['department_id']) ? 'selected':''?> >
            <?=htmlspecialchars($d['department_name'])?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">Subject ID</label>
      <input name="subject" class="form-control" placeholder="Enter subject_id (create in DB)" required value="<?=htmlspecialchars($_POST['subject'] ?? '')?>">
      <div class="form-text">Create subject rows in DB (phpMyAdmin) first; we'll use subject_id here. Later this can be a dropdown.</div>
    </div>

    <div class="row mb-3">
      <div class="col">
        <label class="form-label">Year</label>
        <input name="year" type="number" class="form-control" required value="<?=htmlspecialchars($_POST['year'] ?? '')?>">
      </div>
      <div class="col">
        <label class="form-label">Semester</label>
        <input name="semester" type="number" class="form-control" value="<?=htmlspecialchars($_POST['semester'] ?? '')?>">
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label">Exam Type</label>
      <input name="exam_type" class="form-control" value="<?=htmlspecialchars($_POST['exam_type'] ?? '')?>">
    </div>

    <div class="mb-3">
      <label class="form-label">PDF File</label>
      <input name="paper_file" type="file" accept="application/pdf" class="form-control" required>
    </div>

    <button class="btn btn-success" type="submit">Upload</button>
    <a class="btn btn-secondary" href="index.php">Cancel</a>
  </form>
</body>
</html>