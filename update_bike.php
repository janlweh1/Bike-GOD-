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

// Determine if a photo upload is provided
$hasPhoto = isset($_FILES['photo']) && is_array($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE;

if ($model === '' && $type === '' && $status === '' && ($rate === null || $rate === '') && $condition === '' && !$hasPhoto) {
    echo json_encode(['success' => false, 'error' => 'nothing_to_update']);
    sqlsrv_close($conn);
    exit;
}

// Normalise values for procedure call
$modelParam = $model !== '' ? $model : null;
if ($type !== '') {
    $lcType = strtolower($type);
    $typeParam = $typeMap[$lcType] ?? $type;
} else {
    $typeParam = null;
}
if ($status !== '') {
    $statusParam = $statusMap[strtolower($status)] ?? $status;
} else {
    $statusParam = null;
}
if ($rate !== null && $rate !== '') {
    $rateVal = floatval($rate);
    if (!is_numeric($rate) || $rateVal < 0) {
        echo json_encode(['success' => false, 'error' => 'validation_failed', 'message' => 'invalid rate']);
        sqlsrv_close($conn);
        exit;
    }
    $rateParam = $rateVal;
} else {
    $rateParam = null;
}

$conditionParam = null;
if ($condition !== '') {
    $conditionParam = $condMap[strtolower($condition)] ?? $condition;
}

// Execute stored procedure for main bike fields (including optional condition)
if ($modelParam !== null || $typeParam !== null || $statusParam !== null || $rateParam !== null || $conditionParam !== null) {
    $sql = 'EXEC dbo.sp_UpdateBike @BikeID = ?, @Model = ?, @Type = ?, @Status = ?, @Rate = ?, @Condition = ?';
    $stmt = sqlsrv_query($conn, $sql, [$id, $modelParam, $typeParam, $statusParam, $rateParam, $conditionParam]);
    if ($stmt === false) {
        echo json_encode(['success' => false, 'error' => 'update_failed', 'detail' => sqlsrv_errors()]);
        sqlsrv_close($conn);
        exit;
    }
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
                        // Update photo via stored procedure
                        $sqlPhoto = "EXEC dbo.sp_UpdateBikePhoto @BikeID = ?, @PhotoUrl = ?";
                        sqlsrv_query($conn, $sqlPhoto, [$id, $photoUrl]);
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
