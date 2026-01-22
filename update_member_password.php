<?php
// Update logged-in member's password
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'member' || !isset($_SESSION['user_id'])) {
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

$memberId = (int)$_SESSION['user_id'];

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$currentPassword = isset($input['current_password']) ? $input['current_password'] : '';
$newPassword     = isset($input['new_password']) ? $input['new_password'] : '';

if ($currentPassword === '' || $newPassword === '') {
    echo json_encode(['success' => false, 'message' => 'Current and new password are required']);
    closeConnection($conn);
    exit();
}

if (strlen($newPassword) < 8) {
    echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters long']);
    closeConnection($conn);
    exit();
}

$sql = 'SELECT password FROM Member WHERE Member_ID = ?';
$stmt = sqlsrv_query($conn, $sql, [$memberId]);
if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Query failed']);
    closeConnection($conn);
    exit();
}
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Member not found']);
    closeConnection($conn);
    exit();
}

$storedHash = $row['password'];
if (!password_verify($currentPassword, $storedHash)) {
    echo json_encode(['success' => false, 'message' => 'Incorrect current password']);
    closeConnection($conn);
    exit();
}

$newHash = password_hash($newPassword, PASSWORD_DEFAULT);
$updSql = 'UPDATE Member SET password = ? WHERE Member_ID = ?';
$updStmt = sqlsrv_query($conn, $updSql, [$newHash, $memberId]);
if ($updStmt === false) {
    echo json_encode(['success' => false, 'message' => 'Failed to update password']);
    closeConnection($conn);
    exit();
}

echo json_encode(['success' => true]);

closeConnection($conn);
