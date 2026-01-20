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

    // Start a transaction
    sqlsrv_begin_transaction($conn);

    // Update Rentals: set status = Completed, ensure planned return_date set (if null)
    $upd = sqlsrv_query($conn, "UPDATE Rentals SET status = 'Completed', return_date = ISNULL(return_date, CONVERT(date, GETDATE())) WHERE Rental_ID = ?", [$rentalId]);
    if ($upd === false) { throw new Exception('Failed to update rental'); }

    // Insert Returns row if none exists
    $hasRet = sqlsrv_query($conn, 'SELECT TOP 1 Return_ID FROM Returns WHERE rental_id = ? ORDER BY Return_ID DESC', [$rentalId]);
    $exists = ($hasRet && sqlsrv_fetch_array($hasRet, SQLSRV_FETCH_ASSOC));
    if (!$exists) {
        $ins = sqlsrv_query(
            $conn,
            "INSERT INTO Returns (rental_id, admin_id, return_date, return_time, condition, remarks)
             VALUES (?, ?, CONVERT(date, GETDATE()), CONVERT(time, GETDATE()), ?, ?)",
            [$rentalId, $adminId, 'Good', '']
        );
        if ($ins === false) { throw new Exception('Failed to insert return'); }
    }

    // Free the bike (set Available) upon completion
    $updBike = sqlsrv_query($conn, "UPDATE Bike SET availability_status = 'Available' WHERE Bike_ID = (SELECT bike_id FROM Rentals WHERE Rental_ID = ?)", [$rentalId]);
    if ($updBike === false) { throw new Exception('Failed to update bike availability'); }

    sqlsrv_commit($conn);
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($conn) { sqlsrv_rollback($conn); }
    echo json_encode(['success' => false, 'message' => 'Error completing rental']);
}

closeConnection($conn);
?>
