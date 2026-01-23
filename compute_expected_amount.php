<?php
session_start();
header('Content-Type: application/json');

// Restrict to admin users
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
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

$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!is_array($data)) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON payload']);
    closeConnection($conn);
    exit();
}

$rentalId = (int)($data['rentalId'] ?? 0);
$paymentDate = trim((string)($data['paymentDate'] ?? ''));
$paymentTime = trim((string)($data['paymentTime'] ?? ''));

if ($rentalId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid rental id']);
    closeConnection($conn);
    exit();
}

try {
    // Get rental start, planned end (respects extensions), rate, and status via stored procedure
    $stmt = sqlsrv_query($conn, 'EXEC dbo.sp_GetRentalForExpectedAmount @RentalID = ?', [$rentalId]);
    if ($stmt === false || !($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
        echo json_encode(['success' => false, 'message' => 'Rental not found']);
        closeConnection($conn);
        exit();
    }

    $startDate = $row['rental_date'];
    $startTime = $row['rental_time'];
    $plannedReturnDate = $row['return_date'] ?? null;
    $plannedReturnTime = $row['return_time'] ?? null;
    $statusDb = strtolower((string)($row['status'] ?? ''));
    $rate = isset($row['hourly_rate']) ? (float)$row['hourly_rate'] : 0.0;

    // For cancelled rentals (including free cancellations within the
    // 5-minute window), no payment is required. Return zero expected
    // amount so the admin panel clearly shows no charge.
    if ($statusDb === 'cancelled') {
        echo json_encode([
            'success' => true,
            'expected' => 0.0,
            'rate' => $rate,
            'hours' => 0,
            'usedEnd' => null
        ]);
        closeConnection($conn);
        exit();
    }

    $startDt = null;
    if ($startDate instanceof DateTime) {
        $startDt = new DateTime($startDate->format('Y-m-d') . ' ' . ($startTime instanceof DateTime ? $startTime->format('H:i:s') : '00:00:00'));
    }

    // Planned end from Rentals (supports extensions)
    $plannedEnd = null;
    if ($plannedReturnDate instanceof DateTime) {
        $plannedEnd = new DateTime($plannedReturnDate->format('Y-m-d') . ' ' . (
            $plannedReturnTime instanceof DateTime ? $plannedReturnTime->format('H:i:s') : ($startTime instanceof DateTime ? $startTime->format('H:i:s') : '00:00:00')
        ));
    }

    // Determine end time: prefer Returns (via stored procedure), else payment date/time or now.
    // If the rental has not been returned yet and the payment time is
    // before the planned end, bill up to the planned end to reflect
    // any extensions (same logic as sp_RecordPayment).
    $endDt = null;
    $hasActualReturn = false;
    $stmtR = sqlsrv_query($conn, 'EXEC dbo.sp_GetLatestReturnForRental @RentalID = ?', [$rentalId]);
    if ($stmtR && ($rowR = sqlsrv_fetch_array($stmtR, SQLSRV_FETCH_ASSOC))) {
        $rd = $rowR['return_date'];
        $rt = $rowR['return_time'];
        if ($rd instanceof DateTime) {
            $endDt = new DateTime($rd->format('Y-m-d') . ' ' . ($rt instanceof DateTime ? $rt->format('H:i:s') : '00:00:00'));
            $hasActualReturn = true;
        }
    }

    if (!$hasActualReturn) {
        if ($paymentDate !== '' && $paymentTime !== '') {
            $endDt = DateTime::createFromFormat('Y-m-d H:i', $paymentDate . ' ' . $paymentTime);
        }
        if (!$endDt) {
            $endDt = new DateTime('now');
        }

        if ($plannedEnd && $endDt < $plannedEnd) {
            $endDt = $plannedEnd;
        }
    }

    $hours = 1;
    if ($startDt && $endDt) {
        $diffHrs = (int)round(($endDt->getTimestamp() - $startDt->getTimestamp()) / 3600);
        $hours = max(1, $diffHrs);
    }

    $expected = round($rate * $hours, 2);

    echo json_encode([
        'success' => true,
        'expected' => $expected,
        'rate' => $rate,
        'hours' => $hours,
        'usedEnd' => $endDt ? $endDt->format('c') : null
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error computing expected amount']);
}

closeConnection($conn);
?>
