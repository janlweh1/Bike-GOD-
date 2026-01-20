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

$paymentId = (int)($data['id'] ?? 0);
if ($paymentId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment id']);
    closeConnection($conn);
    exit();
}

try {
    // Update pending -> completed and set payment_date to now
    $sql = "UPDATE Payments SET status = 'completed', payment_date = GETDATE() WHERE Payment_ID = ? AND status = 'pending'";
    $stmt = sqlsrv_query($conn, $sql, [$paymentId]);
    if ($stmt === false) {
        $errs = sqlsrv_errors();
        $msg = 'Error confirming payment';
        if ($errs && isset($errs[0]['SQLSTATE'])) {
            $state = $errs[0]['SQLSTATE'];
            if ($state === '23000') {
                $msg = 'A completed payment already exists for this rental.';
            }
        }
        echo json_encode(['success' => false, 'message' => $msg]);
        closeConnection($conn);
        exit();
    }
    // Check rows affected
    $rows = sqlsrv_rows_affected($stmt);
    if ($rows === false || $rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Payment not pending or not found']);
        closeConnection($conn);
        exit();
    }

    // Also mark related rental as completed if exists
    $rentalId = null;
    $stmtR = sqlsrv_query($conn, 'SELECT rental_id FROM Payments WHERE Payment_ID = ?', [$paymentId]);
    if ($stmtR && ($rowR = sqlsrv_fetch_array($stmtR, SQLSRV_FETCH_ASSOC))) {
        $rentalId = isset($rowR['rental_id']) ? (int)$rowR['rental_id'] : null;
    }
    if ($rentalId) {
        // Update Rentals.status to Completed if not already
        $stmtU = sqlsrv_query($conn, "UPDATE Rentals SET status = 'Completed' WHERE Rental_ID = ? AND (status IS NULL OR status <> 'Completed')", [$rentalId]);
        // ignore failure; best-effort
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error confirming payment']);
}

closeConnection($conn);
?>
