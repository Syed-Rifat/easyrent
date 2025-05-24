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
$full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$user_type = isset($_POST['user_type']) ? trim($_POST['user_type']) : '';
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

if (empty($full_name) || empty($email) || empty($password) || empty($user_type) || empty($status)) {
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

// Check if email already exists
$check_query = "SELECT id FROM users WHERE email = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("s", $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Email already exists']);
    exit();
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert new user
$insert_query = "INSERT INTO users (full_name, email, phone, password, user_type, status, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, NOW())";
$stmt = $conn->prepare($insert_query);
$stmt->bind_param("ssssss", $full_name, $email, $phone, $hashed_password, $user_type, $status);

if ($stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'User created successfully',
        'user_id' => $conn->insert_id
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to create user']);
} 