<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) {
    echo json_encode(["success" => false, "error" => "db_connect_failed", "detail" => sqlsrv_errors()]);
    exit;
}

$sql = "EXEC sp_GetRatesByType";
$stmt = sqlsrv_query($conn, $sql);
if ($stmt === false) {
    echo json_encode(["success" => false, "error" => "query_failed"]);
    exit;
}
$rates = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $rates[$row['bike_type']] = (float)$row['rate'];
}

echo json_encode(["success" => true, "rates" => $rates]);
?>
