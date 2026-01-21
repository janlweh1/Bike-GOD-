<?php
// Upload admin profile photo, store under uploads/admin_<id>.<ext>
session_start();
header('Content-Type: application/json');

// Must be admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin' || !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'unauthorized']);
    exit();
}

$adminId = intval($_SESSION['user_id']);

if (!isset($_FILES['photo'])) {
    echo json_encode(['success' => false, 'error' => 'no_file']);
    exit();
}

$file = $_FILES['photo'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'upload_error', 'code' => $file['error']]);
    exit();
}

// Validate size (<= 5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'file_too_large']);
    exit();
}

// Validate mime type using finfo
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
$allowed = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp'
];
if (!isset($allowed[$mime])) {
    echo json_encode(['success' => false, 'error' => 'invalid_type', 'mime' => $mime]);
    exit();
}
$ext = $allowed[$mime];

$uploads = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
if (!is_dir($uploads)) {
    @mkdir($uploads, 0775, true);
}
if (!is_dir($uploads)) {
    echo json_encode(['success' => false, 'error' => 'uploads_dir_unavailable']);
    exit();
}

// Remove previous files for this admin
foreach (glob($uploads . DIRECTORY_SEPARATOR . 'admin_' . $adminId . '.*') as $old) {
    @unlink($old);
}

$target = $uploads . DIRECTORY_SEPARATOR . 'admin_' . $adminId . '.' . $ext;
if (!move_uploaded_file($file['tmp_name'], $target)) {
    echo json_encode(['success' => false, 'error' => 'save_failed']);
    exit();
}

// Return relative URL
$url = 'uploads/admin_' . $adminId . '.' . $ext;

// Persist URL in DB column if available
require_once __DIR__ . '/db_config.php';
$conn = getConnection();
if ($conn) {
    // Try stored procedure (created by AdminPhoto_Update.sql)
    $sql = "EXEC sp_UpdateAdminPhotoUrl @AdminID=?, @PhotoUrl=?";
    $params = [$adminId, $url];
    @sqlsrv_query($conn, $sql, $params);
    closeConnection($conn);
}

echo json_encode(['success' => true, 'url' => $url]);
