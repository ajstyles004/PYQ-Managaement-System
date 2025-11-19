<?php
// subject_api.php - Improved version (replace existing)
// Note: for development this returns useful error messages. Remove debug output in production.
require 'db.php';
header('Content-Type: application/json; charset=utf-8');

// helper
function jsonErr($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

$action = $_REQUEST['action'] ?? 'list';

// Protect non-public actions
if (!in_array($action, ['list','get'])) {
    require_once 'admin_auth.php';
    ensure_admin();
}

try {

    if ($action === 'list') {
        $dept = isset($_GET['department']) && $_GET['department'] !== '' ? intval($_GET['department']) : null;
        $sql = "SELECT s.subject_id, s.subject_code, s.subject_name, s.department_id, s.semester, d.department_name
                FROM subject s LEFT JOIN department d ON s.department_id = d.department_id WHERE 1=1 ";
        $params = []; $types = '';
        if ($dept !== null) { $sql .= " AND s.department_id = ?"; $params[] = $dept; $types .= 'i'; }
        $sql .= " ORDER BY s.subject_name";
        if ($types === '') {
            $res = $mysqli->query($sql);
            if ($res === false) jsonErr("DB error: " . $mysqli->error, 500);
            $out = []; while ($r = $res->fetch_assoc()) $out[] = $r;
            echo json_encode($out); exit;
        } else {
            $stmt = $mysqli->prepare($sql);
            if (!$stmt) jsonErr("DB prepare error: " . $mysqli->error, 500);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $out = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
            echo json_encode($out); exit;
        }
    }

    if ($action === 'get') {
        $id = intval($_GET['id'] ?? 0);
        if ($id <= 0) jsonErr("Invalid id", 400);
        $stmt = $mysqli->prepare("SELECT subject_id, subject_code, subject_name, department_id, semester FROM subject WHERE subject_id = ?");
        $stmt->bind_param('i',$id); $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc(); $stmt->close();
        echo json_encode($r ?: []);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonErr('Method not allowed', 405);
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $dept = intval($_POST['department_id'] ?? 0);
        $code = trim($_POST['subject_code'] ?? '');
        $name = trim($_POST['subject_name'] ?? '');
        $sem_raw = $_POST['semester'] ?? null;
        $sem = ($sem_raw === null || $sem_raw === '' ) ? null : intval($sem_raw);

        if ($dept <= 0) jsonErr('department_id is required', 400);
        if ($name === '') jsonErr('subject_name is required', 400);

        // ensure department exists
        $chk = $mysqli->prepare("SELECT department_id FROM department WHERE department_id = ?");
        $chk->bind_param('i', $dept);
        $chk->execute();
        $reschk = $chk->get_result();
        if ($reschk->num_rows === 0) jsonErr('Invalid department_id', 400);
        $chk->close();

        if ($action === 'create') {
            $stmt = $mysqli->prepare("INSERT INTO subject (subject_code, subject_name, department_id, semester) VALUES (?, ?, ?, ?)");
            if (!$stmt) jsonErr("DB prepare error: " . $mysqli->error, 500);
            // if semester is null, insert 0 or NULL depending on schema. We'll use 0 to avoid issues.
            $sem_for_db = ($sem === null) ? 0 : $sem;
            $stmt->bind_param('ssii', $code, $name, $dept, $sem_for_db);
            if ($stmt->execute()) {
                echo json_encode(['success'=>true,'subject_id'=>$stmt->insert_id]);
            } else {
                jsonErr('DB insert error: ' . $stmt->error, 500);
            }
            $stmt->close();
            exit;
        } else {
            $id = intval($_POST['subject_id'] ?? 0);
            if ($id <= 0) jsonErr('subject_id is required for update', 400);
            $stmt = $mysqli->prepare("UPDATE subject SET subject_code=?, subject_name=?, department_id=?, semester=? WHERE subject_id=?");
            if (!$stmt) jsonErr("DB prepare error: " . $mysqli->error, 500);
            $sem_for_db = ($sem === null) ? 0 : $sem;
            $stmt->bind_param('ssiii', $code, $name, $dept, $sem_for_db, $id);
            if ($stmt->execute()) {
                echo json_encode(['success'=>true]);
            } else {
                jsonErr('DB update error: ' . $stmt->error, 500);
            }
            $stmt->close();
            exit;
        }
    }

    if ($action === 'delete') {
        $id = intval($_POST['subject_id'] ?? $_POST['id'] ?? 0);
        if ($id <= 0) jsonErr('Invalid subject id', 400);
        $stmt = $mysqli->prepare("DELETE FROM subject WHERE subject_id = ?");
        if (!$stmt) jsonErr("DB prepare error: " . $mysqli->error, 500);
        $stmt->bind_param('i',$id);
        if ($stmt->execute()) {
            echo json_encode(['success'=>true]);
        } else {
            jsonErr('DB delete error: ' . $stmt->error, 500);
        }
        $stmt->close(); exit;
    }

    jsonErr('Unknown action', 400);

} catch (Throwable $e) {
    error_log("subject_api error: " . $e->getMessage());
    jsonErr('Server error: see logs', 500);
}
