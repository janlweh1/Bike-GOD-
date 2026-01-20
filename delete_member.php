<?php
// Delete a member if no rentals exist (admin only)
session_start();
header('Content-Type: application/json');

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

$memberId = isset($_POST['member_id']) ? (int)$_POST['member_id'] : 0;
if ($memberId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid member id']);
    closeConnection($conn);
    exit();
}

// Block delete if rentals exist
$rentalsStmt = sqlsrv_query($conn, 'SELECT COUNT(*) AS cnt FROM Rentals WHERE member_id = ?', [$memberId]);
if ($rentalsStmt === false) {
    echo json_encode(['success' => false, 'message' => 'Validation query failed']);
    closeConnection($conn);
    exit();
}
$rentalsRow = sqlsrv_fetch_array($rentalsStmt, SQLSRV_FETCH_ASSOC);
if ($rentalsRow && (int)$rentalsRow['cnt'] > 0) {
    echo json_encode(['success' => false, 'message' => 'Cannot delete member with existing rentals']);
    closeConnection($conn);
    exit();
}

// Delete member
$delStmt = sqlsrv_query($conn, 'DELETE FROM Member WHERE Member_ID = ?', [$memberId]);
if ($delStmt === false) {
    $err = sqlsrv_errors();
    $msg = 'Delete failed';
    if ($err && isset($err[0]['message'])) { $msg .= ': ' . $err[0]['message']; }
    echo json_encode(['success' => false, 'message' => $msg]);
    closeConnection($conn);
    exit();
}

echo json_encode(['success' => true]);
closeConnection($conn);
?>