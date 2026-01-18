<?php
// Start session
session_start();

// Database configuration
$serverName = "localhost";
$database = "BikeRental";
$username = "";
$password = "";

$connectionOptions = array(
    "Database" => $database,
    "CharacterSet" => "UTF-8"
);

$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Set response header
header('Content-Type: application/json');

// Get POST data
$firstName = trim($_POST['firstName'] ?? '');
$lastName = trim($_POST['lastName'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirmPassword'] ?? '';
$contactNumber = trim($_POST['contactNumber'] ?? '');
$address = trim($_POST['address'] ?? '');
$agreeTerms = isset($_POST['agreeTerms']) ? $_POST['agreeTerms'] : 'false';

// Validate required fields
if (empty($firstName) || empty($lastName) || empty($username) || empty($email) || empty($password)) {
    echo json_encode([
        'success' => false,
        'message' => 'All required fields must be filled'
    ]);
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please enter a valid email address'
    ]);
    exit();
}

// Validate password strength
if (strlen($password) < 8) {
    echo json_encode([
        'success' => false,
        'message' => 'Password must be at least 8 characters long'
    ]);
    exit();
}

// Check if passwords match
if ($password !== $confirmPassword) {
    echo json_encode([
        'success' => false,
        'message' => 'Passwords do not match'
    ]);
    exit();
}

// Check if terms are agreed
if (!isset($_POST['agreeTerms'])) {
    echo json_encode([
        'success' => false,
        'message' => 'You must agree to the Terms & Conditions and Privacy Policy'
    ]);
    exit();
}

// Sanitize inputs
$firstName = htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8');
$lastName = htmlspecialchars($lastName, ENT_QUOTES, 'UTF-8');
$username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
$email = filter_var($email, FILTER_SANITIZE_EMAIL);
$contactNumber = htmlspecialchars($contactNumber, ENT_QUOTES, 'UTF-8');
$address = htmlspecialchars($address, ENT_QUOTES, 'UTF-8');

// Hash the password for security
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

try {
    // Call stored procedure to register member
    $sql = "EXEC sp_RegisterMember @Username = ?, @FirstName = ?, @LastName = ?, @ContactNumber = ?, @Email = ?, @Password = ?, @Address = ?";
    $params = array($username, $firstName, $lastName, $contactNumber, $email, $hashedPassword, $address);

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        $errors = sqlsrv_errors();
        $errorMessage = 'Registration failed';

        // Check for specific error messages
        if ($errors) {
            foreach ($errors as $error) {
                if (strpos($error['message'], 'Username already exists') !== false) {
                    $errorMessage = 'Username already exists. Please choose a different username.';
                } elseif (strpos($error['message'], 'Email already exists') !== false) {
                    $errorMessage = 'Email already exists. Please use a different email address.';
                }
            }
        }

        echo json_encode([
            'success' => false,
            'message' => $errorMessage
        ]);
        exit();
    }

    // Get the new member ID using stored procedure (avoid direct SQL)
    $getMemberStmt = sqlsrv_query($conn, "EXEC sp_GetMemberByEmail @Email = ?", array($email));

    if ($getMemberStmt && $memberData = sqlsrv_fetch_array($getMemberStmt, SQLSRV_FETCH_ASSOC)) {
        $memberId = $memberData['Member_ID'];

        // Auto-login the user after successful registration
        $_SESSION['user_id'] = $memberId;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = $firstName . ' ' . $lastName;
        $_SESSION['user_type'] = 'member';

        echo json_encode([
            'success' => true,
            'memberId' => $memberId,
            'email' => $email,
            'name' => $firstName . ' ' . $lastName,
            'userType' => 'member',
            'message' => 'Account created successfully! Welcome to BikeRental.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Registration completed but failed to retrieve member information'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred during registration. Please try again.'
    ]);
}

// Close connection
sqlsrv_close($conn);
?>