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
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $client = new rabbitMQClient("testRabbitMQ.ini", "testServer");

    $request = [
        'type' => "login",
        'username' => $username,
        'password' => $password,
        'message' => "Login Request"
    ];

    // Send the request to RabbitMQ server
    $response = $client->send_request($request);

    // Decode the JSON response from RabbitMQ
    $response = json_decode($response, true);

    // Check for successful login and presence of the session token
    if (isset($response['success']) && $response['success']) {
        if (isset($response['session_token'])) {
            // Set the session token as a cookie
            setcookie('session', $response['session_token'], [
                'expires' => time() + 3600,
                'path' => '/',
                'secure' => false, // Set to true in production with HTTPS
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
        }

        // Send JSON response with redirect info
        header('Content-Type: application/json');
        echo json_encode([
            "success" => true,
            "message" => "Login successful.",
            "redirect" => "/php/info.php" // Remember to change to .htaccess /info later.
        ]);
        exit;
    }

    // If login fails, return the response as JSON
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);
}