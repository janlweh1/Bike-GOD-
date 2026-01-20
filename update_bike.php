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
// Optional condition field
$condition = isset($_POST['condition']) ? trim($_POST['condition']) : '';

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
// Normalize condition if provided
$condMap = [
    'excellent' => 'Excellent',
    'good' => 'Good',
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

// Determine if a photo upload is provided
$hasPhoto = isset($_FILES['photo']) && is_array($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE;

if (empty($fields) && $condition === '' && !$hasPhoto) {
    echo json_encode(['success' => false, 'error' => 'nothing_to_update']);
    sqlsrv_close($conn);
    exit;
}

// Call sp_UpdateBike; pass NULL for fields not being updated
$modelParam = in_array('bike_name_model = ?', $fields) ? $params[array_search('bike_name_model = ?', $fields)] : null;
$typeParam = in_array('bike_type = ?', $fields) ? ($params[array_search('bike_type = ?', $fields)]) : null;
$statusParam = in_array('availability_status = ?', $fields) ? ($params[array_search('availability_status = ?', $fields)]) : null;
$rateParam = in_array('hourly_rate = ?', $fields) ? ($params[array_search('hourly_rate = ?', $fields)]) : null;

// Execute update only if any of the standard fields were provided
if (!empty($fields)) {
    $sql = 'EXEC dbo.sp_UpdateBike @BikeID = ?, @Model = ?, @Type = ?, @Status = ?, @Rate = ?';
    $stmt = sqlsrv_query($conn, $sql, [$id, $modelParam, $typeParam, $statusParam, $rateParam]);
    if ($stmt === false) {
        // Fallback: perform a direct UPDATE when the procedure is missing or fails
        $directSql = 'UPDATE dbo.Bike SET ' . implode(', ', $fields) . ' WHERE Bike_ID = ?';
        $directParams = $params; // built above in same order as $fields
        $directParams[] = $id;
        $stmt2 = sqlsrv_query($conn, $directSql, $directParams);
        if ($stmt2 === false) {
            echo json_encode(['success' => false, 'error' => 'update_failed', 'detail' => sqlsrv_errors()]);
            sqlsrv_close($conn);
            exit;
        }
    }
}

// If a condition was provided, update it separately when column exists
if ($condition !== '') {
    $dbCondition = $condMap[strtolower($condition)] ?? $condition;
    $sqlCond = "IF COL_LENGTH('dbo.Bike','bike_condition') IS NOT NULL \n                 UPDATE dbo.Bike SET bike_condition = ? WHERE Bike_ID = ?";
    $stmtCond = sqlsrv_query($conn, $sqlCond, [$dbCondition, $id]);
    // Ignore failure to retain compatibility if column not present
}

// If a photo file is provided, validate and save
$photoUrl = null;
$photoError = null;
if ($hasPhoto) {
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
                    foreach (['jpg','jpeg','png','gif','webp','avif'] as $e) {
                        $old = $uploadDir . DIRECTORY_SEPARATOR . 'bike_' . $id . '.' . $e;
                        if (file_exists($old)) { @unlink($old); }
                    }
                    $destFs = $uploadDir . DIRECTORY_SEPARATOR . 'bike_' . $id . '.' . $ext;
                    if (move_uploaded_file($file['tmp_name'], $destFs)) {
                        $photoUrl = 'uploads/' . 'bike_' . $id . '.' . $ext;
                        $sqlPhoto = "IF COL_LENGTH('dbo.Bike','photo_url') IS NOT NULL UPDATE dbo.Bike SET photo_url = ? WHERE Bike_ID = ?";
                        sqlsrv_query($conn, $sqlPhoto, [$photoUrl, $id]);
                    } else {
                        $photoError = 'Failed to save uploaded image';
                    }
                } else {
                    $photoError = 'Uploads directory not writable';
                }
            }
        }
    } else {
        $photoError = 'Upload failed (code ' . $file['error'] . ')';
    }
}

sqlsrv_close($conn);
$resp = ['success' => true];
if ($photoUrl) $resp['photo_url'] = $photoUrl;
if ($photoError) $resp['photo_error'] = $photoError;
echo json_encode($resp);
?>
