<?php
// paper_api.php - Paper CRUD API (list/get/update/delete)
require 'db.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? 'list';

// Protect non-public actions (modifications)
if (!in_array($action, ['list','get'])) {
    require_once 'admin_auth.php';
    ensure_admin();
}

try {
    // LIST - optionally filter by subject_id or department_id
    if ($action === 'list') {
        $subject = isset($_GET['subject']) && $_GET['subject'] !== '' ? intval($_GET['subject']) : null;
        $dept = isset($_GET['department']) && $_GET['department'] !== '' ? intval($_GET['department']) : null;
        $sql = "SELECT p.paper_id, p.title, p.year, p.semester, p.exam_type, p.filename, p.filepath,
                       p.department_id, p.subject_id, d.department_name, s.subject_name
                FROM paper p
                LEFT JOIN department d ON p.department_id = d.department_id
                LEFT JOIN subject s ON p.subject_id = s.subject_id
                WHERE 1=1";
        $params = []; $types = '';
        if ($subject !== null) { $sql .= " AND p.subject_id = ?"; $params[] = $subject; $types .= 'i'; }
        if ($dept !== null) { $sql .= " AND p.department_id = ?"; $params[] = $dept; $types .= 'i'; }
        $sql .= " ORDER BY p.year DESC, p.uploaded_on DESC";
        if ($types === '') {
            $res = $mysqli->query($sql);
            if ($res === false) throw new Exception("DB error: ".$mysqli->error);
            $out = []; while ($r = $res->fetch_assoc()) $out[] = $r;
            echo json_encode($out); exit;
        } else {
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $out = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            echo json_encode($out); exit;
        }
    }

    // GET single paper
    if ($action === 'get') {
        $id = intval($_GET['id'] ?? 0);
        if ($id <= 0) throw new Exception("Invalid id");
        $stmt = $mysqli->prepare("SELECT paper_id, title, year, semester, exam_type, filename, filepath, department_id, subject_id FROM paper WHERE paper_id = ?");
        $stmt->bind_param('i',$id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        echo json_encode($row ?: []);
        exit;
    }

    // POST-only actions below
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'error'=>'Method not allowed']); exit; }
    $action = $_POST['action'] ?? '';

    // UPDATE metadata or replace file (file optional)
    if ($action === 'update') {
        $id = intval($_POST['paper_id'] ?? 0);
        if ($id <= 0) { echo json_encode(['success'=>false,'error'=>'Invalid paper_id']); exit; }

        $title = trim($_POST['title'] ?? '');
        $year = intval($_POST['year'] ?? 0);
        $semester = intval($_POST['semester'] ?? 0);
        $exam_type = trim($_POST['exam_type'] ?? '');
        $department_id = intval($_POST['department_id'] ?? 0);
        $subject_id = intval($_POST['subject_id'] ?? 0);

        if ($title === '' || $department_id <= 0 || $subject_id <= 0 || $year <= 1900) {
            echo json_encode(['success'=>false,'error'=>'Missing required fields']); exit;
        }

        // optional file replacement
        $newFilePath = null;
        $newOrigName = null;
        if (!empty($_FILES['paper_file']) && $_FILES['paper_file']['error'] === UPLOAD_ERR_OK) {
            $f = $_FILES['paper_file'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($f['tmp_name']);
            if (!in_array($mime, ['application/pdf','application/x-pdf'])) {
                echo json_encode(['success'=>false,'error'=>'Only PDF allowed']); exit;
            }

            $uploadDir = __DIR__ . '/uploads';
            if (!is_dir($uploadDir)) mkdir($uploadDir,0755,true);
            $origName = basename($f['name']);
            $ext = pathinfo($origName, PATHINFO_EXTENSION);
            $stored = 'paper_' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
            $dest = $uploadDir . '/' . $stored;
            if (!move_uploaded_file($f['tmp_name'], $dest)) {
                echo json_encode(['success'=>false,'error'=>'Failed to save uploaded file']); exit;
            }
            $newFilePath = 'uploads/'.$stored;
            $newOrigName = $origName;
        }

        // update DB - if file replaced, update filename/filepath too
        if ($newFilePath !== null) {
            // get old file to delete after success
            $stmt = $mysqli->prepare("SELECT filepath FROM paper WHERE paper_id = ?");
            $stmt->bind_param('i',$id); $stmt->execute(); $old = $stmt->get_result()->fetch_assoc(); $stmt->close();
            $stmt = $mysqli->prepare("UPDATE paper SET title=?, year=?, semester=?, exam_type=?, department_id=?, subject_id=?, filename=?, filepath=? WHERE paper_id=?");
            $stmt->bind_param('siiisissi', $title, $year, $semester, $exam_type, $department_id, $subject_id, $newOrigName, $newFilePath, $id);
        } else {
            $stmt = $mysqli->prepare("UPDATE paper SET title=?, year=?, semester=?, exam_type=?, department_id=?, subject_id=? WHERE paper_id=?");
            $stmt->bind_param('siiiisi', $title, $year, $semester, $exam_type, $department_id, $subject_id, $id);
        }

        if ($stmt->execute()) {
            $stmt->close();
            // delete old file if replaced
            if (isset($old) && !empty($old['filepath']) && $newFilePath !== null) {
                $oldFull = __DIR__ . '/' . $old['filepath'];
                if (file_exists($oldFull)) @unlink($oldFull);
            }
            echo json_encode(['success'=>true]);
        } else {
            $err = $stmt->error; $stmt->close();
            // rollback file if uploaded
            if ($newFilePath !== null) @unlink(__DIR__ . '/' . $newFilePath);
            echo json_encode(['success'=>false,'error'=>'DB update error: '.$err]);
        }
        exit;
    }

    // DELETE paper
    if ($action === 'delete') {
        $id = intval($_POST['paper_id'] ?? $_POST['id'] ?? 0);
        if ($id <= 0) { echo json_encode(['success'=>false,'error'=>'Invalid id']); exit; }
        // fetch file path to delete
        $stmt = $mysqli->prepare("SELECT filepath FROM paper WHERE paper_id = ?");
        $stmt->bind_param('i',$id); $stmt->execute(); $r = $stmt->get_result()->fetch_assoc(); $stmt->close();
        $stmt = $mysqli->prepare("DELETE FROM paper WHERE paper_id = ?");
        $stmt->bind_param('i',$id);
        if ($stmt->execute()) {
            // delete file
            if (!empty($r['filepath'])) {
                $full = __DIR__ . '/' . $r['filepath'];
                if (file_exists($full)) @unlink($full);
            }
            echo json_encode(['success'=>true]);
        } else {
            echo json_encode(['success'=>false,'error'=>$stmt->error]);
        }
        $stmt->close(); exit;
    }

    echo json_encode(['success'=>false,'error'=>'Unknown action']);
} catch (Throwable $e) {
    error_log("paper_api error: ".$e->getMessage());
    echo json_encode(['success'=>false,'error'=>'Server error']);
}
