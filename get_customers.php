<?php
// Returns customers with stats for admin page
session_start();
header('Content-Type: application/json');

// Optional: restrict to admin users only
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

$search = isset($_GET['q']) ? trim($_GET['q']) : '';
// Filters
$status = isset($_GET['status']) ? trim($_GET['status']) : ''; // 'active' | 'inactive' | ''
$join = isset($_GET['join']) ? trim($_GET['join']) : ''; // '' | 'week' | 'month' | 'year'
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'name'; // 'name' | 'recent' | 'rentals' | 'spending'

try {
    // Get members with stats (total rentals, active rentals, total spent)
    // Compute analytics via Rentals and Payments instead of storing in Member.
    $sql = "
        SELECT
            m.Member_ID,
            m.first_name,
            m.last_name,
            m.email,
            m.contact_number,
            m.username,
            m.date_joined,
            COUNT(DISTINCT r.Rental_ID) AS TotalRentals,
            SUM(CASE WHEN r.status = 'Active' THEN 1 ELSE 0 END) AS ActiveRentals,
            ISNULL(SUM(CASE WHEN p.status = 'completed' THEN CAST(p.amount AS FLOAT) ELSE 0 END), 0) AS TotalSpent
        FROM Member m
        LEFT JOIN Rentals r ON r.member_id = m.Member_ID
        LEFT JOIN Payments p ON p.rental_id = r.Rental_ID
        GROUP BY
            m.Member_ID,
            m.first_name,
            m.last_name,
            m.email,
            m.contact_number,
            m.username,
            m.date_joined
        ORDER BY m.Member_ID
    ";

    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        throw new Exception('Query failed');
    }
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
            'activeRentals' => (int)($row['ActiveRentals'] ?? 0),
            'totalSpent' => isset($row['TotalSpent']) ? (float)$row['TotalSpent'] : 0.0
        ];
    }

    // Apply filters
    // Search (case-insensitive, no mbstring dependency)
    if ($search !== '') {
        $members = array_values(array_filter($members, function($m) use ($search) {
            $fullName = trim((string)($m['firstName'] ?? '').' '.(string)($m['lastName'] ?? ''));
            $email = (string)($m['email'] ?? '');
            $phone = (string)($m['phone'] ?? '');
            $username = (string)($m['username'] ?? '');
            return (
                stripos($fullName, $search) !== false ||
                stripos($email, $search) !== false ||
                stripos($phone, $search) !== false ||
                stripos($username, $search) !== false
            );
        }));
    }

    // Rental status
    if ($status === 'active') {
        $members = array_values(array_filter($members, function($m) { return (int)($m['activeRentals'] ?? 0) > 0; }));
    } elseif ($status === 'inactive') {
        $members = array_values(array_filter($members, function($m) { return (int)($m['activeRentals'] ?? 0) === 0; }));
    }

    // Join date window
    if (in_array($join, ['week','month','year'], true)) {
        $now = new DateTime('now');
        $members = array_values(array_filter($members, function($m) use ($join, $now) {
            $joinedStr = $m['joined'] ?? null;
            if (!$joinedStr) return false;
            try {
                $joined = new DateTime($joinedStr);
            } catch (Exception $e) { return false; }
            if ($join === 'week') {
                // Compare ISO week
                return ($joined->format('oW') === $now->format('oW'));
            } elseif ($join === 'month') {
                return ($joined->format('Y-m') === $now->format('Y-m'));
            } elseif ($join === 'year') {
                return ($joined->format('Y') === $now->format('Y'));
            }
            return true;
        }));
    }

    // Sorting
    if ($sort === 'recent') {
        usort($members, function($a,$b){
            return strcmp($b['joined'] ?? '', $a['joined'] ?? '');
        });
    } elseif ($sort === 'spending') {
        // Sort by highest total spent
        usort($members, function($a,$b){
            return (float)($b['totalSpent'] ?? 0) <=> (float)($a['totalSpent'] ?? 0);
        });
    } elseif ($sort === 'rentals') {
        usort($members, function($a,$b){
            return (int)($b['totalRentals'] ?? 0) <=> (int)($a['totalRentals'] ?? 0);
        });
    } else { // name or unsupported options default
        usort($members, function($a,$b){
            $an = trim(($a['lastName'] ?? '').' '.($a['firstName'] ?? ''));
            $bn = trim(($b['lastName'] ?? '').' '.($b['firstName'] ?? ''));
            return strcasecmp($an, $bn);
        });
    }

    // Get summary stats
    $totalMembers = 0; $newThisMonth = 0; $activeRentals = 0; $newThisWeek = 0; $prevMonthCount = 0; $momPct = 0;

    $stmtCount = sqlsrv_query($conn, 'EXEC sp_CountMembers');
    if ($stmtCount && $row = sqlsrv_fetch_array($stmtCount, SQLSRV_FETCH_ASSOC)) {
        $totalMembers = (int)$row['count'];
    }

    $stmtNew = sqlsrv_query($conn, 'EXEC sp_CountMembersNewThisMonth');
    if ($stmtNew && $row = sqlsrv_fetch_array($stmtNew, SQLSRV_FETCH_ASSOC)) {
        $newThisMonth = (int)$row['NewThisMonth'];
    }

    // New this week (ISO week)
    $stmtWeek = sqlsrv_query($conn, "SELECT COUNT(*) AS NewThisWeek FROM Member WHERE DATEPART(ISO_WEEK, date_joined) = DATEPART(ISO_WEEK, GETDATE()) AND DATEPART(YEAR, date_joined) = DATEPART(YEAR, GETDATE())");
    if ($stmtWeek && $row = sqlsrv_fetch_array($stmtWeek, SQLSRV_FETCH_ASSOC)) {
        $newThisWeek = (int)$row['NewThisWeek'];
    }

    // Previous month count
    $stmtPrevMonth = sqlsrv_query($conn, "SELECT COUNT(*) AS PrevMonth FROM Member WHERE YEAR(date_joined) = YEAR(DATEADD(MONTH,-1,GETDATE())) AND MONTH(date_joined) = MONTH(DATEADD(MONTH,-1,GETDATE()))");
    if ($stmtPrevMonth && $row = sqlsrv_fetch_array($stmtPrevMonth, SQLSRV_FETCH_ASSOC)) {
        $prevMonthCount = (int)$row['PrevMonth'];
    }

    // Month-over-month percent
    if ($prevMonthCount > 0) {
        $momPct = (int)round((($newThisMonth - $prevMonthCount) / $prevMonthCount) * 100);
    } else {
        $momPct = $newThisMonth > 0 ? 100 : 0;
    }

    // Count how many members currently have at least one active/ongoing booking.
    // In your DB, upcoming/ongoing rentals are stored as 'Pending', so we
    // count those (and also 'Active' if present) to match the real data.
    $stmtActiveMembers = sqlsrv_query($conn, "SELECT COUNT(DISTINCT member_id) AS Cnt FROM Rentals WHERE status IN ('Pending','Active')");
    if ($stmtActiveMembers && $row = sqlsrv_fetch_array($stmtActiveMembers, SQLSRV_FETCH_ASSOC)) {
        $activeRentals = (int)$row['Cnt'];
    }

    echo json_encode([
        'success' => true,
        'summary' => [
            'totalMembers' => $totalMembers,
            'activeRentals' => $activeRentals,
            'newThisMonth' => $newThisMonth,
            'newThisWeek' => $newThisWeek,
            'monthOverMonthPct' => $momPct
        ],
        'members' => $members
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error loading customers']);
}

closeConnection($conn);
?>