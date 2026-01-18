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

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) { $input = $_POST; }
$current = isset($input['current_password']) ? $input['current_password'] : '';
$new = isset($input['new_password']) ? $input['new_password'] : '';

if ($current === '' || $new === '') {
    echo json_encode(["success" => false, "error" => "invalid_input"]);
    exit;
}

// Get current password
$sql = "EXEC sp_GetAdminAuthById @AdminID=?";
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
$dbPass = $row['password'];

$valid = false;
if ($dbPass === $current) { $valid = true; }
if (function_exists('password_verify')) {
    if (password_verify($current, $dbPass)) { $valid = true; }
}
if (!$valid) {
    echo json_encode(["success" => false, "error" => "wrong_current_password"]);
    exit;
}

// Store new password as-is (compatible with current admin login); hash if desired
$newToStore = $new;
if (function_exists('password_hash') && substr($dbPass, 0, 4) === '$2y$') {
    // If existing uses bcrypt, continue using bcrypt
    $newToStore = password_hash($new, PASSWORD_BCRYPT);
}

$sql = "EXEC sp_UpdateAdminPassword @AdminID=?, @Password=?";
$params = [$adminId, $newToStore];
$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) {
    echo json_encode(["success" => false, "error" => "update_failed"]);
    exit;
}

echo json_encode(["success" => true]);
?>
