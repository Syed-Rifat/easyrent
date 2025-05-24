<?php
// Session check and authentication
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Include database connection
require_once "../../database/config.php";

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid request method']);
    exit();
}

// Get and validate input data
$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$user_type = isset($_POST['user_type']) ? trim($_POST['user_type']) : '';
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

if ($user_id <= 0 || empty($full_name) || empty($email) || empty($user_type) || empty($status)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing required fields']);
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid email format']);
    exit();
}

// Check if email already exists for other users
$check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("si", $email, $user_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Email already exists']);
    exit();
}

// Update user
$update_query = "UPDATE users SET full_name = ?, email = ?, phone = ?, user_type = ?, status = ? WHERE id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("sssssi", $full_name, $email, $phone, $user_type, $status, $user_id);

if ($stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'User updated successfully']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to update user']);
} 