<?php
// Start session
session_start();

// Database configuration
$serverName = "localhost"; // or your SQL Server address
$database = "BikeRental";
$username = ""; // Leave empty for Windows Authentication
$password = ""; // Leave empty for Windows Authentication

// Establish connection - try multiple methods
$conn = null;

// Method 1: SQL Server Authentication
$connectionOptions = array(
    "Database" => $database,
    "Uid" => $username,
    "PWD" => $password,
    "CharacterSet" => "UTF-8"
);

$conn = sqlsrv_connect($serverName, $connectionOptions);

// Method 2: If SQL Auth fails, try Windows Authentication
if ($conn === false) {
    $connectionOptions = array(
        "Database" => $database,
        "CharacterSet" => "UTF-8"
    );
    $conn = sqlsrv_connect($serverName, $connectionOptions);
}

// Set response header
header('Content-Type: application/json');

// Check connection
if ($conn === false) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    die();
}

// Get POST data
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// Validate input
if (empty($email) || empty($password)) {
    echo json_encode([
        'success' => false,
        'message' => 'Email and password are required'
    ]);
    exit();
}

// Sanitize email
$email = filter_var($email, FILTER_SANITIZE_EMAIL);

// Log incoming credentials (sanitized) for debugging
error_log('[login] incoming email/username=' . $email . ' length=' . strlen((string)$email));
error_log('[login] incoming password length=' . strlen((string)$password));

// First, check if user is an admin by username
$sqlAdmin = "EXEC sp_GetAdminByUsername @Username = ?";
$paramsAdmin = array($email);
$stmtAdmin = sqlsrv_query($conn, $sqlAdmin, $paramsAdmin);

if ($stmtAdmin === false) {
    echo json_encode([
        'success' => false,
        'message' => 'Query error'
    ]);
    exit();
}

if ($admin = sqlsrv_fetch_array($stmtAdmin, SQLSRV_FETCH_ASSOC)) {
    // Admin found, verify password
    // NOTE: In production, use password_verify() with hashed passwords
    if ($password === $admin['password']) {
        // Login successful
        $_SESSION['user_id'] = $admin['Admin_ID'];
        $_SESSION['user_email'] = $admin['username'];
        $_SESSION['user_name'] = $admin['full_name'];
        $_SESSION['user_type'] = 'admin';
        $_SESSION['user_role'] = $admin['role'];
        
        echo json_encode([
            'success' => true,
            'userId' => $admin['Admin_ID'],
            'email' => $admin['username'],
            'name' => $admin['full_name'],
            'userType' => 'admin',
            'role' => $admin['role'],
            'message' => 'Login successful'
        ]);
        exit();
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid username or password'
        ]);
        exit();
    }
}

// If not admin, check if user is a member by username
$sqlMemberUsername = "EXEC sp_GetMemberByUsername @Username = ?";
$paramsMemberUsername = array($email);
$stmtMemberUsername = sqlsrv_query($conn, $sqlMemberUsername, $paramsMemberUsername);

if ($stmtMemberUsername === false) {
    echo json_encode([
        'success' => false,
        'message' => 'Query error'
    ]);
    exit();
}

if ($member = sqlsrv_fetch_array($stmtMemberUsername, SQLSRV_FETCH_ASSOC)) {
    error_log('[login] member found by username=' . ($member['username'] ?? '')); 
    error_log('[login] password hash len=' . (isset($member['password']) ? strlen($member['password']) : 0));
    // Member found by username, verify password
    if (password_verify($password, $member['password'])) {
        // Login successful
        $_SESSION['user_id'] = $member['Member_ID'];
        $_SESSION['user_email'] = $member['email'];
        $_SESSION['user_name'] = $member['first_name'] . ' ' . $member['last_name'];
        $_SESSION['user_type'] = 'member';

        echo json_encode([
            'success' => true,
            'userId' => $member['Member_ID'],
            'email' => $member['email'],
            'name' => $member['first_name'] . ' ' . $member['last_name'],
            'userType' => 'member',
            'message' => 'Login successful'
        ]);
        exit();
    } else {
        error_log('[login] password verify failed for username');
        echo json_encode([
            'success' => false,
            'message' => 'Invalid username or password'
        ]);
        exit();
    }
}

// If not found by username, check if user is a member by email
$sqlMemberEmail = "EXEC sp_GetMemberByEmail @Email = ?";
$paramsMemberEmail = array($email);
$stmtMemberEmail = sqlsrv_query($conn, $sqlMemberEmail, $paramsMemberEmail);

if ($stmtMemberEmail === false) {
    echo json_encode([
        'success' => false,
        'message' => 'Query error'
    ]);
    exit();
}

if ($member = sqlsrv_fetch_array($stmtMemberEmail, SQLSRV_FETCH_ASSOC)) {
    error_log('[login] member found by email=' . ($member['email'] ?? '')); 
    error_log('[login] password hash len=' . (isset($member['password']) ? strlen($member['password']) : 0));
    // Member found by email, verify password
    if (password_verify($password, $member['password'])) {
        // Login successful
        $_SESSION['user_id'] = $member['Member_ID'];
        $_SESSION['user_email'] = $member['email'];
        $_SESSION['user_name'] = $member['first_name'] . ' ' . $member['last_name'];
        $_SESSION['user_type'] = 'member';

        echo json_encode([
            'success' => true,
            'userId' => $member['Member_ID'],
            'email' => $member['email'],
            'name' => $member['first_name'] . ' ' . $member['last_name'],
            'userType' => 'member',
            'message' => 'Login successful'
        ]);
        exit();
    } else {
        error_log('[login] password verify failed for email');
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email or password'
        ]);
        exit();
    }
}

// No user found
echo json_encode([
    'success' => false,
    'message' => 'Invalid email or password'
]);

// Close connection
sqlsrv_close($conn);
?>
