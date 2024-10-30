<?php
// Logout script that sends the logout request to the rabbitmq server to delete token from the session for the user
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $client = new rabbitMQClient("testRabbitMQ.ini", "testServer");
    $request = [
        'type' => "logout",
        'session_token' => $_COOKIE['session'],
        'message' => "Logout Request"
    ];
    // Send the request to RabbitMQ server
    $response = $client->send_request($request);
    // Decode the JSON response from RabbitMQ
    $response = json_decode($response, true);
    if (isset($response['success']) && $response['success']) {
        header("Location: /index.html");
        exit;
    }
    // If logout fails for some reason.... return the response as JSON
    // Look I just copied this from my login.php. LOL
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);
}