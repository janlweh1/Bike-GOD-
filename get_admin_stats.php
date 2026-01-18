<?php
// Get admin statistics
header('Content-Type: application/json');

// Database configuration
$serverName = "localhost";
$database = "BikeRental";
$username = "";
$password = "";

$connectionOptions = array(
    "Database" => $database,
    "CharacterSet" => "UTF-8"
);

$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

try {
    // Get admin statistics using stored procedure
    $sql = "EXEC sp_GetAdminStats";
    $stmt = sqlsrv_query($conn, $sql);

    if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        echo json_encode([
            'success' => true,
            'totalBikes' => $row['TotalBikes'],
            'activeRentals' => $row['ActiveRentals'],
            'totalMembers' => $row['TotalMembers']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch statistics']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching statistics']);
}

sqlsrv_close($conn);
?>