<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'unauthorized']);
    exit;
}

$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) {
    echo json_encode(['success' => false, 'error' => 'db_connect_failed', 'detail' => sqlsrv_errors()]);
    exit;
}

$model = isset($_POST['model']) ? trim($_POST['model']) : '';
$type = isset($_POST['type']) ? trim($_POST['type']) : '';
$status = isset($_POST['status']) ? trim($_POST['status']) : '';
$rate = isset($_POST['rate']) ? $_POST['rate'] : null;
// Optional condition support (Excellent|Good)
$condition = isset($_POST['condition']) ? trim($_POST['condition']) : '';

if ($model === '' || $type === '' || $status === '' || $rate === null || $rate === '') {
    echo json_encode(['success' => false, 'error' => 'validation_failed', 'message' => 'model, type, status, rate are required']);
    sqlsrv_close($conn);
    exit;
}

// Normalize inputs
$typeMap = [
    'city' => 'City Bike',
    'mountain' => 'Mountain Bike',
    'electric' => 'Electric Bike',
    'kids' => 'Kids Bike',
    'premium' => 'Road Bike',
];
$lcType = strtolower($type);
$dbType = $typeMap[$lcType] ?? $type; // pass-through if already proper text

$statusMap = [
    'available' => 'Available',
    'rented' => 'Rented',
    'maintenance' => 'Maintenance',
];
$dbStatus = $statusMap[strtolower($status)] ?? $status;

// Normalize condition
$condMap = [
    'excellent' => 'Excellent',
    'good' => 'Good',
];
$dbCondition = $condMap[strtolower($condition)] ?? null; // null means not provided

$rateVal = floatval($rate);
if (!is_numeric($rate) || $rateVal < 0) {
    echo json_encode(['success' => false, 'error' => 'validation_failed', 'message' => 'invalid rate']);
    sqlsrv_close($conn);
    exit;
}

$adminId = intval($_SESSION['user_id']);

$sql = "EXEC dbo.sp_AddBike @AdminID = ?, @Model = ?, @Type = ?, @Status = ?, @Rate = ?";
$params = [$adminId, $model, $dbType, $dbStatus, $rateVal];

$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) {
    echo json_encode(['success' => false, 'error' => 'insert_failed', 'detail' => sqlsrv_errors()]);
    sqlsrv_close($conn);
    exit;
}

$insertedId = null;
if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $insertedId = isset($row['Bike_ID']) ? (int)$row['Bike_ID'] : null;
}

// If condition provided and the column exists, update it
if ($insertedId && $dbCondition !== null) {
    $sqlCond = "IF COL_LENGTH('dbo.Bike','bike_condition') IS NOT NULL \n                 UPDATE dbo.Bike SET bike_condition = ? WHERE Bike_ID = ?";
    $stmtCond = sqlsrv_query($conn, $sqlCond, [$dbCondition, $insertedId]);
    // best-effort; ignore failure here to maintain compatibility when column isn't present
}

sqlsrv_close($conn);

echo json_encode(['success' => true, 'id' => $insertedId]);
?>
