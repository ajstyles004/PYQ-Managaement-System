<?php
require_once __DIR__ . '/db.php';

header('Content-Type: text/plain; charset=utf-8');
try {
    $res = $mysqli->query("SHOW COLUMNS FROM student_user");
    if (!$res) {
        echo "ERROR: " . $mysqli->error . "\n";
        exit(1);
    }
    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }
    echo json_encode($rows, JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    echo 'EXCEPTION: ' . $e->getMessage() . "\n";
}

?>
