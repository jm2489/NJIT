<?php

/**
 * @author jm2489
 * @date 2024-10-25
 * @description Database server will be running a php script to handle all security related information. The RMQ will just need to send the request.
 */

try {
    require('config.php');
} catch (Error $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Required configuration file not found or caused an error.',
        'details' => $e->getMessage()
    ]);
    exit;
}

function dbConnect()
{
    $pdo = new PDO(DBHOST, DBUSER, DBPASSWORD);
    return $pdo;
}

// Create a socket
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if ($socket === false) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to create socket.',
        'details' => socket_strerror(socket_last_error())
    ]);
    exit;
}

// Bind socket to an address and port. localhost:8888
$host = '127.0.0.1';
$port = 8888;
if (!socket_bind($socket, $host, $port)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to bind socket to address and port.',
        'details' => socket_strerror(socket_last_error())
    ]);
    exit;
}

// Listen for incoming connections
if (!socket_listen($socket)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to listen for incoming connections.',
        'details' => socket_strerror(socket_last_error())
    ]);
    exit;
}

// Accept incoming connections
$connection = socket_accept($socket);
if ($connection === false) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to accept incoming connection.',
        'details' => socket_strerror(socket_last_error())
    ]);
    exit;
}

// Read data from client
$data = socket_read($connection, 1024);
echo "Recieved data: " . $data;

// Close the socket
socket_close($socket);
socket_close($client);