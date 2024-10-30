<?php
// Custom 403 page to deter unauthorized access from fairytale creatures
// This is an 'action' type php file. Meaning it won't be page a user 'lands' on.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    header('Content-Type: image/gif');
    // Serve a fun GIF or fallback message.. Hehehe
    $gifPath = __DIR__ . '/../media/swamp.gif';
    if (file_exists($gifPath)) {
        readfile($gifPath);
    } else {
        echo "This is where I would have my GIF.... IF I HAD ONE!";
    }
    exit;
}

require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $client = new rabbitMQClient("testRabbitMQ.ini", "testServer");
        $request = [
            'type' => "krakenQuery",
            'transaction' => "query"
        ];
        $response = $client->send_request($request);
        // Check if the response is already JSON-encoded. If it's a valid JSON string, print it as is.
        if (is_string($response) && json_decode($response) !== null) {
            echo $response;
        } else {
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
