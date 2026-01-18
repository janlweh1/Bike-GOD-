<?php
// Returns customers with stats for admin page
header('Content-Type: application/json');

$serverName = "localhost";
$database = "BikeRental";
$conn = sqlsrv_connect($serverName, ["Database" => $database, "CharacterSet" => "UTF-8"]);

if ($conn === false) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$search = isset($_GET['q']) ? trim($_GET['q']) : '';

try {
    // Get members with stats
    $stmt = sqlsrv_query($conn, 'EXEC sp_GetMembersWithStats');
    $members = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $members[] = [
            'id' => $row['Member_ID'],
            'firstName' => $row['first_name'],
            'lastName' => $row['last_name'],
            'email' => $row['email'],
            'phone' => $row['contact_number'] ?? '',
            'username' => $row['username'] ?? '',
            'joined' => isset($row['date_joined']) && $row['date_joined'] ? $row['date_joined']->format('Y-m-d') : null,
            'totalRentals' => (int)($row['TotalRentals'] ?? 0),
            'activeRentals' => (int)($row['ActiveRentals'] ?? 0)
        ];
    }

    // Optional search filtering on server side
    if ($search !== '') {
        $needle = mb_strtolower($search);
        $members = array_values(array_filter($members, function($m) use ($needle) {
            return (
                strpos(mb_strtolower($m['firstName'].' '.$m['lastName']), $needle) !== false ||
                strpos(mb_strtolower($m['email']), $needle) !== false ||
                strpos(mb_strtolower($m['phone']), $needle) !== false ||
                strpos(mb_strtolower($m['username']), $needle) !== false
            );
        }));
    }

    // Get summary stats
    $totalMembers = 0; $newThisMonth = 0; $activeRentals = 0;

    $stmtCount = sqlsrv_query($conn, 'EXEC sp_CountMembers');
    if ($stmtCount && $row = sqlsrv_fetch_array($stmtCount, SQLSRV_FETCH_ASSOC)) {
        $totalMembers = (int)$row['count'];
    }

    $stmtNew = sqlsrv_query($conn, 'EXEC sp_CountMembersNewThisMonth');
    if ($stmtNew && $row = sqlsrv_fetch_array($stmtNew, SQLSRV_FETCH_ASSOC)) {
        $newThisMonth = (int)$row['NewThisMonth'];
    }

    $stmtAdminStats = sqlsrv_query($conn, 'EXEC sp_GetAdminStats');
    if ($stmtAdminStats && $row = sqlsrv_fetch_array($stmtAdminStats, SQLSRV_FETCH_ASSOC)) {
        $activeRentals = (int)$row['ActiveRentals'];
    }

    echo json_encode([
        'success' => true,
        'summary' => [
            'totalMembers' => $totalMembers,
            'activeRentals' => $activeRentals,
            'newThisMonth' => $newThisMonth
        ],
        'members' => $members
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error loading customers']);
}

sqlsrv_close($conn);
?>