<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) {
    echo json_encode(["success" => false, "error" => "db_connect_failed", "detail" => sqlsrv_errors()]);
    exit;
}

session_start();
// Prefer the logged-in admin session id
$adminId = 1;
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin' && isset($_SESSION['user_id'])) {
    $adminId = intval($_SESSION['user_id']);
} elseif (isset($_SESSION['admin_id'])) {
    $adminId = intval($_SESSION['admin_id']);
}

$sql = "EXEC sp_GetAdminProfile @AdminID=?";
$params = [$adminId];
$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) {
    echo json_encode(["success" => false, "error" => "query_failed"]);
    exit;
}
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if (!$row) {
    echo json_encode(["success" => false, "error" => "not_found"]);
    exit;
}

echo json_encode([
    "success" => true,
    "admin" => [
        "id" => $row['Admin_ID'],
        "username" => $row['username'],
        "full_name" => $row['full_name'],
        "role" => $row['role'],
        // Prefer DB photo_url; fallback to uploads directory
        "photo_url" => (function() use ($adminId, $row) {
            if (isset($row['photo_url']) && $row['photo_url']) {
                return (string)$row['photo_url'];
            }
            $base = __DIR__ . '/uploads';
            $candidates = glob($base . '/admin_' . $adminId . '.*');
            if ($candidates && count($candidates) > 0) {
                $file = basename($candidates[0]);
                return 'uploads/' . $file;
            }
            return null;
        })()
    ]
]);
?>
