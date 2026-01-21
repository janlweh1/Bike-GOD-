<?php
// Remove member profile photo
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'member' || !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'unauthorized']);
    exit();
}

$memberId = intval($_SESSION['user_id']);
$uploads = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
$deleted = false;
foreach (glob($uploads . DIRECTORY_SEPARATOR . 'member_' . $memberId . '.*') as $old) {
    if (@unlink($old)) $deleted = true;
}

require_once __DIR__ . '/db_config.php';
$conn = getConnection();
if ($conn) {
    $sql = "EXEC sp_UpdateMemberPhotoUrl @MemberID=?, @PhotoUrl=?";
    $params = [$memberId, null];
    @sqlsrv_query($conn, $sql, $params);
    closeConnection($conn);
}

echo json_encode(['success' => true, 'deleted' => $deleted]);
