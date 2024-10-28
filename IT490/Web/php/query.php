<?php

require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $client = new rabbitMQClient("testRabbitMQ.ini", "testServer");
        $request = [
            'type' => "krakenQuery",
            'message' => "KrakenQuery"
        ];

        // Send the request to RabbitMQ server
        $response = $client->send_request($request);

        // Check if the response is already JSON-encoded
        if (is_string($response) && json_decode($response) !== null) {
            // If it's a valid JSON string, print it as is
            echo $response;
        } else {
            // Otherwise, encode it to JSON
            echo json_encode($response);
        }
    } catch (Exception $e) {
        echo json_encode([
            "success" => false,
            "message" => "RabbitMQ error: " . $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);
}
