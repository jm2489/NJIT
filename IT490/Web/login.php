<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $username = $_POST['username'];
    $password = $_POST['password'];

    $client = new rabbitMQClient("testRabbitMQ.ini", "testServer");

    $request = array();
    $request['type'] = "login";
    $request['username'] = $username;
    $request['password'] = $password;
    // $request['message'] = "test message";

    $response = $client->send_request($request);

    // Output the response
    echo "Client received response: <br>";
    echo "<pre>";
    print_r($response);
    echo "</pre>";
} else {
    echo "Invalid request method.";
}
?>
