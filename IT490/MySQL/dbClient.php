<?php
// This is for testing and learning purposes. Created by chatGPT
// Define the server's IP address and port
$server_ip = '127.0.0.1';
$server_port = 8888;

// The SQL query to send
$query = "SELECT * FROM users WHERE username = 'steve'";

// Create the JSON payload
$data = json_encode(['query' => $query]);

// Create a socket connection
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if ($socket === false) {
    echo "Failed to create socket: " . socket_strerror(socket_last_error()) . "\n";
    exit;
}

// Connect to the server
$result = socket_connect($socket, $server_ip, $server_port);
if ($result === false) {
    echo "Failed to connect to server: " . socket_strerror(socket_last_error($socket)) . "\n";
    socket_close($socket);
    exit;
}

// Send the JSON payload to the server with the delimiter
if (socket_write($socket, $data, strlen($data)) === false) {
    echo "Failed to send data: " . socket_strerror(socket_last_error($socket)) . "\n";
    socket_close($socket);
    exit;
}

// Read the response from the server
$response = '';
while ($chunk = socket_read($socket, 2048)) {
    $response .= $chunk;
    // Stop reading if we encounter a null character
    if (strpos($chunk, "\0") !== false) {
        break;
    }
}

// Trim the null character from the response
$response = rtrim($response, "\0");

// Check if the response was received
if ($response === '') {
    echo "No response from server.\n";
} else {
    // Decode and display the response
    $response = json_decode($response);
    echo json_encode($response);
}

// Close the socket connection
socket_close($socket);
?>
