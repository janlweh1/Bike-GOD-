<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) {
    echo json_encode(["success" => false, "error" => "db_connect_failed", "detail" => sqlsrv_errors()]);
    exit;
}

session_start();
$adminId = isset($_SESSION['admin_id']) ? intval($_SESSION['admin_id']) : 1;

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
        "role" => $row['role']
    ]
]);
?>
