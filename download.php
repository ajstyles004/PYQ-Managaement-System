<?php
// download.php
require 'db.php';
if (!isset($_GET['id'])) { http_response_code(400); echo "Missing id"; exit; }
$id = intval($_GET['id']);
$stmt = $mysqli->prepare("SELECT filename, filepath FROM paper WHERE paper_id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) { http_response_code(404); echo "Not found"; exit; }
$row = $res->fetch_assoc();
$stmt->close();

$filepath = __DIR__ . '/' . $row['filepath'];
$filename = $row['filename'];
if (!file_exists($filepath)) { http_response_code(404); echo "File missing"; exit; }

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . basename($filename) . '"');
header('Content-Length: ' . filesize($filepath));
readfile($filepath);
exit;
?>