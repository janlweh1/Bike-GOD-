<?php
header('Content-Type: application/json');
// Use central config for DB connectivity consistency
require_once __DIR__ . '/config.php';

$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) {
    echo json_encode(["success" => false, "error" => "db_connect_failed", "detail" => sqlsrv_errors()]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) { $input = $_POST; }
$bikeId = isset($input['bike_id']) ? intval($input['bike_id']) : 0;
$rate = isset($input['rate']) ? floatval($input['rate']) : null;

if ($bikeId <= 0 || $rate === null) {
    echo json_encode(["success" => false, "error" => "invalid_input", "detail" => $input]);
    closeConnection($conn);
    exit;
}

$sql = "EXEC dbo.sp_UpdateBikeRate @BikeID=?, @Rate=?";
$params = [$bikeId, $rate];
$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) {
    echo json_encode(["success" => false, "error" => "update_failed", "detail" => sqlsrv_errors()]);
    sqlsrv_close($conn);
    exit;
}

sqlsrv_close($conn);

echo json_encode(["success" => true]);
?>
