<?php
// Test database connection
$serverName = "localhost";
$database = "BikeRental";
$username = ""; // Empty for Windows Authentication
$password = ""; // Empty for Windows Authentication

$connectionOptions = array(
    "Database" => $database,
    "Uid" => $username,
    "PWD" => $password,
    "CharacterSet" => "UTF-8"
);

echo "<h2>Testing Database Connection</h2>";

$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    echo "<p style='color: red;'>❌ Connection failed!</p>";
    echo "<p>Error details:</p>";
    echo "<pre>";
    print_r(sqlsrv_errors());
    echo "</pre>";

    echo "<h3>Common Solutions:</h3>";
    echo "<ul>";
    echo "<li>Make sure SQL Server is running</li>";
    echo "<li>Check if the database 'BikeRental' exists</li>";
    echo "<li>Try different credentials:</li>";
    echo "<ul>";
    echo "<li>Username: 'sa', Password: '' (empty)</li>";
    echo "<li>Username: '', Password: '' (Windows Authentication)</li>";
    echo "<li>Username: 'your_windows_username', Password: ''</li>";
    echo "</ul>";
    echo "<li>Make sure SQL Server Browser service is running</li>";
    echo "</ul>";
} else {
    echo "<p style='color: green;'>✅ Connection successful!</p>";

    // Test a simple query via stored procedure
    $sql = 'EXEC dbo.sp_CountAdmins';
    $stmt = sqlsrv_query($conn, $sql);

    if ($stmt === false) {
        echo "<p style='color: red;'>❌ Query failed!</p>";
        echo "<pre>";
        print_r(sqlsrv_errors());
        echo "</pre>";
    } else {
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        echo "<p>Found " . $row['AdminCount'] . " admin(s) in database</p>";
        sqlsrv_free_stmt($stmt);
    }

    sqlsrv_close($conn);
}
?>