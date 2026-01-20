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

// Determine if a row was actually deleted (procedures return RowsAffected)
$rowsAffected = null;
if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    if (isset($row['RowsAffected'])) {
        $rowsAffected = (int)$row['RowsAffected'];
    }
}

if ($rowsAffected === null) {
    // Back-compat: if procedure didn't return a row, assume success
    $rowsAffected = 1;
}

if ($rowsAffected > 0) {
    // Remove associated image file(s)
    $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
    if (is_dir($uploadDir)) {
        foreach (['jpg','jpeg','png','gif','webp','avif'] as $ext) {
            $path = $uploadDir . DIRECTORY_SEPARATOR . 'bike_' . $id . '.' . $ext;
            if (file_exists($path)) { @unlink($path); }
        }
    }
    sqlsrv_close($conn);
    echo json_encode(['success' => true]);
} else {
    sqlsrv_close($conn);
    echo json_encode(['success' => false, 'error' => 'not_deleted', 'message' => 'Bike could not be deleted']);
}
?>
