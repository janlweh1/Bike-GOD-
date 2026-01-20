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

// Insert bike first to obtain ID
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

// Handle optional photo upload after insert
$photoUrl = null;
$photoError = null;
if ($insertedId && isset($_FILES['photo']) && is_array($_FILES['photo'])) {
    $file = $_FILES['photo'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $maxBytes = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxBytes) {
            $photoError = 'Image exceeds 5MB limit';
        } else {
            $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
            $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : ($file['type'] ?? '');
            if ($finfo) finfo_close($finfo);
            $allowed = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/gif'  => 'gif',
                'image/webp' => 'webp',
                'image/avif' => 'avif',
            ];
            if (!isset($allowed[$mime])) {
                $photoError = 'Unsupported image type';
            } else {
                $ext = $allowed[$mime];
                $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0775, true);
                }
                if (is_dir($uploadDir) && is_writable($uploadDir)) {
                    // Remove older files for this bike id (any extension)
                    foreach (['jpg','jpeg','png','gif','webp','avif'] as $e) {
                        $old = $uploadDir . DIRECTORY_SEPARATOR . 'bike_' . $insertedId . '.' . $e;
                        if (file_exists($old)) { @unlink($old); }
                    }
                    $destFs = $uploadDir . DIRECTORY_SEPARATOR . 'bike_' . $insertedId . '.' . $ext;
                    if (move_uploaded_file($file['tmp_name'], $destFs)) {
                        // Build web path relative to this app root
                        $photoUrl = 'uploads/' . 'bike_' . $insertedId . '.' . $ext;
                        // Persist to DB column if available
                        $sqlPhoto = "IF COL_LENGTH('dbo.Bike','photo_url') IS NOT NULL UPDATE dbo.Bike SET photo_url = ? WHERE Bike_ID = ?";
                        sqlsrv_query($conn, $sqlPhoto, [$photoUrl, $insertedId]);
                    } else {
                        $photoError = 'Failed to save uploaded image';
                    }
                } else {
                    $photoError = 'Uploads directory not writable';
                }
            }
        }
    } elseif ($file['error'] !== UPLOAD_ERR_NO_FILE) {
        $photoError = 'Upload failed (code ' . $file['error'] . ')';
    }
}

sqlsrv_close($conn);

$response = ['success' => true, 'id' => $insertedId];
if ($photoUrl) $response['photo_url'] = $photoUrl;
if ($photoError) $response['photo_error'] = $photoError;
echo json_encode($response);
?>
