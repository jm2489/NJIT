<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Set HTTP status to 403 Forbidden
    http_response_code(403);

    // Set content type to GIF
    header('Content-Type: image/gif');

    // Specify the path to your funny GIF
    $gifPath = __DIR__ . '/../media/swamp.gif';

    // Check if the file exists
    if (file_exists($gifPath)) {
        // Read and output the GIF
        readfile($gifPath);
    } else {
        // If the GIF doesn't exist, show a default message
        echo "No funny GIF today :(";
    }
    exit; // Stop further script execution
}
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

// Check if form data is submitted
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
    print_r($response);  // Display the raw response from RabbitMQ
    echo "</pre>";
    
} else {
    // Handle invalid request method
    echo "Invalid request method.";
}
