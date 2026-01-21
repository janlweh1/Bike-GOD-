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
$fullName = isset($input['full_name']) ? trim($input['full_name']) : '';
$username = isset($input['username']) ? trim($input['username']) : '';
$email = isset($input['email']) ? trim($input['email']) : null;

if ($fullName === '' || $username === '') {
    echo json_encode(["success" => false, "error" => "invalid_input"]);
    exit;
}

$sql = "EXEC sp_UpdateAdminProfile @AdminID=?, @Username=?, @FullName=?, @Email=?";
$params = [$adminId, $username, $fullName, $email];
$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) {
    echo json_encode(["success" => false, "error" => "update_failed", "detail" => sqlsrv_errors()]);
    exit;
}

echo json_encode(["success" => true]);
?>
