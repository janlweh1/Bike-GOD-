<?php
// Return current member profile data as JSON for client-side profile (ahome.html)
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

$sql = "EXEC sp_GetMemberProfile @MemberID = ?";
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

$first = $row['first_name'] ?? '';
$last  = $row['last_name'] ?? '';
$fullName = trim($first . ' ' . $last);
$email = $row['email'] ?? '';
$phone = $row['contact_number'] ?? '';
$address = $row['address'] ?? '';
$photoUrl = $row['photo_url'] ?? '';

$joinDateLabel = '';
if (!empty($row['date_joined']) && $row['date_joined'] instanceof DateTimeInterface) {
    $joinDateLabel = 'Member since ' . $row['date_joined']->format('F Y');
}

$defaultAvatar = 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&h=400&fit=crop';

echo json_encode([
    'success'  => true,
    'fullName' => $fullName,
    'email'    => $email,
    'phone'    => $phone,
    'address'  => $address,
    'photoUrl' => $photoUrl !== '' ? $photoUrl : $defaultAvatar,
    'joinDate' => $joinDateLabel
]);

closeConnection($conn);
