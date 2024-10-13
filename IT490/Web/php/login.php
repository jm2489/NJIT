<?php
// Made a custom 403 page to deter rude fairytale creatures
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    header('Content-Type: image/gif');

    // Hehe
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
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // RabbitMQ client setup
    $client = new rabbitMQClient("testRabbitMQ.ini", "testServer");

    // Create the request
    $request = array();
    $request['type'] = "login";
    $request['username'] = $username;
    $request['password'] = $password;
    $request['message'] = "Login Request";
    
    // Send the request and get the response
    $response = $client->send_request($request);

    // Output the response (this will be shown in the HTML)
    echo "Client received response: <br>".PHP_EOL;
    echo "<pre>";
    print_r($response);
    echo "</pre>";
    
} else {
    // Handle invalid request method
    echo "Invalid request method.";
}
