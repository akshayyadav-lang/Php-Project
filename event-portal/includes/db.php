<?php
/**
 * Database Connection
 * Connects to Local MySQL database
 */

$host = 'localhost';
$user = 'root';
$password = 'root';
$database = 'event_portal';

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to utf8
mysqli_set_charset($conn, "utf8");

