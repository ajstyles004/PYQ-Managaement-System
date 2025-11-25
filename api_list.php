<?php
// api_list.php  (fixed - returns department and subject keys expected by JS)
require 'db.php';
header('Content-Type: application/json; charset=utf-8');

$dept = isset($_GET['department']) && $_GET['department'] !== '' ? intval($_GET['department']) : null;
$study_year = isset($_GET['study_year']) && $_GET['study_year'] !== '' ? intval($_GET['study_year']) : null;
$semester = isset($_GET['semester']) && $_GET['semester'] !== '' ? intval($_GET['semester']) : null;
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

 $sql = "SELECT p.paper_id, p.title, p.year, p.filename, p.filepath,
               d.department_name AS department, s.subject_name AS subject, s.subject_code AS subject_code
        FROM paper p
        JOIN department d ON p.department_id = d.department_id
        LEFT JOIN subject s ON p.subject_id = s.subject_id
        WHERE 1=1 ";
$params = [];
$types = '';
$if_dept = false;
if ($dept !== null) { $sql .= " AND p.department_id = ?"; $params[] = $dept; $types .= 'i'; $if_dept = true; }
// Map study year (1-4) to semester ranges
if ($study_year !== null) {
        $startSem = ($study_year - 1) * 2 + 1;
        $endSem = $study_year * 2;
        $sql .= " AND p.semester BETWEEN ? AND ?"; $params[] = $startSem; $params[] = $endSem; $types .= 'ii';
}
if ($semester !== null) { $sql .= " AND p.semester = ?"; $params[] = $semester; $types .= 'i'; }
// Search by subject code
if ($q !== '') { $sql .= " AND s.subject_code LIKE ?"; $params[] = '%' . $q . '%'; $types .= 's'; }
$sql .= " ORDER BY p.year DESC, p.uploaded_on DESC LIMIT 500";

$stmt = $mysqli->prepare($sql);
if ($types !== '') $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
$rows = [];
while ($r = $res->fetch_assoc()) $rows[] = $r;
$stmt->close();
echo json_encode($rows);
