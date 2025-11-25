<?php
// get_subjects.php
require 'db.php';
header('Content-Type: application/json; charset=utf-8');

$dept = isset($_GET['department']) ? intval($_GET['department']) : 0;
if ($dept <= 0) {
    echo json_encode([]);
    exit;
}

$stmt = $mysqli->prepare("SELECT subject_id, subject_code, subject_name FROM subject WHERE department_id = ? ORDER BY subject_name");
$stmt->bind_param('i', $dept);
$stmt->execute();
$res = $stmt->get_result();
$rows = [];
while ($r = $res->fetch_assoc()) $rows[] = $r;
$stmt->close();
echo json_encode($rows);
