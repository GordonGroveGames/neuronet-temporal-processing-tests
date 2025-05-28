<?php
require_once __DIR__ . '/../private/admin/includes/auth.php';

// Log the user out
logout();

// Redirect to login page
header('Location: login.php');
exit();
