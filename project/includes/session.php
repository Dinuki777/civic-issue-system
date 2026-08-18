<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function checkLogin() {
    if (!isLoggedIn()) {
        header("Location: " . BASE_URL . "auth/login.php");
        exit();
    }
}

function checkRole($allowed_roles) {
    checkLogin();
    if (!in_array($_SESSION['role_name'], $allowed_roles)) {
        header("Location: " . BASE_URL . "index.php?error=unauthorized");
        exit();
    }
}

function logout() {
    session_unset();
    session_destroy();
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}
?>
