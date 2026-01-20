<?php
// Returns rentals list and summary for admin rentals page
session_start();
header('Content-Type: application/json');
// Allow reading rentals regardless of session for now
// If you want to restrict, check_session.php can gate access.

require_once __DIR__ . '/db_config.php';
$conn = getConnection();
if ($conn === null) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Helper to safely format SQLSRV types
function fmt_date($d) { return $d instanceof DateTime ? $d->format('Y-m-d') : null; }
function fmt_time($t) { return $t instanceof DateTime ? $t->format('H:i') : null; }

try {
    // Fetch rentals joined with member & bike; include latest return if present
    $sql = "
        SELECT r.Rental_ID,
               r.rental_date,
               r.rental_time,
               r.return_date AS planned_return_date,
               r.status,
               m.first_name, m.last_name, m.email, m.contact_number,
               b.bike_name_model, b.bike_type, b.hourly_rate,
               rr.return_date AS actual_return_date,
               rr.return_time AS actual_return_time
        FROM Rentals r
        INNER JOIN Member m ON m.Member_ID = r.member_id
        INNER JOIN Bike b ON b.Bike_ID = r.bike_id
        OUTER APPLY (
            SELECT TOP 1 return_date, return_time
            FROM Returns x
            WHERE x.rental_id = r.Rental_ID
            ORDER BY x.Return_ID DESC
        ) rr
        ORDER BY r.Rental_ID DESC
    ";

    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        throw new Exception('Query failed');
    }

    $now = new DateTime('now');
    $todayYmd = $now->format('Y-m-d');
    $rentals = [];

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $rentalId = (int)$row['Rental_ID'];
        $startDate = $row['rental_date'];
        $startTime = $row['rental_time'];
        $plannedReturnDate = $row['planned_return_date'];
        $actualReturnDate = $row['actual_return_date'] ?? null;
        $actualReturnTime = $row['actual_return_time'] ?? null;
        $statusDb = strtolower((string)($row['status'] ?? ''));

        // Compose DateTime for start
        $startDt = null;
        if ($startDate instanceof DateTime) {
            $startDt = new DateTime($startDate->format('Y-m-d') . ' ' . ($startTime instanceof DateTime ? $startTime->format('H:i:s') : '00:00:00'));
        }

        // Determine end/planned end
        $plannedEndDt = null;
        if ($plannedReturnDate instanceof DateTime && $startDt) {
            // Assume same time-of-day return as pickup time if no explicit return time in Rentals
            $plannedEndDt = new DateTime($plannedReturnDate->format('Y-m-d') . ' ' . ($startTime instanceof DateTime ? $startTime->format('H:i:s') : '00:00:00'));
        }

        $actualEndDt = null;
        if ($actualReturnDate instanceof DateTime) {
            $actualEndDt = new DateTime($actualReturnDate->format('Y-m-d') . ' ' . ($actualReturnTime instanceof DateTime ? $actualReturnTime->format('H:i:s') : '00:00:00'));
        }

        // Compute duration hours: prefer planned for ongoing, actual for completed
        $durationHours = 0;
        if ($actualEndDt && $startDt) {
            $durationHours = max(1, (int)round(($actualEndDt->getTimestamp() - $startDt->getTimestamp()) / 3600));
        } elseif ($plannedEndDt && $startDt) {
            $durationHours = max(1, (int)round(($plannedEndDt->getTimestamp() - $startDt->getTimestamp()) / 3600));
        } elseif ($startDt) {
            // Fallback: duration to now
            $durationHours = max(1, (int)round(($now->getTimestamp() - $startDt->getTimestamp()) / 3600));
        }

        // Compute cost using bike hourly rate
        $rate = isset($row['hourly_rate']) ? (float)$row['hourly_rate'] : 0.0;
        $cost = round($rate * $durationHours, 2);

        // Derive status
        $status = 'active';
        if ($statusDb === 'completed' || $actualEndDt) {
            $status = 'completed';
        } elseif ($startDt && $plannedEndDt && $now > $plannedEndDt) {
            $status = 'overdue';
        } elseif ($startDt && $now < $startDt) {
            $status = 'pending';
        } elseif ($statusDb) {
            $status = $statusDb; // active/cancelled/etc.
        }

        // Map bike category to simplified token
        $bikeType = strtolower((string)($row['bike_type'] ?? ''));
        $category = 'city';
        if (strpos($bikeType, 'mountain') !== false) $category = 'mountain';
        elseif (strpos($bikeType, 'road') !== false) $category = 'road';
        elseif (strpos($bikeType, 'electric') !== false || strpos($bikeType, 'e-') !== false) $category = 'electric';
        elseif (strpos($bikeType, 'kid') !== false) $category = 'kids';
        elseif (strpos($bikeType, 'premium') !== false) $category = 'premium';

        $r = [
            'id' => (string)$rentalId,
            'pickupDate' => fmt_date($startDate) ?: null,
            'pickupTime' => fmt_time($startTime) ?: '00:00',
            'duration' => $durationHours,
            'cost' => $cost,
            'status' => $status,
            'startTime' => $startDt ? $startDt->format('c') : null,
            'endTime' => $actualEndDt ? $actualEndDt->format('c') : null,
            'name' => (string)($row['bike_name_model'] ?? 'Bike'),
            'category' => $category,
            'customerName' => trim((string)($row['first_name'] ?? '') . ' ' . (string)($row['last_name'] ?? '')),
            'customerEmail' => (string)($row['email'] ?? ''),
            'customerPhone' => (string)($row['contact_number'] ?? ''),
        ];

        $rentals[] = $r;
    }

    // Compute summary directly from DB for accuracy
    $sumActive = 0; $sumOverdue = 0; $sumCompletedToday = 0; $sumRevenueToday = 0.0;

    // Active
    $stmtA = sqlsrv_query($conn, "SELECT COUNT(*) AS Cnt FROM Rentals WHERE status = 'Active'");
    if ($stmtA && ($rowA = sqlsrv_fetch_array($stmtA, SQLSRV_FETCH_ASSOC))) { $sumActive = (int)$rowA['Cnt']; }
    // Overdue: planned return date before today while still Active
    $stmtO = sqlsrv_query($conn, "SELECT COUNT(*) AS Cnt FROM Rentals WHERE status = 'Active' AND return_date < CONVERT(date, GETDATE())");
    if ($stmtO && ($rowO = sqlsrv_fetch_array($stmtO, SQLSRV_FETCH_ASSOC))) { $sumOverdue = (int)$rowO['Cnt']; }
    // Completed today
    $stmtC = sqlsrv_query($conn, "SELECT COUNT(*) AS Cnt FROM Returns WHERE return_date = CONVERT(date, GETDATE())");
    if ($stmtC && ($rowC = sqlsrv_fetch_array($stmtC, SQLSRV_FETCH_ASSOC))) { $sumCompletedToday = (int)$rowC['Cnt']; }
    // Revenue today: sum of hourly_rate * duration between rental start and actual return for returns today
    $stmtR = sqlsrv_query($conn, "
        SELECT SUM(
            CAST(b.hourly_rate AS FLOAT) * NULLIF(DATEDIFF(HOUR,
                CONVERT(datetime, CONCAT(CONVERT(varchar(10), r.rental_date, 120), ' ', CONVERT(varchar(8), r.rental_time, 108))),
                CONVERT(datetime, CONCAT(CONVERT(varchar(10), x.return_date, 120), ' ', CONVERT(varchar(8), x.return_time, 108)))
            ), 0)
        ) AS Revenue
        FROM Returns x
        INNER JOIN Rentals r ON r.Rental_ID = x.rental_id
        INNER JOIN Bike b ON b.Bike_ID = r.bike_id
        WHERE x.return_date = CONVERT(date, GETDATE())
    ");
    if ($stmtR && ($rowR = sqlsrv_fetch_array($stmtR, SQLSRV_FETCH_ASSOC)) && isset($rowR['Revenue'])) {
        $sumRevenueToday = (float)$rowR['Revenue'];
    }

    echo json_encode([
        'success' => true,
        'summary' => [
            'active' => $sumActive,
            'completedToday' => $sumCompletedToday,
            'overdue' => $sumOverdue,
            'todayRevenue' => $sumRevenueToday
        ],
        'rentals' => $rentals
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error loading rentals']);
}

closeConnection($conn);
?>
