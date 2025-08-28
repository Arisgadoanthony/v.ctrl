<?php
// Embedded db_connection.php content
$conn = new mysqli("127.0.0.1", "root", "", "voting_website", 3307);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>
