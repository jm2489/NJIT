<?php
require_once('validater.php');

// Validate session
$response = validateSession();
$responseArray = json_decode($response, true);

// Continue with page content...
if (is_array($responseArray) && isset($responseArray['success']) && $responseArray['success']) {
    $username = $responseArray['username'];
    echo "Welcome, " . htmlspecialchars($username) . "!";
} else {
    // Handle invalid or unsuccessful response
    echo "Session validation failed.";
}

require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $client = new rabbitMQClient("testRabbitMQ.ini", "testServer");

    $request = [
        'type' => "krakenQuery",
        'message' => "Kraken API Query"
    ];

    // Send the request to RabbitMQ server
    $response = $client->send_request($request);

    // Decode the JSON response from RabbitMQ
    echo json_encode($response);

} else {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);
}
