<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) {
    echo json_encode(["success" => false, "error" => "db_connect_failed", "detail" => sqlsrv_errors()]);
    exit;
}

$stmt = sqlsrv_query($conn, "EXEC dbo.sp_GetBusinessInfo");
if ($stmt === false) {
    echo json_encode(["success" => false, "error" => "query_failed", "detail" => sqlsrv_errors()]);
    sqlsrv_close($conn);
    exit;
}
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
sqlsrv_close($conn);

if (!$row) {
    echo json_encode(["success" => true, "business" => null]);
    exit;
}

function fmtTime($t) {
    if (!$t) return null;
    if ($t instanceof DateTime) return $t->format('H:i');
    return (string)$t; // fallback
}

echo json_encode([
    "success" => true,
    "business" => [
        "name" => $row['BusinessName'],
        "address" => $row['Address'],
        "phone" => $row['Phone'],
        "email" => $row['Email'],
        "website" => $row['Website'],
        "tin" => $row['TIN'],
        "hours" => [
            "weekdays" => ["from" => fmtTime($row['WeekdaysOpen']), "to" => fmtTime($row['WeekdaysClose'])],
            "saturday" => ["from" => fmtTime($row['SaturdayOpen']), "to" => fmtTime($row['SaturdayClose'])],
            "sunday" => ["from" => fmtTime($row['SundayOpen']), "to" => fmtTime($row['SundayClose'])],
        ],
        "updated_at" => isset($row['UpdatedAt']) ? ($row['UpdatedAt'] instanceof DateTime ? $row['UpdatedAt']->format('c') : (string)$row['UpdatedAt']) : null,
    ]
]);
?>
