<?php
// Upload member profile photo, store under uploads/member_<id>.<ext>
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'member' || !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'unauthorized']);
    exit();
}

$memberId = intval($_SESSION['user_id']);

if (!isset($_FILES['photo'])) {
    echo json_encode(['success' => false, 'error' => 'no_file']);
    exit();
}

$file = $_FILES['photo'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'upload_error', 'code' => $file['error']]);
    exit();
}

if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'file_too_large']);
    exit();
}

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
if (!is_dir($uploads)) { @mkdir($uploads, 0775, true); }
if (!is_dir($uploads)) { echo json_encode(['success' => false, 'error' => 'uploads_dir_unavailable']); exit(); }

foreach (glob($uploads . DIRECTORY_SEPARATOR . 'member_' . $memberId . '.*') as $old) { @unlink($old); }
$target = $uploads . DIRECTORY_SEPARATOR . 'member_' . $memberId . '.' . $ext;
if (!move_uploaded_file($file['tmp_name'], $target)) {
    echo json_encode(['success' => false, 'error' => 'save_failed']);
    exit();
}

$url = 'uploads/member_' . $memberId . '.' . $ext;

require_once __DIR__ . '/db_config.php';
$conn = getConnection();
if ($conn) {
    $sql = "EXEC sp_UpdateMemberPhotoUrl @MemberID=?, @PhotoUrl=?";
    $params = [$memberId, $url];
    @sqlsrv_query($conn, $sql, $params);
    closeConnection($conn);
}

echo json_encode(['success' => true, 'url' => $url]);
