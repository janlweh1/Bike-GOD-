<?php
// Marks a rental as completed and records a return
session_start();
header('Content-Type: application/json');

// Restrict to admin users only
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

$rentalId = isset($_POST['rental_id']) ? (int)$_POST['rental_id'] : 0;
if ($rentalId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid rental id']);
    exit();
}

$adminId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
if (!$adminId) {
    echo json_encode(['success' => false, 'message' => 'Missing admin session']);
    exit();
}

try {
    // Basic existence check
    $stmt = sqlsrv_query($conn, 'SELECT Rental_ID, status FROM Rentals WHERE Rental_ID = ?', [$rentalId]);
    if ($stmt === false || !($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
        echo json_encode(['success' => false, 'message' => 'Rental not found']);
        closeConnection($conn);
        exit();
    }

    // Avoid duplicate completion
    if (strtolower((string)($row['status'] ?? '')) === 'completed') {
        echo json_encode(['success' => true, 'message' => 'Already completed']);
        closeConnection($conn);
        exit();
    }

    // Complete rental via stored procedure
    $res = sqlsrv_query($conn, 'EXEC dbo.sp_CompleteRentalAdmin @RentalID = ?, @AdminID = ?', [$rentalId, $adminId]);
    if ($res === false) {
        throw new Exception('Failed to complete rental');
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error completing rental']);
}

closeConnection($conn);
?>
