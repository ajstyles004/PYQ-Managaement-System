<?php
require_once 'admin_auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();
logout_admin_session();
header('Location: choose_role.php');
exit;
