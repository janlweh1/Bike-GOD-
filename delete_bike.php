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
if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'validation_failed', 'message' => 'invalid id']);
    sqlsrv_close($conn);
    exit;
}

$cascade = false;
if (isset($_POST['cascade'])) {
    $v = strtolower((string)$_POST['cascade']);
    $cascade = ($v === '1' || $v === 'true' || $v === 'yes');
}

$sql = $cascade ? 'EXEC dbo.sp_DeleteBikeCascade @BikeID = ?' : 'EXEC dbo.sp_DeleteBike @BikeID = ?';
$stmt = sqlsrv_query($conn, $sql, [$id]);
if ($stmt === false) {
    $detail = sqlsrv_errors();
    echo json_encode(['success' => false, 'error' => 'delete_failed', 'detail' => $detail]);
    sqlsrv_close($conn);
    exit;
}

sqlsrv_close($conn);
echo json_encode(['success' => true]);
?>
