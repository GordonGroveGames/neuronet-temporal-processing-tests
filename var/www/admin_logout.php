<?php
require_once __DIR__ . '/admin_session.php';
admin_logout();
header('Location: admin_login.php');
exit();
