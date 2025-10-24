<?php
$servername = "localhost";
$username = "root"; // Replace with your database username
$password = "root";     // Replace with your database password
$dbname = "hospital_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database Connection Failed: ' . $conn->connect_error]);
    exit();
}
// echo "Connected successfully"; // For testing, can be removed later
?>