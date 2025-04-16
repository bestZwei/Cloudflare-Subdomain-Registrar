<?php
session_start();
require_once __DIR__ . '/includes/auth.php';

if (isset($_SESSION['user'])) {
    header('Location: views/dashboard.php');
    exit();
}

include 'views/login.php';
?> 