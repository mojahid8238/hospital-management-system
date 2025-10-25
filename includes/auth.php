<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function is_doctor() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'doctor';
}

function is_patient() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'patient';
}

function redirect_if_not_logged_in() {
    if (!is_logged_in()) {
        header("Location: /hospital-management-system/auth/login.php");
        exit();
    }
}

function redirect_if_not_admin() {
    if (!is_admin()) {
        header("Location: /hospital-management-system/index.php"); // Redirect to a general page or error page
        exit();
    }
}

function redirect_if_not_doctor() {
    if (!is_doctor()) {
        header("Location: /hospital-management-system/index.php");
        exit();
    }
}

function redirect_if_not_patient() {
    if (!is_patient()) {
        header("Location: /hospital-management-system/index.php");
        exit();
    }
}
?>