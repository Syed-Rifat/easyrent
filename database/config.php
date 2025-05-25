<?php
// Database Configuration
$servername = "localhost";
$username = "root";  // Your database username
$password = "";      // Your database password
$dbname = "easyrent";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4"); 
