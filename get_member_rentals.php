<?php
session_start();
header('Content-Type: application/json');

// Must be logged in as member
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'member') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/db_config.php';
$conn = getConnection();
if ($conn === null) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$memberId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if ($memberId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid member session']);
    closeConnection($conn);
    exit();
}

function resolveBikePhoto($bikeId) {
    $uploadDirFs = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
    if (is_dir($uploadDirFs)) {
        foreach (['jpg','jpeg','png','gif','webp','avif'] as $ext) {
            $candidate = $uploadDirFs . DIRECTORY_SEPARATOR . 'bike_' . $bikeId . '.' . $ext;
            if (file_exists($candidate)) {
                return 'uploads/' . 'bike_' . $bikeId . '.' . $ext;
            }
        }
    }
    return null;
}

try {
    $sql = 'EXEC dbo.sp_GetMemberRentals @MemberID = ?';
    $stmt = sqlsrv_query($conn, $sql, [$memberId]);
    if ($stmt === false) { throw new Exception('Query failed'); }

    $items = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $rid = (int)$row['Rental_ID'];
        $pickupDate = $row['rental_date'] instanceof DateTime ? $row['rental_date']->format('Y-m-d') : null;
        $pickupTime = $row['rental_time'] instanceof DateTime ? $row['rental_time']->format('H:i') : null;
        $returnDate = $row['return_date'] instanceof DateTime ? $row['return_date']->format('Y-m-d') : null;
        $items[] = [
            'rentalId' => $rid,
            'status' => strtolower((string)($row['rental_status'] ?? '')),
            'pickupDate' => $pickupDate,
            'pickupTime' => $pickupTime,
            'returnDate' => $returnDate,
            'bikeModel' => (string)($row['bike_name_model'] ?? ''),
            'bikeType'  => (string)($row['bike_type'] ?? ''),
            'hourlyRate' => isset($row['hourly_rate']) ? (float)$row['hourly_rate'] : 0.0,
            'photo_url' => resolveBikePhoto((int)($row['Bike_ID'] ?? 0))
        ];
    }

    echo json_encode(['success' => true, 'rentals' => $items]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error loading member rentals']);
}

closeConnection($conn);
?>