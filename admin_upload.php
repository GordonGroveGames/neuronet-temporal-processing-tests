<?php
require_once __DIR__ . '/admin_session.php';
require_admin_login();

header('Content-Type: application/json');

$uploadDir = __DIR__ . '/assets/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$response = ['success' => false];
try {
    $fields = ['left_image', 'center_image', 'right_image', 'left_sound', 'center_sound', 'right_sound'];
    $saved = [];
    foreach ($fields as $field) {
        if (!empty($_FILES[$field]['name'])) {
            $ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
            $safeName = uniqid($field . '_', true) . '.' . $ext;
            $target = $uploadDir . $safeName;
            if (move_uploaded_file($_FILES[$field]['tmp_name'], $target)) {
                $saved[$field] = 'assets/uploads/' . $safeName;
            } else {
                throw new Exception('Failed to upload ' . $field);
            }
        }
    }
    $response['success'] = true;
    $response['message'] = 'Files uploaded successfully.';
    $response['files'] = $saved;
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}
echo json_encode($response);
