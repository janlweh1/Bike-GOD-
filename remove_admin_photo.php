<?php
// Remove admin profile photo
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin' || !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'unauthorized']);
    exit();
}

$adminId = intval($_SESSION['user_id']);
$uploads = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
$deleted = false;
foreach (glob($uploads . DIRECTORY_SEPARATOR . 'admin_' . $adminId . '.*') as $old) {
    if (@unlink($old)) $deleted = true;
}

echo json_encode(['success' => true, 'deleted' => $deleted]);
