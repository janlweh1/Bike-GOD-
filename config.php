<?php
// Central DB configuration used by endpoints
// Configure via environment variables or edit defaults below.

$serverName = getenv('DB_SERVER') ?: '.'; // e.g., 'localhost\\SQLEXPRESS'
$connectionOptions = [
    'Database' => getenv('DB_NAME') ?: 'BikeRental',
    'TrustServerCertificate' => true,
];
if ($uid = getenv('DB_USER')) { $connectionOptions['UID'] = $uid; }
if ($pwd = getenv('DB_PASS')) { $connectionOptions['PWD'] = $pwd; }

?>
