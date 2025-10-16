<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

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

// Basic file validation
if ($file['error'] !== UPLOAD_ERR_OK) {
    $response['message'] = 'File upload error: ' . $file['error'];
    echo json_encode($response);
    exit();
}

$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($file['type'], $allowed_types)) {
    $response['message'] = 'Invalid file type. Only JPG, PNG, and GIF are allowed.';
    echo json_encode($response);
    exit();
}

if ($file['size'] > 2 * 1024 * 1024) { // 2MB limit
    $response['message'] = 'File size exceeds 2MB limit.';
    echo json_encode($response);
    exit();
}

$upload_dir = '../assets/images/profile_pics/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$new_file_name = $role . '_' . $user_id . '.' . $file_extension;
$target_file = $upload_dir . $new_file_name;

if (move_uploaded_file($file['tmp_name'], $target_file)) {
    // Update database with new profile picture path
    $relative_path = 'assets/images/profile_pics/' . $new_file_name;
    $table = '';
    if ($role === 'patient') {
        $table = 'patients';
    } elseif ($role === 'doctor') {
        $table = 'doctors';
    } elseif ($role === 'admin') {
        $table = 'admin';
    }

    if ($table) {
        $stmt = $conn->prepare("UPDATE $table SET profile_pic = ? WHERE user_id = ?");
        $stmt->bind_param("si", $relative_path, $user_id);
        if ($stmt->execute()) {
            $_SESSION['profile_pic'] = $relative_path;
            $response['success'] = true;
            $response['message'] = 'Profile picture updated successfully.';
            $response['profile_pic_path'] = $relative_path;
        } else {
            $response['message'] = 'Database update failed: ' . $conn->error;
        }
        $stmt->close();
    } else {
        $response['message'] = 'Invalid user role.';
    }
} else {
    $response['message'] = 'Failed to move uploaded file.';
}

$conn->close();
echo json_encode($response);
?>