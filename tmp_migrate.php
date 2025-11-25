<?php
require_once __DIR__ . '/db.php';

header('Content-Type: text/plain; charset=utf-8');
try {
    $cols = [
        // Add email as nullable to avoid conflicts on existing rows; make unique later if needed
        'email' => "ALTER TABLE student_user ADD COLUMN email VARCHAR(150) DEFAULT NULL AFTER username",
        'otp_code' => "ALTER TABLE student_user ADD COLUMN otp_code VARCHAR(10) DEFAULT NULL",
        'otp_expires_at' => "ALTER TABLE student_user ADD COLUMN otp_expires_at DATETIME DEFAULT NULL",
        'is_verified' => "ALTER TABLE student_user ADD COLUMN is_verified TINYINT(1) DEFAULT 0",
    ];

    foreach ($cols as $col => $sql) {
        $res = $mysqli->query("SHOW COLUMNS FROM student_user LIKE '" . $mysqli->real_escape_string($col) . "'");
        if (!$res) {
            echo "ERROR checking column {$col}: " . $mysqli->error . "\n";
            continue;
        }
        if ($res->num_rows === 0) {
            if ($mysqli->query($sql)) {
                echo "Added column {$col}\n";
            } else {
                echo "Failed to add {$col}: " . $mysqli->error . "\n";
            }
        } else {
            echo "Column {$col} already exists\n";
        }
    }
} catch (Throwable $e) {
    echo 'EXCEPTION: ' . $e->getMessage() . "\n";
}

?>
