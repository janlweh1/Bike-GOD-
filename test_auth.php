<?php
// Test authentication logic directly

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
    die("Connection failed");
}

// Test cases
$testCases = [
    ['input' => 'admin', 'password' => 'admin123', 'expected' => 'admin_success'],
    ['input' => 'admin', 'password' => 'wrongpass', 'expected' => 'admin_fail'],
    ['input' => 'manager', 'password' => 'manager123', 'expected' => 'admin_success'],
    ['input' => 'john_a', 'password' => 'password', 'expected' => 'member_disabled'],
    ['input' => 'john.anderson@email.com', 'password' => 'password', 'expected' => 'member_disabled'],
    ['input' => 'nonexistent', 'password' => 'password', 'expected' => 'not_found']
];

foreach ($testCases as $i => $test) {
    echo "Test " . ($i + 1) . ": {$test['input']} / {$test['password']}\n";

    // Check admin by username via stored procedure
    $sqlAdmin = "EXEC dbo.sp_GetAdminByUsername @Username = ?";
    $paramsAdmin = array($test['input']);
    $stmtAdmin = sqlsrv_query($conn, $sqlAdmin, $paramsAdmin);

    if ($stmtAdmin && $admin = sqlsrv_fetch_array($stmtAdmin, SQLSRV_FETCH_ASSOC)) {
        if ($test['password'] === $admin['password']) {
            echo "✅ Admin login SUCCESS (username: {$admin['username']})\n";
        } else {
            echo "❌ Admin login FAILED - wrong password\n";
        }
    } else {
        // Check member by username via stored procedure
        $sqlMemberUsername = "EXEC dbo.sp_GetMemberByUsername @Username = ?";
        $paramsMemberUsername = array($test['input']);
        $stmtMemberUsername = sqlsrv_query($conn, $sqlMemberUsername, $paramsMemberUsername);

        if ($stmtMemberUsername && $member = sqlsrv_fetch_array($stmtMemberUsername, SQLSRV_FETCH_ASSOC)) {
            echo "🚫 Member login DISABLED (username: {$member['username']})\n";
        } else {
            // Check member by email via stored procedure
            $sqlMemberEmail = "EXEC dbo.sp_GetMemberByEmail @Email = ?";
            $paramsMemberEmail = array($test['input']);
            $stmtMemberEmail = sqlsrv_query($conn, $sqlMemberEmail, $paramsMemberEmail);

            if ($stmtMemberEmail && $member = sqlsrv_fetch_array($stmtMemberEmail, SQLSRV_FETCH_ASSOC)) {
                echo "🚫 Member login DISABLED (email: {$member['email']})\n";
            } else {
                echo "❌ User not found\n";
            }
        }
    }

    echo "\n";
}

sqlsrv_close($conn);
?>