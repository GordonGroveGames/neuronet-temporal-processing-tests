<?php
session_start();

function is_test_user_logged_in() {
    return isset($_SESSION['test_user_logged_in']) && $_SESSION['test_user_logged_in'] === true;
}

function require_test_user_login() {
    if (!is_test_user_logged_in()) {
        header('Location: login.php');
        exit();
    }
}

function get_test_user_info() {
    if (is_test_user_logged_in()) {
        return [
            'username' => $_SESSION['test_user'] ?? '',
            'email' => $_SESSION['test_user_email'] ?? ''
        ];
    }
    return null;
}
?>