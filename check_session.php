<?php
// Check if user is logged in
session_start();

header('Content-Type: application/json');

if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
    echo json_encode([
        'success' => true,
        'loggedIn' => true,
        'userId' => $_SESSION['user_id'],
        'email' => $_SESSION['user_email'],
        'name' => $_SESSION['user_name'],
        'userType' => $_SESSION['user_type']
    ]);
} else {
    echo json_encode([
        'success' => true,
        'loggedIn' => false
    ]);
}
?>
