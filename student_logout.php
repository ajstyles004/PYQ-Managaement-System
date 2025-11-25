<?php
require_once 'student_auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();
logout_student_session();
header('Location: choose_role.php');
exit;
