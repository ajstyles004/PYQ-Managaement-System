<?php
// dept_api.php - Department CRUD API
require 'db.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? 'list';

if ($action === 'list') {
    $res = $mysqli->query("SELECT department_id, department_code, department_name FROM department ORDER BY department_name");
    $out = [];
    while ($r = $res->fetch_assoc()) $out[] = $r;
    echo json_encode($out); exit;
}

if ($action === 'get') {
    $id = intval($_GET['id'] ?? 0);
    $stmt = $mysqli->prepare("SELECT department_id, department_code, department_name FROM department WHERE department_id = ?");
    $stmt->bind_param('i',$id); $stmt->execute(); $r = $stmt->get_result()->fetch_assoc(); $stmt->close();
    echo json_encode($r ?: []); exit;
}

// POST actions
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'error'=>'Method not allowed']); exit; }

$action = $_POST['action'] ?? '';
if ($action === 'create') {
    $code = trim($_POST['department_code'] ?? '');
    $name = trim($_POST['department_name'] ?? '');
    if ($name === '') { echo json_encode(['success'=>false,'error'=>'Name required']); exit; }
    $stmt = $mysqli->prepare("INSERT INTO department (department_code, department_name) VALUES (?, ?)");
    $stmt->bind_param('ss',$code,$name);
    if ($stmt->execute()) echo json_encode(['success'=>true,'department_id'=>$stmt->insert_id]);
    else echo json_encode(['success'=>false,'error'=>$stmt->error]);
    $stmt->close();
    exit;
}

if ($action === 'update') {
    $id = intval($_POST['department_id'] ?? 0);
    $code = trim($_POST['department_code'] ?? '');
    $name = trim($_POST['department_name'] ?? '');
    if ($id<=0 || $name === '') { echo json_encode(['success'=>false,'error'=>'Invalid']); exit; }
    $stmt = $mysqli->prepare("UPDATE department SET department_code=?, department_name=? WHERE department_id=?");
    $stmt->bind_param('ssi',$code,$name,$id);
    if ($stmt->execute()) echo json_encode(['success'=>true]);
    else echo json_encode(['success'=>false,'error'=>$stmt->error]);
    $stmt->close();
    exit;
}

if ($action === 'delete') {
    $id = intval($_POST['department_id'] ?? $_POST['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['success'=>false,'error'=>'Invalid id']); exit; }
    // deleting department will cascade delete subjects (if FK ON DELETE CASCADE set) — careful
    $stmt = $mysqli->prepare("DELETE FROM department WHERE department_id = ?");
    $stmt->bind_param('i',$id);
    if ($stmt->execute()) echo json_encode(['success'=>true]);
    else echo json_encode(['success'=>false,'error'=>$stmt->error]);
    $stmt->close();
    exit;
}

echo json_encode(['success'=>false,'error'=>'Unknown action']);
