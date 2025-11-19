<?php
// student_auth.php - session helpers for student users
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';

function is_student_logged_in() {
    return !empty($_SESSION['student_logged_in']) && !empty($_SESSION['student_username']);
}

function ensure_student() {
    if (!is_student_logged_in()) {
        $redirect = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/student_dashboard.php';
        header('Location: student_login.php?redirect=' . urlencode($redirect));
        exit;
    }
}

function login_student_session($username, $student_id = null) {
    session_regenerate_id(true);
    $_SESSION['student_logged_in'] = true;
    $_SESSION['student_username'] = $username;
    if ($student_id !== null) $_SESSION['student_id'] = $student_id;
}

function logout_student_session() {
    $_SESSION = array_diff_key($_SESSION, array_flip(['admin_logged_in','admin_username']));
    // Remove student keys
    unset($_SESSION['student_logged_in'], $_SESSION['student_username'], $_SESSION['student_id']);
}

?>
