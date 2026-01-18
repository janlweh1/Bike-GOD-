<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) {
    echo json_encode([
        "success" => false,
        "stage" => "connect",
        "error" => "db_connect_failed",
        "detail" => sqlsrv_errors()
    ]);
    exit;
}

$checks = [];
// Simple SP existence check: try exec and catch failure
$procs = [
    'sp_GetRatesByType',
    'sp_UpdateRateByType',
    'sp_GetAdminProfile',
    'sp_UpdateAdminProfile',
    'sp_GetAdminAuthById',
    'sp_UpdateAdminPassword'
];
foreach ($procs as $p) {
    $stmt = sqlsrv_query($conn, "EXEC $p", []);
    if ($stmt === false) {
        $checks[$p] = ["ok" => false, "detail" => sqlsrv_errors()];
    } else {
        $checks[$p] = ["ok" => true];
    }
}

echo json_encode(["success" => true, "server" => $serverName, "db" => "BikeRental", "procedures" => $checks]);
?>
