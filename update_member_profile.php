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

    // Check email uniqueness among other members
    $checkSql = 'SELECT COUNT(*) AS cnt FROM Member WHERE email = ? AND Member_ID <> ?';
    $checkStmt = sqlsrv_query($conn, $checkSql, [$email, $memberId]);
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
}

// If username is provided, ensure it's unique among other members
if ($username !== '') {
    $checkUserSql = 'SELECT COUNT(*) AS cnt FROM Member WHERE username = ? AND Member_ID <> ?';
    $checkUserStmt = sqlsrv_query($conn, $checkUserSql, [$username, $memberId]);
    if ($checkUserStmt === false) {
        echo json_encode(['success' => false, 'message' => 'Username validation failed']);
        closeConnection($conn);
        exit();
    }
    $userRow = sqlsrv_fetch_array($checkUserStmt, SQLSRV_FETCH_ASSOC);
    if ($userRow && (int)$userRow['cnt'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Username already in use']);
        closeConnection($conn);
        exit();
    }
}

// Build dynamic update depending on whether email/username were supplied
$updateFields = '';
$params = [];

if ($username !== '') {
    $updateFields .= 'username = ?';
    $params[] = $username;
}

if ($updateFields !== '') {
    $updateFields .= ', ';
}

if ($email !== '') {
    $updateFields .= 'first_name = ?, last_name = ?, email = ?, contact_number = ?, address = ?';
    array_push($params, $firstName, $lastName, $email, $phone, $address);
} else {
    $updateFields .= 'first_name = ?, last_name = ?, contact_number = ?, address = ?';
    array_push($params, $firstName, $lastName, $phone, $address);
}

$updateSql = 'UPDATE Member SET ' . $updateFields . ' WHERE Member_ID = ?';
$params[] = $memberId;
$updateStmt = sqlsrv_query($conn, $updateSql, $params);

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
