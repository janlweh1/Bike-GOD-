<?php
// Update logged-in member's profile information (self-service)
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

// Support JSON or form-encoded
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$username = isset($input['username']) ? trim($input['username']) : '';
$fullName = isset($input['full_name']) ? trim($input['full_name']) : '';
$email    = isset($input['email']) ? trim($input['email']) : '';
$phone    = isset($input['phone']) ? trim($input['phone']) : '';
$address  = isset($input['address']) ? trim($input['address']) : '';

if ($fullName === '') {
    echo json_encode(['success' => false, 'message' => 'Name is required']);
    closeConnection($conn);
    exit();
}

// Split full name into first and last name (simple heuristic)
$parts = preg_split('/\s+/', $fullName);
$firstName = $parts[0];
$lastName  = '';
if (count($parts) > 1) {
    array_shift($parts);
    $lastName = implode(' ', $parts);
}

// If email is provided, validate and enforce uniqueness
if ($email !== '') {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
        closeConnection($conn);
        exit();
    }

    // Check email uniqueness among other members via stored procedure
    $checkSql = 'EXEC dbo.sp_CheckMemberEmailUnique @MemberID = ?, @Email = ?';
    $checkStmt = sqlsrv_query($conn, $checkSql, [$memberId, $email]);
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
}

// If username is provided, ensure it's unique among other members
if ($username !== '') {
    $checkUserSql = 'EXEC dbo.sp_CheckMemberUsernameUnique @MemberID = ?, @Username = ?';
    $checkUserStmt = sqlsrv_query($conn, $checkUserSql, [$memberId, $username]);
    if ($checkUserStmt === false) {
        echo json_encode(['success' => false, 'message' => 'Username validation failed']);
        closeConnection($conn);
        exit();
    }
    $userRow = sqlsrv_fetch_array($checkUserStmt, SQLSRV_FETCH_ASSOC);
    if ($userRow && (int)$userRow['Cnt'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Username already in use']);
        closeConnection($conn);
        exit();
    }
}

// Update via stored procedure (profile fields)
$updateSql = 'EXEC dbo.sp_UpdateMemberProfile @MemberID = ?, @Username = ?, @FirstName = ?, @LastName = ?, @Email = ?, @Phone = ?, @Address = ?';
$updateStmt = sqlsrv_query($conn, $updateSql, [
    $memberId,
    $username !== '' ? $username : null,
    $firstName,
    $lastName,
    $email !== '' ? $email : null,
    $phone !== '' ? $phone : null,
    $address !== '' ? $address : null
]);

if ($updateStmt === false) {
    $err = sqlsrv_errors();
    $msg = 'Update failed';
    if ($err && isset($err[0]['message'])) {
        $msg .= ': ' . $err[0]['message'];
    }
    echo json_encode(['success' => false, 'message' => $msg]);
    closeConnection($conn);
    exit();
}

// Refresh session basics if available
if ($email !== '') {
    $_SESSION['user_email'] = $email;
}
$_SESSION['user_name']  = $fullName;

echo json_encode([
    'success'  => true,
    'username' => $username,
    'fullName' => $fullName,
    'email'    => $email,
    'phone'    => $phone,
    'address'  => $address
]);

closeConnection($conn);
