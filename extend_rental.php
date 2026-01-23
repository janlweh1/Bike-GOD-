<?php
// Extend an existing rental's planned duration (member action)
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

// Accept either JSON or form-urlencoded
$raw = file_get_contents('php://input');
$data = null;
if ($raw) {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $data = $decoded;
    }
}
if (!is_array($data)) {
    $data = $_POST;
}

$rentalId = isset($data['rental_id']) ? (int)$data['rental_id'] : 0;
$additionalHours = isset($data['additional_hours']) ? (int)$data['additional_hours'] : 0;

if ($memberId <= 0 || $rentalId <= 0 || $additionalHours <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    closeConnection($conn);
    exit();
}

try {
    // Load the rental belonging to this member, including bike/admin and rate, via stored procedure
    $stmt = sqlsrv_query(
        $conn,
        'EXEC dbo.sp_GetRentalForExtend @RentalID = ?, @MemberID = ?',
        [$rentalId, $memberId]
    );
    if ($stmt === false || !($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
        echo json_encode(['success' => false, 'message' => 'Rental not found']);
        closeConnection($conn);
        exit();
    }

    $statusDb = strtolower((string)($row['status'] ?? ''));
    if ($statusDb === 'completed' || $statusDb === 'cancelled') {
        echo json_encode(['success' => false, 'message' => 'Completed or cancelled rentals cannot be extended']);
        closeConnection($conn);
        exit();
    }

    $rentalDate = $row['rental_date'];
    $rentalTime = $row['rental_time'];
    $returnDate = $row['return_date'];
    $returnTime = $row['return_time'] ?? null;
    $bikeId    = isset($row['bike_id']) ? (int)$row['bike_id'] : 0;
    $adminId   = isset($row['admin_id']) ? (int)$row['admin_id'] : 0;
    $hourlyRate = isset($row['hourly_rate']) ? (float)$row['hourly_rate'] : 0.0;

    if (!($rentalDate instanceof DateTime) || !($rentalTime instanceof DateTime)) {
        echo json_encode(['success' => false, 'message' => 'Invalid rental start date/time']);
        closeConnection($conn);
        exit();
    }

    // Build current planned end datetime
    if ($returnDate instanceof DateTime) {
        $currentEnd = new DateTime($returnDate->format('Y-m-d') . ' ' . (
            $returnTime instanceof DateTime ? $returnTime->format('H:i:s') : $rentalTime->format('H:i:s')
        ));
    } else {
        // Fallback: start + 1 hour if no planned end was saved
        $currentEnd = new DateTime($rentalDate->format('Y-m-d') . ' ' . $rentalTime->format('H:i:s'));
        $currentEnd->modify('+1 hour');
    }

    // New planned end after extension
    $newEnd = clone $currentEnd;
    $newEnd->modify('+' . $additionalHours . ' hours');

    // Compute old and new total duration in whole hours from start
    $startDt = new DateTime($rentalDate->format('Y-m-d') . ' ' . $rentalTime->format('H:i:s'));
    $diffOldSecs = max(0, $currentEnd->getTimestamp() - $startDt->getTimestamp());
    $oldDurationHours = (int)floor($diffOldSecs / 3600);
    if ($oldDurationHours < 1) { $oldDurationHours = 1; }

    $diffNewSecs = max(0, $newEnd->getTimestamp() - $startDt->getTimestamp());
    $newDurationHours = (int)floor($diffNewSecs / 3600);
    if ($newDurationHours < 1) { $newDurationHours = 1; }

    $extraHours = $newDurationHours - $oldDurationHours;
    if ($extraHours < 0) { $extraHours = 0; }

    // If there is already a completed payment for this rental, do NOT
    // modify the original rental. Instead, create a brand-new rental
    // that represents the extension so it has its own Rental_ID and can
    // carry a separate payment.
    $hasCompletedPayment = false;
    $payStmt = sqlsrv_query(
        $conn,
        "SELECT TOP 1 Payment_ID FROM Payments WHERE rental_id = ? AND status = 'completed'",
        [$rentalId]
    );
    if ($payStmt && sqlsrv_fetch_array($payStmt, SQLSRV_FETCH_ASSOC)) {
        $hasCompletedPayment = true;
    }

    $extensionRentalId = null;

    if ($hasCompletedPayment && $extraHours > 0 && $bikeId > 0) {
        // Create a new rental that starts at the previous planned end
        // and ends at the new extended end.
        $now = new DateTime('now');
        $extStatus = ($currentEnd > $now) ? 'Pending' : 'Active';

        $ins = sqlsrv_query(
            $conn,
            'EXEC dbo.sp_CreateRental @MemberID = ?, @BikeID = ?, @AdminID = ?, @RentalDate = ?, @RentalTime = ?, @PlannedReturnDate = ?, @PlannedReturnTime = ?, @Status = ?',
            [
                $memberId,
                $bikeId,
                $adminId,
                $currentEnd->format('Y-m-d'),
                $currentEnd->format('H:i:s'),
                $newEnd->format('Y-m-d'),
                $newEnd->format('H:i:s'),
                $extStatus
            ]
        );

        if ($ins === false) {
            echo json_encode(['success' => false, 'message' => 'Failed to create extension rental']);
            closeConnection($conn);
            exit();
        }

        if ($rowExt = sqlsrv_fetch_array($ins, SQLSRV_FETCH_ASSOC)) {
            $extensionRentalId = isset($rowExt['Rental_ID']) ? (int)$rowExt['Rental_ID'] : (int)array_values($rowExt)[0];
        }

        if (!$extensionRentalId) {
            echo json_encode(['success' => false, 'message' => 'Failed to get extension rental id']);
            closeConnection($conn);
            exit();
        }
    } else {
        // No completed payment yet: simply extend the original rental
        // in-place so that a single payment will cover the full time.
        $upd = sqlsrv_query(
            $conn,
            'EXEC dbo.sp_ExtendRental @RentalID = ?, @MemberID = ?, @NewReturnDate = ?, @NewReturnTime = ?',
            [
                $rentalId,
                $memberId,
                $newEnd->format('Y-m-d'),
                $newEnd->format('H:i:s')
            ]
        );

        if ($upd === false) {
            echo json_encode(['success' => false, 'message' => 'Failed to update rental']);
            closeConnection($conn);
            exit();
        }
    }

    echo json_encode([
        'success' => true,
        'rental_id' => $rentalId,
        'newReturnDate' => $newEnd->format('Y-m-d'),
        'newReturnTime' => $newEnd->format('H:i:s'),
        'newDurationHours' => $newDurationHours,
        'extension_rental_id' => $extensionRentalId
    ]);

} catch (Exception $e) {
    if ($conn) { sqlsrv_rollback($conn); }
    echo json_encode(['success' => false, 'message' => 'Error extending rental']);
}

closeConnection($conn);
?>
