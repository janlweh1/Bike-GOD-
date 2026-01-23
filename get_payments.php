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

    $sql = "EXEC dbo.sp_GetPaymentsFiltered @Status = ?, @Method = ?, @Range = ?, @Sort = ?";
    $stmt = sqlsrv_query($conn, $sql, [
        $status !== '' ? $status : null,
        $method !== '' ? $method : null,
        $range !== '' ? $range : null,
        $sort !== '' ? $sort : 'recent'
    ]);
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

    // Summary & pending via stored procedure
    $sumToday = 0; $sumWeek = 0; $sumMonth = 0; $pendingCount = 0; $pendingTotal = 0;
    $todayChangePct = null; $weekChangePct = null; $monthChangePct = null;

    $stmtSummary = sqlsrv_query($conn, "EXEC dbo.sp_GetPaymentSummaryAndPending @Range = ?", [$range !== '' ? $range : null]);
    if ($stmtSummary && ($r = sqlsrv_fetch_array($stmtSummary, SQLSRV_FETCH_ASSOC))) {
        $sumToday       = isset($r['TodayRevenue']) ? (float)$r['TodayRevenue'] : 0;
        $sumWeek        = isset($r['WeekRevenue']) ? (float)$r['WeekRevenue'] : 0;
        $sumMonth       = isset($r['MonthRevenue']) ? (float)$r['MonthRevenue'] : 0;
        $pendingCount   = isset($r['PendingCount']) ? (int)$r['PendingCount'] : 0;
        $pendingTotal   = isset($r['PendingTotal']) ? (float)$r['PendingTotal'] : 0;
        $todayChangePct = isset($r['TodayChangePct']) ? (float)$r['TodayChangePct'] : null;
        $weekChangePct  = isset($r['WeekChangePct']) ? (float)$r['WeekChangePct'] : null;
        $monthChangePct = isset($r['MonthChangePct']) ? (float)$r['MonthChangePct'] : null;
    }

    // Method sums (completed only) via stored procedure
    $methodSums = ['cash' => 0, 'card' => 0, 'ewallet' => 0, 'bank' => 0];
    $stmtMethods = sqlsrv_query($conn, "EXEC dbo.sp_GetPaymentMethodSums");
    if ($stmtMethods) {
        while ($r = sqlsrv_fetch_array($stmtMethods, SQLSRV_FETCH_ASSOC)) {
            $m = strtolower((string)($r['payment_method'] ?? ''));
            if (isset($methodSums[$m])) {
                $methodSums[$m] = isset($r['TotalAmount']) ? (float)$r['TotalAmount'] : 0;
            }
        }
    }

    // Recent activity: latest 8
    $act = [];
    $stmtA = sqlsrv_query($conn, "EXEC dbo.sp_GetRecentPaymentActivity");
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
                'timestamp' => $ts,
                'amount' => isset($ra['amount']) ? (float)$ra['amount'] : 0,
                'status' => $statusA
            ];
        }
    }

    // Rentals without a completed payment (unpaid)
    $unpaidRentals = [];
    $stU = sqlsrv_query($conn, "EXEC dbo.sp_GetUnpaidRentals");
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
