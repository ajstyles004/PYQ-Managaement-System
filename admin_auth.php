<?php
// admin_auth.php - simple admin session helpers
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';

function is_admin_logged_in() {
    return !empty($_SESSION['admin_logged_in']) && !empty($_SESSION['admin_username']);
}

function ensure_admin() {
    if (!is_admin_logged_in()) {
        // redirect to login and preserve requested page
        $redirect = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/manage.php';
        header('Location: admin_login.php?redirect=' . urlencode($redirect));
        exit;
    }
}

function login_admin_session($username) {
    // Regenerate session id to avoid fixation
    session_regenerate_id(true);
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_username'] = $username;
}

function logout_admin_session() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'], $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

?>
