<?php
header('Content-Type: application/json');
// Use the same central config as other endpoints for consistency
require_once __DIR__ . '/config.php';

$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) {
    echo json_encode(["success" => false, "error" => "db_connect_failed", "detail" => sqlsrv_errors()]);
    exit;
}

$sql = "EXEC dbo.sp_ListBikes";
$stmt = sqlsrv_query($conn, $sql);
if ($stmt === false) {
    echo json_encode(["success" => false, "error" => "query_failed", "detail" => sqlsrv_errors()]);
    sqlsrv_close($conn);
    exit;
}

$bikes = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $bikes[] = [
        'id' => (int)$row['Bike_ID'],
        'model' => $row['bike_name_model'],
        'type' => $row['bike_type'],
        'availability' => isset($row['availability_status']) ? $row['availability_status'] : null,
        'hourly_rate' => isset($row['hourly_rate']) ? (float)$row['hourly_rate'] : 0.0
    ];
}

sqlsrv_close($conn);

echo json_encode(["success" => true, "bikes" => $bikes]);
?>
