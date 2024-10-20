<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');
$home = '/index.html';

// Function to validate the session token
function validateSession() {
    global $home;
    if (!isset($_COOKIE['session'])) {
        // No session token found; redirect to login
        header('Location: ' . $home);
        exit;
    }

    $sessionToken = $_COOKIE['session'];

    // Set up RabbitMQ client
    $client = new rabbitMQClient("testRabbitMQ.ini", "testServer");

    // Create a request to validate the session token
    $request = [
        'type' => "validate_session",
        'session_token' => $sessionToken,
        'message' => "Session Validation Request"
    ];

    // Send the request to the RabbitMQ server
    try {
        $response = $client->send_request($request);
        $response = json_decode($response, true);

        // Check if the response indicates a valid session
        if (isset($response['success']) && $response['success']) {
            // Session is valid, return the username
            return $response['username'];
        } else {
            // Session is invalid or expired; redirect to login
            header('Location: ' . $home);
            exit;
        }
    } catch (Exception $e) {
        // Handle RabbitMQ communication errors
        error_log('RabbitMQ error: ' . $e->getMessage());
        header('Location: ' . $home);
        exit;
    }
}
