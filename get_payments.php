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

function fmtDateStr($dt) {
    if ($dt instanceof DateTime) return $dt->format('M d, Y');
    return '';
}
function fmtTimeStr($dt) {
    if ($dt instanceof DateTime) return $dt->format('h:i A');
    return '';
}

try {
    // Filters
    $status = isset($_GET['status']) ? strtolower(trim($_GET['status'])) : '';
    $method = isset($_GET['method']) ? strtolower(trim($_GET['method'])) : '';
    $range = isset($_GET['range']) ? strtolower(trim($_GET['range'])) : '';
    $sort = isset($_GET['sort']) ? strtolower(trim($_GET['sort'])) : 'recent';

    $sql = "
        SELECT p.Payment_ID, p.transaction_id, p.payment_date, p.rental_id,
               p.payment_method, p.amount, p.status,
               m.first_name, m.last_name
        FROM Payments p
        LEFT JOIN Rentals r ON r.Rental_ID = p.rental_id
        LEFT JOIN Member m ON m.Member_ID = r.member_id
        WHERE 1=1
    ";
    $params = [];
    if ($status !== '') { $sql .= " AND p.status = ?"; $params[] = $status; }
    if ($method !== '') { $sql .= " AND p.payment_method = ?"; $params[] = $method; }
    if ($range === 'today') {
        $sql .= " AND CAST(p.payment_date AS date) = CAST(GETDATE() AS date)";
    } elseif ($range === 'week') {
        $sql .= " AND CAST(p.payment_date AS date) >= DATEADD(day, -6, CAST(GETDATE() AS date))";
    } elseif ($range === 'month') {
        $sql .= " AND YEAR(p.payment_date) = YEAR(GETDATE()) AND MONTH(p.payment_date) = MONTH(GETDATE())";
    }
    if ($sort === 'oldest') {
        $sql .= " ORDER BY p.Payment_ID ASC";
    } elseif ($sort === 'amount-high') {
        $sql .= " ORDER BY p.amount DESC";
    } elseif ($sort === 'amount-low') {
        $sql .= " ORDER BY p.amount ASC";
    } else {
        $sql .= " ORDER BY p.Payment_ID DESC";
    }

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) { throw new Exception('Query failed'); }

    $payments = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $name = trim((string)($row['first_name'] ?? '') . ' ' . (string)($row['last_name'] ?? ''));
        $pdate = $row['payment_date'];
        $payments[] = [
            'id' => (int)$row['Payment_ID'],
            'transactionId' => (string)($row['transaction_id'] ?? ''),
            'dateStr' => fmtDateStr($pdate),
            'timeStr' => fmtTimeStr($pdate),
            'rentalId' => isset($row['rental_id']) ? (int)$row['rental_id'] : null,
            'customerName' => $name,
            'paymentMethod' => (string)($row['payment_method'] ?? ''),
            'amount' => isset($row['amount']) ? (float)$row['amount'] : 0,
            'status' => strtolower((string)($row['status'] ?? '')),
        ];
    }

    // Summary: today, week (last 7 days), month, pending
    $sumToday = 0; $sumWeek = 0; $sumMonth = 0; $pendingCount = 0; $pendingTotal = 0;
    $sumYesterday = 0; $sumPrevWeek = 0; $sumPrevMonth = 0;

    $stmtToday = sqlsrv_query($conn, "SELECT SUM(CAST(amount AS FLOAT)) AS S FROM Payments WHERE status = 'completed' AND CAST(payment_date AS date) = CAST(GETDATE() AS date)");
    if ($stmtToday && ($r = sqlsrv_fetch_array($stmtToday, SQLSRV_FETCH_ASSOC)) && isset($r['S'])) $sumToday = (float)$r['S'];

    // Last 7 days including today
    $stmtWeek = sqlsrv_query($conn, "SELECT SUM(CAST(amount AS FLOAT)) AS S FROM Payments WHERE status = 'completed' AND CAST(payment_date AS date) >= DATEADD(day, -6, CAST(GETDATE() AS date))");
    if ($stmtWeek && ($r = sqlsrv_fetch_array($stmtWeek, SQLSRV_FETCH_ASSOC)) && isset($r['S'])) $sumWeek = (float)$r['S'];

    $stmtMonth = sqlsrv_query($conn, "SELECT SUM(CAST(amount AS FLOAT)) AS S FROM Payments WHERE status = 'completed' AND YEAR(payment_date) = YEAR(GETDATE()) AND MONTH(payment_date) = MONTH(GETDATE())");
    if ($stmtMonth && ($r = sqlsrv_fetch_array($stmtMonth, SQLSRV_FETCH_ASSOC)) && isset($r['S'])) $sumMonth = (float)$r['S'];
    // Yesterday
    $stmtY = sqlsrv_query($conn, "SELECT SUM(CAST(amount AS FLOAT)) AS S FROM Payments WHERE status = 'completed' AND CAST(payment_date AS date) = DATEADD(day, -1, CAST(GETDATE() AS date))");
    if ($stmtY && ($r = sqlsrv_fetch_array($stmtY, SQLSRV_FETCH_ASSOC)) && isset($r['S'])) $sumYesterday = (float)$r['S'];

    // Previous week (the 7-day window before the current 7-day window)
    $stmtPW = sqlsrv_query($conn, "SELECT SUM(CAST(amount AS FLOAT)) AS S FROM Payments WHERE status = 'completed' AND CAST(payment_date AS date) >= DATEADD(day, -13, CAST(GETDATE() AS date)) AND CAST(payment_date AS date) < DATEADD(day, -6, CAST(GETDATE() AS date))");
    if ($stmtPW && ($r = sqlsrv_fetch_array($stmtPW, SQLSRV_FETCH_ASSOC)) && isset($r['S'])) $sumPrevWeek = (float)$r['S'];

    // Previous month
    $stmtPM = sqlsrv_query($conn, "SELECT SUM(CAST(amount AS FLOAT)) AS S FROM Payments WHERE status = 'completed' AND YEAR(payment_date) = YEAR(DATEADD(month,-1,GETDATE())) AND MONTH(payment_date) = MONTH(DATEADD(month,-1,GETDATE()))");
    if ($stmtPM && ($r = sqlsrv_fetch_array($stmtPM, SQLSRV_FETCH_ASSOC)) && isset($r['S'])) $sumPrevMonth = (float)$r['S'];

    function pctChange($current, $prev) {
        if ($prev > 0) { return (($current - $prev) / $prev) * 100.0; }
        return null;
    }
    $todayChangePct = pctChange($sumToday, $sumYesterday);
    $weekChangePct = pctChange($sumWeek, $sumPrevWeek);
    $monthChangePct = pctChange($sumMonth, $sumPrevMonth);

    // Pending payments overview (respect date range filter for consistency)
    $pendSql = "SELECT COUNT(*) AS C, SUM(CAST(amount AS FLOAT)) AS T FROM Payments WHERE status = 'pending'";
    if ($range === 'today') {
        $pendSql .= " AND CAST(payment_date AS date) = CAST(GETDATE() AS date)";
    } elseif ($range === 'week') {
        $pendSql .= " AND CAST(payment_date AS date) >= DATEADD(day, -6, CAST(GETDATE() AS date))";
    } elseif ($range === 'month') {
        $pendSql .= " AND YEAR(payment_date) = YEAR(GETDATE()) AND MONTH(payment_date) = MONTH(GETDATE())";
    }
    $stmtPendCnt = sqlsrv_query($conn, $pendSql);
    if ($stmtPendCnt && ($r = sqlsrv_fetch_array($stmtPendCnt, SQLSRV_FETCH_ASSOC))) {
        $pendingCount = isset($r['C']) ? (int)$r['C'] : 0;
        $pendingTotal = isset($r['T']) ? (float)$r['T'] : 0;
    }

    // Method sums (completed only)
    $methodSums = ['cash' => 0, 'card' => 0, 'ewallet' => 0, 'bank' => 0];
    $stmtMethods = sqlsrv_query($conn, "SELECT payment_method, SUM(CAST(amount AS FLOAT)) AS S FROM Payments WHERE status = 'completed' GROUP BY payment_method");
    if ($stmtMethods) {
        while ($r = sqlsrv_fetch_array($stmtMethods, SQLSRV_FETCH_ASSOC)) {
            $m = strtolower((string)($r['payment_method'] ?? ''));
            if (isset($methodSums[$m])) $methodSums[$m] = (float)$r['S'];
        }
    }

    // Recent activity: latest 8
    $act = [];
    $stmtA = sqlsrv_query($conn, "
        SELECT TOP 8 p.payment_date, p.amount, p.status, m.first_name, m.last_name
        FROM Payments p
        LEFT JOIN Rentals r ON r.Rental_ID = p.rental_id
        LEFT JOIN Member m ON m.Member_ID = r.member_id
        ORDER BY p.payment_date DESC, p.Payment_ID DESC
    ");
    if ($stmtA) {
        $nowTs = time();
        while ($ra = sqlsrv_fetch_array($stmtA, SQLSRV_FETCH_ASSOC)) {
            $name = trim((string)($ra['first_name'] ?? '') . ' ' . (string)($ra['last_name'] ?? ''));
            $pd = $ra['payment_date'];
            $ts = $pd instanceof DateTime ? $pd->getTimestamp() : $nowTs;
            $diff = max(0, $nowTs - $ts);
            $mins = (int)floor($diff / 60);
            $hours = (int)floor($diff / 3600);
            $days = (int)floor($diff / 86400);
            $timeStr = $mins < 60 ? ($mins . ' minutes ago') : ($hours < 24 ? ($hours . ' hours ago') : ($days . ' days ago'));
            $statusA = strtolower((string)($ra['status'] ?? ''));
            $text = ($statusA === 'completed' ? 'Payment received from ' : 'Payment pending from ') . ($name !== '' ? $name : 'Customer');
            $act[] = [
                'text' => $text,
                'time' => $timeStr,
                'amount' => isset($ra['amount']) ? (float)$ra['amount'] : 0,
                'status' => $statusA
            ];
        }
    }

    // Rentals without a completed payment (unpaid)
        $unpaidRentals = [];
        $sqlU = "
                SELECT r.Rental_ID,
                             r.rental_date,
                             r.rental_time,
                             r.status AS rental_status,
                             m.first_name, m.last_name,
                             b.bike_name_model
                FROM Rentals r
                INNER JOIN Member m ON m.Member_ID = r.member_id
                INNER JOIN Bike b ON b.Bike_ID = r.bike_id
                LEFT JOIN Payments p ON p.rental_id = r.Rental_ID AND p.status = 'completed'
                WHERE p.Payment_ID IS NULL
                    AND r.status <> 'Cancelled'
                ORDER BY r.Rental_ID DESC";
    $stU = sqlsrv_query($conn, $sqlU);
    if ($stU) {
        while ($row = sqlsrv_fetch_array($stU, SQLSRV_FETCH_ASSOC)) {
            $name = trim((string)($row['first_name'] ?? '') . ' ' . (string)($row['last_name'] ?? ''));

            // Map database rental status to UI-friendly status, keeping it
            // consistent with the Rentals overview pages:
            // - Cancelled stays "cancelled"
            // - Completed stays "completed"
            // - Any other in-progress state (e.g. "pending") is shown as "active"
            $statusDb = strtolower((string)($row['rental_status'] ?? ''));
            if ($statusDb === 'cancelled') {
                $statusUi = 'cancelled';
            } elseif ($statusDb === 'completed') {
                $statusUi = 'completed';
            } else {
                $statusUi = 'active';
            }

            $unpaidRentals[] = [
                'rentalId' => (int)$row['Rental_ID'],
                'customerName' => $name,
                'bikeModel' => (string)($row['bike_name_model'] ?? ''),
                'pickupDate' => ($row['rental_date'] instanceof DateTime) ? $row['rental_date']->format('Y-m-d') : null,
                'pickupTime' => ($row['rental_time'] instanceof DateTime) ? $row['rental_time']->format('H:i') : null,
                'status' => $statusUi
            ];
        }
    }
    $unpaidCount = count($unpaidRentals);

    echo json_encode([
        'success' => true,
        'summary' => [
            'todayRevenue' => $sumToday,
            'weekRevenue' => $sumWeek,
            'monthRevenue' => $sumMonth,
            'pendingCount' => $pendingCount,
            'pendingTotal' => $pendingTotal,
            'unpaidCount' => $unpaidCount,
            'todayChangePct' => $todayChangePct,
            'weekChangePct' => $weekChangePct,
            'monthChangePct' => $monthChangePct
        ],
        'methodSums' => $methodSums,
        'payments' => $payments,
        'activity' => $act,
        'unpaidRentals' => $unpaidRentals
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error loading payments']);
}

closeConnection($conn);
?>
