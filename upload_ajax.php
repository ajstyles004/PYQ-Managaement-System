<?php
// upload_ajax.php
require 'db.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'error'=>'Method not allowed']);
    exit;
}

$title = trim($_POST['title'] ?? '');
$department_id = intval($_POST['department'] ?? 0);
$subject_id = intval($_POST['subject'] ?? 0);
$year = intval($_POST['year'] ?? 0);
$semester = intval($_POST['semester'] ?? 0);
$exam_type = trim($_POST['exam_type'] ?? '');

if ($title === '' || $department_id <= 0 || $subject_id <= 0 || $year <= 1900) {
    echo json_encode(['success'=>false,'error'=>'Missing required fields']);
    exit;
}

if (!isset($_FILES['paper_file'])) {
    echo json_encode(['success'=>false,'error'=>'No file uploaded']);
    exit;
}

$file = $_FILES['paper_file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success'=>false,'error'=>'Upload error code: ' . $file['error']]);
    exit;
}

// check mimetype
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
if (!in_array($mime, ['application/pdf','application/x-pdf'])) {
    echo json_encode(['success'=>false,'error'=>'Only PDF files allowed: detected ' . $mime]);
    exit;
}

// save file
$uploadDir = __DIR__ . '/uploads';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$origName = basename($file['name']);
$ext = pathinfo($origName, PATHINFO_EXTENSION);
$stored = 'paper_' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
$dest = $uploadDir . '/' . $stored;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    echo json_encode(['success'=>false,'error'=>'Failed to move uploaded file']);
    exit;
}

// insert DB record
$filepath = 'uploads/' . $stored;
$stmt = $mysqli->prepare("INSERT INTO paper (title, department_id, subject_id, year, semester, exam_type, filename, filepath) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param('siiissss', $title, $department_id, $subject_id, $year, $semester, $exam_type, $origName, $filepath);
if ($stmt->execute()) {
    echo json_encode(['success'=>true, 'paper_id'=>$stmt->insert_id]);
} else {
    // rollback file if db insert fails
    @unlink($dest);
    echo json_encode(['success'=>false,'error'=>'DB error: ' . $stmt->error]);
}
$stmt->close();
