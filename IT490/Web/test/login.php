<?php
// Disable output buffering and set the content type to JSON
ob_end_clean();
header('Content-Type: application/json');

// Simulate user authentication logic
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// Example login logic (replace with real authentication in production)
if ($username === 'admin' && $password === 'password') {
    // Successful login
    echo json_encode([
        "success" => true,
        "message" => "Login successful!"
    ]);
} else {
    // Failed login
    echo json_encode([
        "success" => false,
        "message" => "Invalid username or password."
    ]);
}
