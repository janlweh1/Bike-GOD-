<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) {
    echo json_encode(["success" => false, "error" => "db_connect_failed", "detail" => sqlsrv_errors()]);
    exit;
}

$stmt = sqlsrv_query($conn, "EXEC dbo.sp_GetPricingSettings");
if ($stmt === false) {
    echo json_encode(["success" => false, "error" => "query_failed", "detail" => sqlsrv_errors()]);
    sqlsrv_close($conn);
    exit;
}
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
sqlsrv_close($conn);
if (!$row) {
    echo json_encode(["success" => false, "error" => "not_found"]);
    exit;
}

echo json_encode([
    "success" => true,
    "charges" => [
        "late_fee" => (float)$row['LateFeePerDay'],
        "damage_fee" => (float)$row['DamageFeeMin'],
        "deposit" => (float)$row['SecurityDeposit'],
        "tax_inclusive" => (bool)$row['TaxInclusive'],
        "updated_at" => isset($row['UpdatedAt']) ? ($row['UpdatedAt'] instanceof DateTime ? $row['UpdatedAt']->format('c') : (string)$row['UpdatedAt']) : null
    ]
]);
?>
