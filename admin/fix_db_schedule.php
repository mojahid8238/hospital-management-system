<?php
require_once '../includes/db.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h1>Database Fixer</h1>";

// 1. Check current type
$result = $conn->query("SHOW COLUMNS FROM doctors LIKE 'schedule'");
$row = $result->fetch_assoc();
$current_type = $row['Type'];
echo "<p>Current 'schedule' column type: <strong>$current_type</strong></p>";

// 2. Fix if needed
if (strpos($current_type, 'varchar') === false) {
    echo "<p>Attempting to change type to VARCHAR(255)...</p>";
    $sql = "ALTER TABLE doctors MODIFY schedule VARCHAR(255)";
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color: green;'><strong>SUCCESS: Table 'doctors' altered successfully.</strong></p>";
    } else {
        echo "<p style='color: red;'><strong>ERROR: " . $conn->error . "</strong></p>";
    }
} else {
    echo "<p style='color: blue;'><strong>No Change Needed: Column is already VARCHAR.</strong></p>";
}

echo "<p>Please return to the application and try updating the schedule again.</p>";
$conn->close();
?>
