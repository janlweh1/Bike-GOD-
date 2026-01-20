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

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$model = isset($_POST['model']) ? trim($_POST['model']) : '';
$type = isset($_POST['type']) ? trim($_POST['type']) : '';
$status = isset($_POST['status']) ? trim($_POST['status']) : '';
$rate = isset($_POST['rate']) ? $_POST['rate'] : null;

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'validation_failed', 'message' => 'invalid id']);
    sqlsrv_close($conn);
    exit;
}

// Only update fields provided
$fields = [];
$params = [];

// Normalize if provided
$typeMap = [
    'city' => 'City Bike',
    'mountain' => 'Mountain Bike',
    'electric' => 'Electric Bike',
    'kids' => 'Kids Bike',
    'premium' => 'Road Bike',
];
$statusMap = [
    'available' => 'Available',
    'rented' => 'Rented',
    'maintenance' => 'Maintenance',
];

if ($model !== '') { $fields[] = 'bike_name_model = ?'; $params[] = $model; }
if ($type !== '') {
    $lcType = strtolower($type);
    $dbType = $typeMap[$lcType] ?? $type;
    $fields[] = 'bike_type = ?';
    $params[] = $dbType;
}
if ($status !== '') {
    $dbStatus = $statusMap[strtolower($status)] ?? $status;
    $fields[] = 'availability_status = ?';
    $params[] = $dbStatus;
}
if ($rate !== null && $rate !== '') {
    $rateVal = floatval($rate);
    if (!is_numeric($rate) || $rateVal < 0) {
        echo json_encode(['success' => false, 'error' => 'validation_failed', 'message' => 'invalid rate']);
        sqlsrv_close($conn);
        exit;
    }
    $fields[] = 'hourly_rate = ?';
    $params[] = $rateVal;
}

if (empty($fields)) {
    echo json_encode(['success' => false, 'error' => 'nothing_to_update']);
    sqlsrv_close($conn);
    exit;
}

// Call sp_UpdateBike; pass NULL for fields not being updated
$modelParam = in_array('bike_name_model = ?', $fields) ? $params[array_search('bike_name_model = ?', $fields)] : null;
$typeParam = in_array('bike_type = ?', $fields) ? ($params[array_search('bike_type = ?', $fields)]) : null;
$statusParam = in_array('availability_status = ?', $fields) ? ($params[array_search('availability_status = ?', $fields)]) : null;
$rateParam = in_array('hourly_rate = ?', $fields) ? ($params[array_search('hourly_rate = ?', $fields)]) : null;

$sql = 'EXEC dbo.sp_UpdateBike @BikeID = ?, @Model = ?, @Type = ?, @Status = ?, @Rate = ?';
$stmt = sqlsrv_query($conn, $sql, [$id, $modelParam, $typeParam, $statusParam, $rateParam]);
if ($stmt === false) {
    echo json_encode(['success' => false, 'error' => 'update_failed', 'detail' => sqlsrv_errors()]);
    sqlsrv_close($conn);
    exit;
}

sqlsrv_close($conn);
echo json_encode(['success' => true]);
?>
