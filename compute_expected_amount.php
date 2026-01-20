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
    // Get rental start and rate
    $stmt = sqlsrv_query($conn, "SELECT r.rental_date, r.rental_time, b.hourly_rate, r.Rental_ID FROM Rentals r INNER JOIN Bike b ON b.Bike_ID = r.bike_id WHERE r.Rental_ID = ?", [$rentalId]);
    if ($stmt === false || !($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
        echo json_encode(['success' => false, 'message' => 'Rental not found']);
        closeConnection($conn);
        exit();
    }

    $startDate = $row['rental_date'];
    $startTime = $row['rental_time'];
    $rate = isset($row['hourly_rate']) ? (float)$row['hourly_rate'] : 0.0;

    $startDt = null;
    if ($startDate instanceof DateTime) {
        $startDt = new DateTime($startDate->format('Y-m-d') . ' ' . ($startTime instanceof DateTime ? $startTime->format('H:i:s') : '00:00:00'));
    }

    // Determine end time: prefer Returns, else provided payment date/time, else now
    $endDt = null;
    $stmtR = sqlsrv_query($conn, "SELECT TOP 1 return_date, return_time FROM Returns WHERE rental_id = ? ORDER BY Return_ID DESC", [$rentalId]);
    if ($stmtR && ($rowR = sqlsrv_fetch_array($stmtR, SQLSRV_FETCH_ASSOC))) {
        $rd = $rowR['return_date'];
        $rt = $rowR['return_time'];
        if ($rd instanceof DateTime) {
            $endDt = new DateTime($rd->format('Y-m-d') . ' ' . ($rt instanceof DateTime ? $rt->format('H:i:s') : '00:00:00'));
        }
    }

    if ($endDt === null) {
        if ($paymentDate !== '' && $paymentTime !== '') {
            $endDt = DateTime::createFromFormat('Y-m-d H:i', $paymentDate . ' ' . $paymentTime);
        }
        if (!$endDt) {
            $endDt = new DateTime('now');
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
