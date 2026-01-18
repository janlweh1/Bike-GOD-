<?php
// Get member statistics
header('Content-Type: application/json');

$memberId = $_GET['member_id'] ?? 0;

if (!$memberId) {
    echo json_encode(['success' => false, 'message' => 'Member ID required']);
    exit();
}

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
    // Get member statistics using stored procedure
    $sql = "EXEC sp_GetMemberStats @MemberID = ?";
    $params = array($memberId);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        echo json_encode([
            'success' => true,
            'totalRentals' => $row['TotalRentals'],
            'activeRentals' => $row['ActiveRentals'],
            'favoriteBikes' => $row['FavoriteBikes']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch statistics']);
    }

    echo json_encode([
        'success' => true,
        'totalRentals' => $totalRentals,
        'activeRentals' => $activeRentals,
        'favoriteBikes' => $favoriteBikes
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching statistics']);
}

sqlsrv_close($conn);
?>