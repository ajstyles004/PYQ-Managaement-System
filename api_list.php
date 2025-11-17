<?php
// api_list.php  (fixed - returns department and subject keys expected by JS)
require 'db.php';
header('Content-Type: application/json; charset=utf-8');

$dept = isset($_GET['department']) && $_GET['department'] !== '' ? intval($_GET['department']) : null;
$year = isset($_GET['year']) && $_GET['year'] !== '' ? intval($_GET['year']) : null;
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

$sql = "SELECT p.paper_id, p.title, p.year, p.filename, p.filepath,
               d.department_name AS department, s.subject_name AS subject
        FROM paper p
        JOIN department d ON p.department_id = d.department_id
        LEFT JOIN subject s ON p.subject_id = s.subject_id
        WHERE 1=1 ";
$params = [];
$types = '';
if ($dept !== null) { $sql .= " AND p.department_id = ?"; $params[] = $dept; $types .= 'i'; }
if ($year !== null) { $sql .= " AND p.year = ?"; $params[] = $year; $types .= 'i'; }
if ($q !== '') { $sql .= " AND p.title LIKE ?"; $params[] = '%' . $q . '%'; $types .= 's'; }
$sql .= " ORDER BY p.year DESC, p.uploaded_on DESC LIMIT 500";

$stmt = $mysqli->prepare($sql);
if ($types !== '') $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
$rows = [];
while ($r = $res->fetch_assoc()) $rows[] = $r;
$stmt->close();
echo json_encode($rows);
