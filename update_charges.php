<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) {
    echo json_encode(["success" => false, "error" => "db_connect_failed", "detail" => sqlsrv_errors()]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) { $input = $_POST; }
$late = isset($input['late_fee']) ? (float)$input['late_fee'] : null;
$damage = isset($input['damage_fee']) ? (float)$input['damage_fee'] : null;
$deposit = isset($input['deposit']) ? (float)$input['deposit'] : null;
$tax = isset($input['tax_inclusive']) ? (bool)$input['tax_inclusive'] : null;

if ($late === null || $damage === null || $deposit === null || $tax === null) {
    echo json_encode(["success" => false, "error" => "invalid_input", "detail" => $input]);
    sqlsrv_close($conn);
    exit;
}

$sql = "EXEC dbo.sp_UpdatePricingSettings @LateFeePerDay=?, @DamageFeeMin=?, @SecurityDeposit=?, @TaxInclusive=?";
$params = [$late, $damage, $deposit, $tax ? 1 : 0];
$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) {
    echo json_encode(["success" => false, "error" => "update_failed", "detail" => sqlsrv_errors()]);
    sqlsrv_close($conn);
    exit;
}

sqlsrv_close($conn);

echo json_encode(["success" => true]);
?>
