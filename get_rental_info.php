<?php
session_start();
header('Content-Type: application/json');

// Admin only
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

$rentalId = isset($_GET['rentalId']) ? (int)$_GET['rentalId'] : 0;
if ($rentalId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid rental id']);
    closeConnection($conn);
    exit();
}

try {
    $sql = "SELECT r.Rental_ID,
                    m.first_name, m.last_name, m.email, m.contact_number,
                    b.bike_name_model
             FROM Rentals r
             INNER JOIN Member m ON m.Member_ID = r.member_id
             INNER JOIN Bike b ON b.Bike_ID = r.bike_id
             WHERE r.Rental_ID = ?";
    $stmt = sqlsrv_query($conn, $sql, [$rentalId]);
    if ($stmt === false || !($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
        echo json_encode(['success' => false, 'message' => 'Rental not found']);
        closeConnection($conn);
        exit();
    }
    $name = trim((string)($row['first_name'] ?? '') . ' ' . (string)($row['last_name'] ?? ''));
    echo json_encode([
        'success' => true,
        'rentalId' => $rentalId,
        'customerName' => $name,
        'bikeModel' => (string)($row['bike_name_model'] ?? '')
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error retrieving rental info']);
}

closeConnection($conn);
?>