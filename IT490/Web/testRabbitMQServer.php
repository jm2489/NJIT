#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

$requestsCounter = 0;
date_default_timezone_set('America/New_York');

function doLogin($username, $password) {
    try {
        // Database connection details
        $dbLogin = 'mysql:host=10.0.0.3;dbname=logindb';
        $dbUsername = 'rabbit';
        $dbPassword = 'rabbitIT490!';

        $pdo = new PDO($dbLogin, $dbUsername, $dbPassword);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Fetch only the hashed password from the database.
        $stmt = $pdo->prepare("SELECT password FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password'])) {
            // Update the last_login field on successful login
            $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();

            // Return JSON-structured success message
            return [
                "success" => true,
                "message" => "Login successful!"
            ];
        } else {
            // Return JSON-structured failure message
            return [
                "success" => false,
                "message" => "Invalid username or password."a,,q//al
            ];
        }
    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage());

        // Return JSON-structured error response
        return [
            "success" => false,
            "message" => "An error occurred during login. Please try again later."
        ];
    }
}

function requestProcessor($request) {
    global $requestsCounter;
    $logFile = __DIR__ . '/received_messages.log';
    $logTime = date('m-d-Y H:i:s');
    $logRequest = "[" . $logTime . "] Received request: " . print_r($request, true) . PHP_EOL;
    file_put_contents($logFile, $logRequest, FILE_APPEND);

    if (!isset($request['type'])) {
        return json_encode([
            "success" => false,
            "message" => "ERROR: Unsupported message type"
        ]);
    }

    switch ($request['type']) {
        case "login":
            $response = doLogin($request['username'], $request['password']);
            break;
        case "validate_session":
            $response = doValidate($request['sessionId']);
            break;
        default:
            $response = [
                "success" => false,
                "message" => "ERROR: Unknown request type"
            ];
    }

    return json_encode($response);
}

$server = new rabbitMQServer("testRabbitMQ.ini", "testServer");

echo "testRabbitMQServer BEGIN" . PHP_EOL;
$server->process_requests('requestProcessor');
echo "testRabbitMQServer END" . PHP_EOL;
exit();
?>
