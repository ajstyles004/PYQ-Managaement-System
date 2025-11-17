<?php
// add_subject.php
require 'db.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'error'=>'Method not allowed']);
    exit;
}

$department_id = isset($_POST['department_id']) ? intval($_POST['department_id']) : 0;
$subject_code  = trim($_POST['subject_code'] ?? '');
$subject_name  = trim($_POST['subject_name'] ?? '');
$semester      = isset($_POST['semester']) ? intval($_POST['semester']) : null;

if ($department_id <= 0 || $subject_name === '') {
    echo json_encode(['success'=>false,'error'=>'department_id and subject_name are required']);
    exit;
}

try {
    // Prevent duplicate subject_code under same department (optional)
    if ($subject_code !== '') {
        $chk = $mysqli->prepare("SELECT subject_id FROM subject WHERE subject_code = ? AND department_id = ?");
        $chk->bind_param('si', $subject_code, $department_id);
        $chk->execute();
        $r = $chk->get_result();
        if ($r && $r->num_rows > 0) {
            echo json_encode(['success'=>false,'error'=>'Subject code already exists for this department']);
            exit;
        }
        $chk->close();
    }

    $stmt = $mysqli->prepare("INSERT INTO subject (subject_code, subject_name, department_id, semester) VALUES (?, ?, ?, ?)");
    // semester can be null
    if ($semester === null || $semester === 0) {
        $stmt->bind_param('ssii', $subject_code, $subject_name, $department_id, $semester);
        // above line keeps int binding but if semester null, it's 0. That's fine for most cases.
    } else {
        $stmt->bind_param('ssii', $subject_code, $subject_name, $department_id, $semester);
    }

    if ($stmt->execute()) {
        $newId = $stmt->insert_id;
        echo json_encode(['success'=>true, 'subject_id'=>$newId, 'subject_name'=>$subject_name, 'subject_code'=>$subject_code, 'semester'=>$semester]);
    } else {
        echo json_encode(['success'=>false,'error'=>'DB error: ' . $stmt->error]);
    }
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success'=>false,'error'=>'Exception: ' . $e->getMessage()]);
}
