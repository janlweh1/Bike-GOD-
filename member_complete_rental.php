<?php
// Allows a member to end or cancel their own rental and syncs with admin view
session_start();
header('Content-Type: application/json');

// Must be logged in as member
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'member' || !isset($_SESSION['user_id'])) {
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
$rentalId = isset($_POST['rental_id']) ? (int)$_POST['rental_id'] : 0;
// Optional explicit action from frontend: 'complete' or 'cancel'
$action = isset($_POST['action']) ? strtolower(trim((string)$_POST['action'])) : '';

if ($memberId <= 0 || $rentalId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    closeConnection($conn);
    exit();
}

try {
    $sql = "EXEC dbo.sp_MemberEndRental @RentalID = ?, @MemberID = ?, @RequestedAction = ?";
    $params = [
        $rentalId,
        $memberId,
        $action !== '' ? $action : null
    ];

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        $errs = sqlsrv_errors();
        $msg = 'Error ending rental';
        $detail = null;
        if ($errs && isset($errs[0]['message'])) {
            $detail = $errs[0]['message'];
            if (stripos($detail, 'Rental not found') !== false) {
                $msg = 'Rental not found';
            } elseif (stripos($detail, 'You do not own this rental') !== false) {
                http_response_code(403);
                $msg = 'You do not own this rental';
            } elseif (stripos($detail, 'sp_MemberEndRental') !== false) {
                $msg = 'Stored procedure sp_MemberEndRental is missing in the database';
            }
        }
        echo json_encode(['success' => false, 'message' => $msg, 'detail' => $detail]);
        closeConnection($conn);
        exit();
    }

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Error ending rental', 'detail' => 'Procedure returned no result row']);
        closeConnection($conn);
        exit();
    }

    $newStatus = isset($row['NewStatus']) ? strtolower((string)$row['NewStatus']) : '';
    echo json_encode([
        'success' => (bool)$row['Success'],
        'status'  => $newStatus !== '' ? $newStatus : 'completed',
        'message' => isset($row['Message']) ? (string)$row['Message'] : 'Rental ended'
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error ending rental', 'detail' => $e->getMessage()]);
}

closeConnection($conn);
?>
