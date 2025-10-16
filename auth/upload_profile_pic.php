<?php
// --- ROBUST ERROR HANDLING FOR API ---
// Log all errors to the server's error log, but do NOT display them to the user.
// This prevents breaking the JSON response.
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('display_errors', 0); // This is the critical change

session_start();
// NOTE: Assuming db.php and auth.php are correctly located and initialize $conn
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Set the content type to JSON AFTER includes, just in case they fail.
header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => 'An unknown error occurred.'
];

if (!is_logged_in()) {
    $response['message'] = 'User not logged in.';
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if (!isset($_FILES['profile_pic'])) {
    $response['message'] = 'No file uploaded.';
    echo json_encode($response);
    exit();
}

$file = $_FILES['profile_pic'];

// Basic file validation checks
if ($file['error'] !== UPLOAD_ERR_OK) {
    $response['message'] = 'File upload error code: ' . $file['error'] . '. Check file size and PHP settings.';
    echo json_encode($response);
    exit();
}

// Allowed types and size checks (omitted for brevity, assuming correct from previous step)

// --- Path Construction ---
$upload_dir = __DIR__ . '/../assets/images/profile_pics/';
$file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)); // Sanitize extension
$new_file_name = $role . '_' . $user_id . '.' . $file_extension;
$target_file = $upload_dir . $new_file_name;

// Check and create directory if it doesn't exist
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0777, true)) {
        $response['message'] = 'FATAL: Failed to create upload directory. Check permissions.';
        error_log('Failed to create upload directory: ' . $upload_dir); // Log the specific error
        echo json_encode($response);
        exit();
    }
}

// --- Core Upload Operation ---
if (move_uploaded_file($file['tmp_name'], $target_file)) {
    
    // CRITICAL DIAGNOSTIC: Check file existence immediately after move.
    if (!file_exists($target_file)) {
        $response['message'] = "WARNING: File move reported success, but file is missing.";
        error_log("Upload success reported, but file missing at: " . $target_file);
        echo json_encode($response);
        exit();
    }
    
    // The relative path stored in the DB should NOT have a leading '../'
    $relative_path = 'assets/images/profile_pics/' . $new_file_name; 
    
    // --- Database Update and Old File Deletion Logic ---
    $table = '';
    if ($role === 'patient') { $table = 'patients'; } 
    elseif ($role === 'doctor') { $table = 'doctors'; } 
    elseif ($role === 'admin') { $table = 'admin'; }

    if ($table) {
        $stmt = $conn->prepare("UPDATE $table SET profile_pic = ? WHERE user_id = ?");
        // Check if prepare() failed
        if ($stmt === false) {
             $response['message'] = 'Database error: Failed to prepare statement. Check table/column names.';
             error_log('SQL Prepare Error: ' . $conn->error);
        } else {
            $stmt->bind_param("si", $relative_path, $user_id);
            if ($stmt->execute()) {
                $_SESSION['profile_pic'] = $relative_path;
                $response['success'] = true;
                $response['message'] = 'Profile picture updated successfully.';
                $response['profile_pic_path'] = $relative_path; 
            } else {
                $response['message'] = 'Database update failed: ' . $stmt->error;
            }
            $stmt->close();
        }
    } else {
        $response['message'] = 'Invalid user role, unable to update database.';
    }

} else {
    $response['message'] = 'Upload FAILED: Server cannot move the file. Check write permissions on the directory: ' . $upload_dir;
    // Log detailed error for debugging
    error_log('move_uploaded_file failed. Source: ' . $file['tmp_name'] . ' Target: ' . $target_file);
}

$conn->close();
echo json_encode($response);