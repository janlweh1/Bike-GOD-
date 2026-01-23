<?php
// Update logged-in member's email address
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

$newEmail = isset($input['new_email']) ? trim($input['new_email']) : '';
$currentPassword = isset($input['current_password']) ? $input['current_password'] : '';

if ($newEmail === '' || $currentPassword === '') {
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    closeConnection($conn);
    exit();
}

if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
    closeConnection($conn);
    exit();
}

// Get current member password and email
$sql = 'SELECT email, password FROM Member WHERE Member_ID = ?';
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
    echo json_encode(['success' => false, 'message' => 'Incorrect password']);
    closeConnection($conn);
    exit();
}

// Ensure email is unique
$checkSql = 'SELECT COUNT(*) AS cnt FROM Member WHERE email = ? AND Member_ID <> ?';
$checkStmt = sqlsrv_query($conn, $checkSql, [$newEmail, $memberId]);
if ($checkStmt === false) {
    echo json_encode(['success' => false, 'message' => 'Validation query failed']);
    closeConnection($conn);
    exit();
}
$cntRow = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);
if ($cntRow && (int)$cntRow['cnt'] > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already in use']);
    closeConnection($conn);
    exit();
}

// Update email via stored procedure
$updSql = 'EXEC dbo.sp_UpdateMemberEmail @MemberID = ?, @Email = ?';
$updStmt = sqlsrv_query($conn, $updSql, [$memberId, $newEmail]);
if ($updStmt === false) {
    echo json_encode(['success' => false, 'message' => 'Failed to update email']);
    closeConnection($conn);
    exit();
}

$_SESSION['user_email'] = $newEmail;

echo json_encode(['success' => true, 'newEmail' => $newEmail]);

closeConnection($conn);
