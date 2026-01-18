<?php
// Database configuration file

// SQL Server configuration
$serverName = "localhost"; // Change this to your SQL Server address
$database = "BikeRental";

// Connection options
$connectionOptions = array(
    "Database" => $database,
    "CharacterSet" => "UTF-8",
    "TrustServerCertificate" => true
);

// Function to get database connection
function getConnection() {
    global $serverName, $connectionOptions;
    
    $conn = sqlsrv_connect($serverName, $connectionOptions);
    
    if ($conn === false) {
        return null;
    }
    
    return $conn;
}

// Function to close database connection
function closeConnection($conn) {
    if ($conn !== null) {
        sqlsrv_close($conn);
    }
}
?>
