<?php
// Check and update member data using stored procedures
$conn = sqlsrv_connect('localhost', ['Database' => 'BikeRental', 'CharacterSet' => 'UTF-8']);

if ($conn) {
    echo "Current members in database:\n";
    $result = sqlsrv_query($conn, 'EXEC sp_ListMembersBasic');
    while ($member = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
        echo "ID: {$member['Member_ID']}, Name: {$member['first_name']} {$member['last_name']}, Email: {$member['email']}, Username: " . ($member['username'] ?? 'NULL') . "\n";
    }

    echo "\nUpdating members with usernames...\n";

    // Update existing members with usernames
    $updates = [
        1 => 'john_a',
        2 => 'sarah_j',
        3 => 'mike_d',
        4 => 'emily_w'
    ];

    foreach ($updates as $id => $username) {
        $sql = "EXEC sp_UpdateMemberUsername @MemberID = ?, @Username = ?";
        $params = array($id, $username);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt) {
            echo "Updated Member ID $id with username '$username'\n";
        } else {
            echo "Failed to update Member ID $id\n";
        }
    }

    echo "\nUpdated members:\n";
    $result = sqlsrv_query($conn, 'EXEC sp_ListMembersBasic');
    while ($member = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
        echo "ID: {$member['Member_ID']}, Name: {$member['first_name']} {$member['last_name']}, Email: {$member['email']}, Username: {$member['username']}\n";
    }

    sqlsrv_close($conn);
} else {
    echo 'Connection failed';
}
?>