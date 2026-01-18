<?php
// Test profile page functionality
echo "<h1>Profile Page Test</h1>";

// Test 1: Check if profile.php exists and is accessible
echo "<h2>Test 1: File Access</h2>";
if (file_exists('profile.php')) {
    echo "✅ profile.php exists<br>";
} else {
    echo "❌ profile.php not found<br>";
}

// Test 2: Check database connection
echo "<h2>Test 2: Database Connection</h2>";
$serverName = "localhost";
$database = "BikeRental";
$connectionOptions = array(
    "Database" => $database,
    "CharacterSet" => "UTF-8"
);

$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn) {
    echo "✅ Database connection successful<br>";

    // Test admin query
    $sql = "SELECT Admin_ID, username, full_name FROM Admin WHERE username = 'admin'";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt && $admin = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        echo "✅ Admin data accessible: " . $admin['full_name'] . "<br>";
    } else {
        echo "❌ Admin data not accessible<br>";
    }

    // Test member query
    $sql = "SELECT Member_ID, username, first_name, last_name FROM Member WHERE username = 'john_a'";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt && $member = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        echo "✅ Member data accessible: " . $member['first_name'] . " " . $member['last_name'] . "<br>";
    } else {
        echo "❌ Member data not accessible<br>";
    }

    sqlsrv_close($conn);
} else {
    echo "❌ Database connection failed<br>";
}

// Test 3: Check supporting files
echo "<h2>Test 3: Supporting Files</h2>";
$files = ['get_admin_stats.php', 'get_member_stats.php', 'logout.js', 'ahome.css'];
foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ $file not found<br>";
    }
}

echo "<h2>Access Profile Page</h2>";
echo "<p><a href='profile.php' target='_blank'>Click here to test the profile page</a></p>";
echo "<p><strong>Note:</strong> You need to be logged in to access the profile page. Try logging in first at <a href='login.html' target='_blank'>login.html</a></p>";
?>