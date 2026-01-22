<?php
// Allows a member to end or cancel their own rental and syncs with admin view
session_start();
header('Content-Type: application/json');

// Must be logged in as member
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'member') {
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

$memberId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$rentalId = isset($_POST['rental_id']) ? (int)$_POST['rental_id'] : 0;
// Optional explicit action from frontend: 'complete' or 'cancel'
$action = isset($_POST['action']) ? strtolower(trim((string)$_POST['action'])) : '';

if ($memberId <= 0 || $rentalId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    closeConnection($conn);
    exit();
}

try {
    // Load rental and basic info; ensure it belongs to this member
    $stmt = sqlsrv_query(
        $conn,
        "SELECT r.Rental_ID, r.member_id, r.bike_id, r.admin_id, r.rental_date, r.rental_time, r.return_date, r.status
         FROM Rentals r
         WHERE r.Rental_ID = ?",
        [$rentalId]
    );
    if ($stmt === false || !($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
        echo json_encode(['success' => false, 'message' => 'Rental not found']);
        closeConnection($conn);
        exit();
    }

    if ((int)$row['member_id'] !== $memberId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You do not own this rental']);
        closeConnection($conn);
        exit();
    }

    $statusDb = strtolower((string)($row['status'] ?? ''));
    if (in_array($statusDb, ['completed', 'cancelled'], true)) {
        // Already ended; nothing more to do
        echo json_encode(['success' => true, 'message' => 'Rental already ended']);
        closeConnection($conn);
        exit();
    }

    $rentalDate = $row['rental_date'];
    $rentalTime = $row['rental_time'];
    $bikeId = (int)($row['bike_id'] ?? 0);
    $adminId = isset($row['admin_id']) ? (int)$row['admin_id'] : null;

    // Derive whether rental has started from DB times (server-side check)
    $now = new DateTime('now');
    $startDt = null;
    if ($rentalDate instanceof DateTime) {
        $startDt = new DateTime($rentalDate->format('Y-m-d') . ' ' . ($rentalTime instanceof DateTime ? $rentalTime->format('H:i:s') : '00:00:00'));
    }

    $hasStarted = $startDt && ($now >= $startDt);

    // Normalise requested action; if not provided or invalid, we fall back
    // to legacy behaviour (cancel before start, complete after start).
    $requestedAction = in_array($action, ['complete', 'cancel'], true) ? $action : null;

    sqlsrv_begin_transaction($conn);

    if ($requestedAction === 'cancel') {
        // Explicit cancellation request from member
        $upd = sqlsrv_query(
            $conn,
            "UPDATE Rentals SET status = 'Cancelled' WHERE Rental_ID = ?",
            [$rentalId]
        );
        if ($upd === false) {
            throw new Exception('Failed to cancel rental');
        }

        // Free the bike as well
        if ($bikeId > 0) {
            $updBike = sqlsrv_query($conn, "UPDATE Bike SET availability_status = 'Available' WHERE Bike_ID = ?", [$bikeId]);
            if ($updBike === false) {
                throw new Exception('Failed to update bike availability');
            }
        }

        $newStatus = 'cancelled';
    } elseif ($requestedAction === 'complete') {
        // Explicit completion request from member
        $upd = sqlsrv_query(
            $conn,
            "UPDATE Rentals SET status = 'Completed', return_date = ISNULL(return_date, CONVERT(date, GETDATE())) WHERE Rental_ID = ?",
            [$rentalId]
        );
        if ($upd === false) {
            throw new Exception('Failed to update rental status');
        }

        // Insert a Returns row so admin summaries & history see completion
        $ins = sqlsrv_query(
            $conn,
            "INSERT INTO Returns (rental_id, admin_id, return_date, return_time, condition, remarks)
             VALUES (?, ?, CONVERT(date, GETDATE()), CONVERT(time, GETDATE()), ?, ?)",
            [
                $rentalId,
                $adminId ?: null,
                'Good',
                'Returned by member via portal'
            ]
        );
        if ($ins === false) {
            throw new Exception('Failed to insert return record');
        }

        // Free the bike
        if ($bikeId > 0) {
            $updBike = sqlsrv_query($conn, "UPDATE Bike SET availability_status = 'Available' WHERE Bike_ID = ?", [$bikeId]);
            if ($updBike === false) {
                throw new Exception('Failed to update bike availability');
            }
        }

        $newStatus = 'completed';
    } else {
        // Legacy behaviour kept for backward compatibility:
        // - If rental has already started → mark as Completed
        // - If rental has not started yet → mark as Cancelled
        if ($hasStarted) {
            $upd = sqlsrv_query(
                $conn,
                "UPDATE Rentals SET status = 'Completed', return_date = ISNULL(return_date, CONVERT(date, GETDATE())) WHERE Rental_ID = ?",
                [$rentalId]
            );
            if ($upd === false) {
                throw new Exception('Failed to update rental status');
            }

            $ins = sqlsrv_query(
                $conn,
                "INSERT INTO Returns (rental_id, admin_id, return_date, return_time, condition, remarks)
                 VALUES (?, ?, CONVERT(date, GETDATE()), CONVERT(time, GETDATE()), ?, ?)",
                [
                    $rentalId,
                    $adminId ?: null,
                    'Good',
                    'Returned by member via portal'
                ]
            );
            if ($ins === false) {
                throw new Exception('Failed to insert return record');
            }

            if ($bikeId > 0) {
                $updBike = sqlsrv_query($conn, "UPDATE Bike SET availability_status = 'Available' WHERE Bike_ID = ?", [$bikeId]);
                if ($updBike === false) {
                    throw new Exception('Failed to update bike availability');
                }
            }

            $newStatus = 'completed';
        } else {
            $upd = sqlsrv_query(
                $conn,
                "UPDATE Rentals SET status = 'Cancelled' WHERE Rental_ID = ?",
                [$rentalId]
            );
            if ($upd === false) {
                throw new Exception('Failed to cancel rental');
            }

            if ($bikeId > 0) {
                $updBike = sqlsrv_query($conn, "UPDATE Bike SET availability_status = 'Available' WHERE Bike_ID = ?", [$bikeId]);
                if ($updBike === false) {
                    throw new Exception('Failed to update bike availability');
                }
            }

            $newStatus = 'cancelled';
        }
    }

    sqlsrv_commit($conn);
    echo json_encode(['success' => true, 'status' => $newStatus]);

} catch (Exception $e) {
    if ($conn) {
        sqlsrv_rollback($conn);
    }
    echo json_encode(['success' => false, 'message' => 'Error ending rental']);
}

closeConnection($conn);
?>
