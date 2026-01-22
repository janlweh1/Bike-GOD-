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
    // Detect return_time column on Rentals
    $hasReturnTimeCol = false;
    $colStmt = sqlsrv_query($conn, "SELECT 1 AS X FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'Rentals' AND COLUMN_NAME = 'return_time'");
    if ($colStmt && sqlsrv_fetch_array($colStmt, SQLSRV_FETCH_ASSOC)) {
        $hasReturnTimeCol = true;
    }

    // Load the rental belonging to this member
    $sql = $hasReturnTimeCol
        ? "SELECT Rental_ID, rental_date, rental_time, return_date, return_time, status
           FROM Rentals
           WHERE Rental_ID = ? AND member_id = ?"
        : "SELECT Rental_ID, rental_date, rental_time, return_date, status
           FROM Rentals
           WHERE Rental_ID = ? AND member_id = ?";

    $stmt = sqlsrv_query($conn, $sql, [$rentalId, $memberId]);
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
    $returnTime = $hasReturnTimeCol ? ($row['return_time'] ?? null) : null;

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

    // Compute new total duration in whole hours from start
    $startDt = new DateTime($rentalDate->format('Y-m-d') . ' ' . $rentalTime->format('H:i:s'));
    $diffSecs = max(0, $newEnd->getTimestamp() - $startDt->getTimestamp());
    $newDurationHours = (int)floor($diffSecs / 3600);
    if ($newDurationHours < 1) { $newDurationHours = 1; }

    // Persist updated planned return date/time
    sqlsrv_begin_transaction($conn);

    if ($hasReturnTimeCol) {
        $upd = sqlsrv_query(
            $conn,
            'UPDATE Rentals SET return_date = CONVERT(date, ?), return_time = CONVERT(time, ?) WHERE Rental_ID = ? AND member_id = ?',
            [
                $newEnd->format('Y-m-d'),
                $newEnd->format('H:i:s'),
                $rentalId,
                $memberId
            ]
        );
    } else {
        $upd = sqlsrv_query(
            $conn,
            'UPDATE Rentals SET return_date = CONVERT(date, ?) WHERE Rental_ID = ? AND member_id = ?',
            [
                $newEnd->format('Y-m-d'),
                $rentalId,
                $memberId
            ]
        );
    }

    if ($upd === false) {
        sqlsrv_rollback($conn);
        echo json_encode(['success' => false, 'message' => 'Failed to update rental']);
        closeConnection($conn);
        exit();
    }

    sqlsrv_commit($conn);

    echo json_encode([
        'success' => true,
        'rental_id' => $rentalId,
        'newReturnDate' => $newEnd->format('Y-m-d'),
        'newReturnTime' => $newEnd->format('H:i:s'),
        'newDurationHours' => $newDurationHours
    ]);

} catch (Exception $e) {
    if ($conn) { sqlsrv_rollback($conn); }
    echo json_encode(['success' => false, 'message' => 'Error extending rental']);
}

closeConnection($conn);
?>
