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
    $sql = "EXEC dbo.sp_ConfirmPayment @PaymentID = ?";
    $stmt = sqlsrv_query($conn, $sql, [$paymentId]);
    if ($stmt === false) {
        $errs = sqlsrv_errors();
        $msg = 'Error confirming payment';
        if ($errs && isset($errs[0]['message'])) {
            if (stripos($errs[0]['message'], 'completed payment already exists') !== false) {
                $msg = 'A completed payment already exists for this rental.';
            } elseif (stripos($errs[0]['message'], 'not pending or not found') !== false) {
                $msg = 'Payment not pending or not found';
            }
        }
        echo json_encode(['success' => false, 'message' => $msg]);
        closeConnection($conn);
        exit();
    }

    // Procedure succeeded
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error confirming payment']);
}

closeConnection($conn);
?>
