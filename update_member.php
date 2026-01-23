<?php
// Update basic member fields (admin only)
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
$firstName = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
$lastName = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['contact_number']) ? trim($_POST['contact_number']) : '';

if ($memberId <= 0 || $firstName === '' || $lastName === '' || $email === '') {
    echo json_encode(['success' => false, 'message' => 'Missing or invalid fields']);
    closeConnection($conn);
    exit();
}

// Check email uniqueness (exclude current member) via stored procedure
$checkStmt = sqlsrv_query($conn, 'EXEC dbo.sp_CheckMemberEmailUnique @MemberID = ?, @Email = ?', [$memberId, $email]);
if ($checkStmt === false) {
    echo json_encode(['success' => false, 'message' => 'Validation query failed']);
    closeConnection($conn);
    exit();
}
$cntRow = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);
if ($cntRow && (int)$cntRow['Cnt'] > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already in use']);
    closeConnection($conn);
    exit();
}

// Perform update via stored procedure
$sql = 'EXEC dbo.sp_UpdateMemberBasic @MemberID = ?, @FirstName = ?, @LastName = ?, @Email = ?, @Phone = ?';
$params = [$memberId, $firstName, $lastName, $email, $phone];
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    $err = sqlsrv_errors();
    $msg = 'Update failed';
    if ($err && isset($err[0]['message'])) { $msg .= ': ' . $err[0]['message']; }
    echo json_encode(['success' => false, 'message' => $msg]);
    closeConnection($conn);
    exit();
}

echo json_encode(['success' => true]);
closeConnection($conn);
?>