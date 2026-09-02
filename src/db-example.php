<<<<<<< HEAD
<?php
$servername = "localhost";
$username   = "root";
$password   = "deinpasswort";
$database   = "filamentlager";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
=======
<?php
$servername = "localhost";
$username   = "root";
$password   = "deinpasswort";
$database   = "filamentlager";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
>>>>>>> 3c22cdc (Initial commit)
