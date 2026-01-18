<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) {
    $err = sqlsrv_errors();
    echo json_encode(["success" => false, "error" => "db_connect_failed", "detail" => $err]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) { $input = $_POST; }
if (!isset($input['rates']) || !is_array($input['rates'])) {
    echo json_encode(["success" => false, "error" => "invalid_input", "detail" => $input]);
    exit;
}

$ok = true;
foreach ($input['rates'] as $type => $rate) {
    $sql = "EXEC sp_UpdateRateByType @BikeType=?, @Rate=?";
    $params = [$type, (float)$rate];
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        $ok = false;
        $err = sqlsrv_errors();
        echo json_encode(["success" => false, "error" => "update_failed", "type" => $type, "rate" => (float)$rate, "detail" => $err]);
        exit;
    }
}

echo json_encode(["success" => $ok]);
?>
