<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) {
    echo json_encode(["success" => false, "error" => "db_connect_failed", "detail" => sqlsrv_errors()]);
    exit;
}

session_start();
$adminId = isset($_SESSION['admin_id']) ? intval($_SESSION['admin_id']) : null;

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) { $input = $_POST; }

$name = isset($input['name']) ? trim($input['name']) : '';
$address = isset($input['address']) ? trim($input['address']) : '';
$phone = isset($input['phone']) ? trim($input['phone']) : '';
$email = isset($input['email']) ? trim($input['email']) : '';
$website = isset($input['website']) ? trim($input['website']) : '';
$tin = isset($input['tin']) ? trim($input['tin']) : '';

$hours = isset($input['hours']) && is_array($input['hours']) ? $input['hours'] : [];
$wFrom = isset($hours['weekdays']['from']) ? $hours['weekdays']['from'] : null;
$wTo   = isset($hours['weekdays']['to'])   ? $hours['weekdays']['to']   : null;
$sFrom = isset($hours['saturday']['from']) ? $hours['saturday']['from'] : null;
$sTo   = isset($hours['saturday']['to'])   ? $hours['saturday']['to']   : null;
$suFrom= isset($hours['sunday']['from'])   ? $hours['sunday']['from']   : null;
$suTo  = isset($hours['sunday']['to'])     ? $hours['sunday']['to']     : null;

if ($name === '') {
    echo json_encode(["success" => false, "error" => "invalid_input", "detail" => "Business name required"]);
    sqlsrv_close($conn);
    exit;
}

$sql = "EXEC dbo.sp_UpdateBusinessInfo @BusinessName=?, @Address=?, @Phone=?, @Email=?, @Website=?, @TIN=?, @WeekdaysOpen=?, @WeekdaysClose=?, @SaturdayOpen=?, @SaturdayClose=?, @SundayOpen=?, @SundayClose=?, @UpdatedBy=?";
$params = [$name, $address, $phone, $email, $website, $tin, $wFrom, $wTo, $sFrom, $sTo, $suFrom, $suTo, $adminId];
$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) {
    echo json_encode(["success" => false, "error" => "update_failed", "detail" => sqlsrv_errors()]);
    sqlsrv_close($conn);
    exit;
}

sqlsrv_close($conn);
echo json_encode(["success" => true]);
?>
