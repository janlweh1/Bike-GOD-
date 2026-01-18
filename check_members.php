<?php
// Check member data using stored procedures
$conn = sqlsrv_connect('localhost', ['Database' => 'BikeRental', 'CharacterSet' => 'UTF-8']);

if ($conn) {
    // Count members
    $result = sqlsrv_query($conn, 'EXEC sp_CountMembers');
    $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
    echo 'Members in database: ' . $row['count'] . PHP_EOL;

    // Show sample members
    if ($row['count'] > 0) {
        $result2 = sqlsrv_query($conn, 'EXEC sp_GetTopMembersEmails');
        while ($member = sqlsrv_fetch_array($result2, SQLSRV_FETCH_ASSOC)) {
            echo 'Member: ' . $member['email'] . ' (' . $member['first_name'] . ' ' . $member['last_name'] . ')' . PHP_EOL;
        }
    }

    sqlsrv_close($conn);
} else {
    echo 'Connection failed';
}
?>