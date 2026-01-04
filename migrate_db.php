<?php
require_once 'includes/db.php';

echo "Starting database migration...<br>";

// 1. Add is_video to appointments
$check_column = $conn->query("SHOW COLUMNS FROM appointments LIKE 'is_video'");
if ($check_column->num_rows == 0) {
    if ($conn->query("ALTER TABLE appointments ADD COLUMN is_video TINYINT(1) DEFAULT 0 AFTER type")) {
        echo "Added 'is_video' column to 'appointments' table.<br>";
    } else {
        echo "Error adding 'is_video' column: " . $conn->error . "<br>";
    }
} else {
    echo "'is_video' column already exists in 'appointments' table.<br>";
}

// 2. Ensure video_calls table is correct
$check_table = $conn->query("SHOW TABLES LIKE 'video_calls'");
if ($check_table->num_rows == 0) {
    $create_table = "CREATE TABLE video_calls (
        id INT AUTO_INCREMENT PRIMARY KEY,
        caller_id INT NOT NULL,
        receiver_id INT NOT NULL,
        appointment_id INT DEFAULT NULL,
        start_time DATETIME NOT NULL,
        end_time DATETIME DEFAULT NULL,
        status ENUM('scheduled', 'in_progress', 'completed', 'cancelled', 'failed') DEFAULT 'scheduled',
        meeting_link VARCHAR(255) NOT NULL,
        room_name VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (caller_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
    )";
    if ($conn->query($create_table)) {
        echo "Created 'video_calls' table.<br>";
    } else {
        echo "Error creating 'video_calls' table: " . $conn->error . "<br>";
    }
} else {
    // Check for room_name column in existing table
    $check_room_name = $conn->query("SHOW COLUMNS FROM video_calls LIKE 'room_name'");
    if ($check_room_name->num_rows == 0) {
        if ($conn->query("ALTER TABLE video_calls ADD COLUMN room_name VARCHAR(255) NOT NULL AFTER meeting_link")) {
            echo "Added 'room_name' column to 'video_calls' table.<br>";
        } else {
            echo "Error adding 'room_name' column: " . $conn->error . "<br>";
        }
    } else {
        echo "'room_name' column already exists in 'video_calls' table.<br>";
    }
}

// 3. Ensure prescriptions table exists and has new columns
$check_table = $conn->query("SHOW TABLES LIKE 'prescriptions'");
if ($check_table->num_rows == 0) {
    $create_table = "CREATE TABLE prescriptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        appointment_id INT NOT NULL,
        doctor_id INT NOT NULL,
        patient_id INT NOT NULL,
        content TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        med_name VARCHAR(255),
        med_dosage VARCHAR(100),
        med_freq VARCHAR(100),
        med_duration VARCHAR(100),
        FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
        FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
        FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
    )";
    if ($conn->query($create_table)) {
        echo "Created 'prescriptions' table.<br>";
    } else {
        echo "Error creating 'prescriptions' table: " . $conn->error . "<br>";
    }
} else {
    // Check for new columns
    $cols_to_add = [
        'med_name' => "VARCHAR(255)",
        'med_dosage' => "VARCHAR(100)",
        'med_freq' => "VARCHAR(100)",
        'med_duration' => "VARCHAR(100)"
    ];
    foreach ($cols_to_add as $col => $type) {
        $check_col = $conn->query("SHOW COLUMNS FROM prescriptions LIKE '$col'");
        if ($check_col->num_rows == 0) {
            $conn->query("ALTER TABLE prescriptions ADD COLUMN $col $type");
            echo "Added '$col' to 'prescriptions' table.<br>";
        }
    }
}

// 4. Ensure prescription_items table exists
$check_table = $conn->query("SHOW TABLES LIKE 'prescription_items'");
if ($check_table->num_rows == 0) {
    $create_table = "CREATE TABLE prescription_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        prescription_id INT NOT NULL,
        medicine_name VARCHAR(255) NOT NULL,
        dosage VARCHAR(100),
        frequency VARCHAR(100),
        duration VARCHAR(100),
        FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE CASCADE
    )";
    if ($conn->query($create_table)) {
        echo "Created 'prescription_items' table.<br>";
    } else {
        echo "Error creating 'prescription_items' table: " . $conn->error . "<br>";
    }
}

echo "Migration finished.";
?>
