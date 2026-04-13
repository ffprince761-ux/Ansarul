<?php
// Database Configuration for Hostinger
// UPDATE THESE VALUES WITH YOUR ACTUAL DATABASE DETAILS

$host = "localhost"; // Usually localhost on Hostinger
$dbname = "bizinote_db"; // Your database name
$username = "bizinote_user"; // Your database username
$password = "YOUR_ACTUAL_PASSWORD"; // Your database password

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");

// Enable error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

echo "Database connected successfully!";
?>
