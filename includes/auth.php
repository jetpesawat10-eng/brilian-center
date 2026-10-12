<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . basePath() . 'index.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . basePath() . 'user/dashboard.php');
        exit;
    }
}

function requireUser() {
    requireLogin();
    if (isAdmin()) {
        header('Location: ' . basePath() . 'admin/dashboard.php');
        exit;
    }
}

// Menentukan path relatif ke root project, agar redirect benar
// dipanggil dari folder admin/ atau user/ maupun root
function basePath() {
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    if (basename($dir) === 'admin' || basename($dir) === 'user') {
        return '../';
    }
    return '';
}

function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}
