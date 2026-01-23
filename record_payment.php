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

// Validate required fields
$transactionId = trim((string)($data['transactionId'] ?? ''));
$rentalId = (int)($data['rentalId'] ?? 0);
$amount = (float)($data['amount'] ?? 0);
$paymentMethod = strtolower(trim((string)($data['paymentMethod'] ?? '')));
$status = strtolower(trim((string)($data['status'] ?? '')));
$paymentDate = trim((string)($data['paymentDate'] ?? ''));
$paymentTime = trim((string)($data['paymentTime'] ?? ''));
$notes = trim((string)($data['notes'] ?? ''));

$allowedMethods = ['cash', 'card', 'ewallet', 'bank'];
$allowedStatus = ['completed', 'pending', 'failed'];

if ($transactionId === '' || $rentalId <= 0 || $amount <= 0 || !in_array($paymentMethod, $allowedMethods) || !in_array($status, $allowedStatus) || $paymentDate === '' || $paymentTime === '') {
    echo json_encode(['success' => false, 'message' => 'Missing or invalid fields']);
    closeConnection($conn);
    exit();
}

// Compose datetime string
$paymentDateTimeStr = $paymentDate . ' ' . $paymentTime;

try {
    // Get member_id and current rental status
    $memberId = null;
    $rentalStatusDb = null;
    $stmtM = sqlsrv_query($conn, 'SELECT member_id, status FROM Rentals WHERE Rental_ID = ?', [$rentalId]);
    if ($stmtM && ($rowM = sqlsrv_fetch_array($stmtM, SQLSRV_FETCH_ASSOC))) {
        $memberId = isset($rowM['member_id']) ? (int)$rowM['member_id'] : null;
        $rentalStatusDb = strtolower((string)($rowM['status'] ?? ''));
    }

    // Do not allow payments to be recorded for rentals that have been
    // cancelled (including those cancelled within the 5-minute free
    // cancellation window); these should incur no charge.
    if ($rentalStatusDb === 'cancelled') {
        echo json_encode(['success' => false, 'message' => 'This rental is cancelled. No payment is required.']);
        closeConnection($conn);
        exit();
    }

    // Insert payment via stored procedure
    $sql = 'EXEC dbo.sp_RecordPayment ?, ?, ?, ?, ?, ?, ?';
    $params = [
        $transactionId,
        $rentalId,
        $amount,
        $paymentMethod,
        $status,
        $paymentDateTimeStr,
        $notes !== '' ? $notes : null
    ];
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        $errs = sqlsrv_errors();
        $msg = 'Error recording payment';
        if ($errs && isset($errs[0]['SQLSTATE'])) {
            $state = $errs[0]['SQLSTATE'];
            $text = $errs[0]['message'] ?? '';
            if (strpos($text, 'Duplicate transaction ID') !== false || $state === '23000') {
                $msg = 'Transaction ID already exists or duplicate key.';
            } elseif (strpos($text, 'Payment already completed for this rental') !== false) {
                $msg = 'A completed payment already exists for this rental.';
            } elseif (strpos($text, 'Rental not found') !== false) {
                $msg = 'Rental not found.';
            } elseif (strpos($text, 'Invalid payment date/time') !== false) {
                $msg = 'Invalid payment date/time.';
            } elseif (strpos($text, 'Amount does not match expected rental cost') !== false) {
                $msg = 'Amount does not match the expected rental cost.';
            }
        }
        echo json_encode(['success' => false, 'message' => $msg]);
        closeConnection($conn);
        exit();
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error recording payment']);
}

closeConnection($conn);
?>
