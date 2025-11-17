<?php
// db.php
$DB_HOST = '127.0.0.1';
$DB_NAME = 'question_bank';
$DB_USER = 'root';
$DB_PASS = ''; // change if root has password

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($mysqli->connect_error) {
    http_response_code(500);
    die('DB Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');
?>
