<?php
// Create a rental from member browse page
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
$bikeId = isset($_POST['bike_id']) ? (int)$_POST['bike_id'] : 0;
$durationHours = isset($_POST['duration_hours']) ? (int)$_POST['duration_hours'] : 0;
$pickupDate = isset($_POST['pickup_date']) ? $_POST['pickup_date'] : ''; // YYYY-MM-DD
$pickupTime = isset($_POST['pickup_time']) ? $_POST['pickup_time'] : ''; // HH:MM

if ($memberId <= 0 || $bikeId <= 0 || $durationHours <= 0 || !$pickupDate || !$pickupTime) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    closeConnection($conn);
    exit();
}

try {
    // Validate bike and get admin + rate via stored procedure
    $stmt = sqlsrv_query($conn, 'EXEC dbo.sp_GetBikeForRental @BikeID = ?', [$bikeId]);
    if ($stmt === false || !($bike = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
        echo json_encode(['success' => false, 'message' => 'Bike not found']);
        closeConnection($conn);
        exit();
    }

    $status = strtolower((string)($bike['availability_status'] ?? ''));
    if ($status !== 'available') {
        echo json_encode(['success' => false, 'message' => 'Bike not available']);
        closeConnection($conn);
        exit();
    }

    $adminId = (int)($bike['admin_id'] ?? 0);
    $rate = isset($bike['hourly_rate']) ? (float)$bike['hourly_rate'] : 0.0;

    // Build datetime objects
    $pickupDtStr = $pickupDate . ' ' . $pickupTime . ':00';
    $pickupDt = DateTime::createFromFormat('Y-m-d H:i:s', $pickupDtStr);
    if (!$pickupDt) { throw new Exception('Invalid pickup datetime'); }
    $endDt = clone $pickupDt;
    $endDt->modify('+' . $durationHours . ' hours');
    $plannedReturnDate = $endDt->format('Y-m-d');

    // Derive rental status: pending if scheduled in future, else active
    $now = new DateTime('now');
    $rentalStatus = ($pickupDt > $now) ? 'Pending' : 'Active';

    // Start a transaction
    sqlsrv_begin_transaction($conn);

    // Create rental and mark bike rented via stored procedure
    $ins = sqlsrv_query(
        $conn,
        'EXEC dbo.sp_CreateRental @MemberID = ?, @BikeID = ?, @AdminID = ?, @RentalDate = ?, @RentalTime = ?, @PlannedReturnDate = ?, @PlannedReturnTime = ?, @Status = ?',
        [
            $memberId,
            $bikeId,
            $adminId,
            $pickupDt->format('Y-m-d'),
            $pickupDt->format('H:i:s'),
            $plannedReturnDate,
            $endDt->format('H:i:s'),
            $rentalStatus
        ]
    );
    if ($ins === false) { throw new Exception('Failed to insert rental: ' . print_r(sqlsrv_errors(), true)); }

    $newId = null;
    if ($row = sqlsrv_fetch_array($ins, SQLSRV_FETCH_ASSOC)) {
        $newId = isset($row['Rental_ID']) ? (int)$row['Rental_ID'] : (int)array_values($row)[0];
    }
    if (!$newId) { throw new Exception('Failed to get rental id after insert'); }

    sqlsrv_commit($conn);

    echo json_encode([
        'success' => true,
        'rental_id' => $newId,
        'status' => $rentalStatus,
        'amount' => round($rate * $durationHours, 2)
    ]);

} catch (Exception $e) {
    if ($conn) { sqlsrv_rollback($conn); }
    $detail = $e->getMessage();
    $sqlDetail = sqlsrv_errors();
    echo json_encode(['success' => false, 'message' => 'Error creating rental', 'detail' => $detail, 'sqlsrv' => $sqlDetail]);
}

closeConnection($conn);
?>
