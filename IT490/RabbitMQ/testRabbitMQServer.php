#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');
$config = include('dbClient.php');

$requestsCounter = 0;
date_default_timezone_set('America/New_York');

function doLogin($username, $password) {
    try {
        global $config;
        $dbhost = $config['DBHOST'];
        $logindb = $config['LOGINDATABASE'];
        $dbLogin = "mysql:host=$dbhost;dbname=$logindb";
        $dbUsername = $config['DBUSER'];
        $dbPassword = $config['DBPASSWORD'];

        $pdo = new PDO($dbLogin, $dbUsername, $dbPassword);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Fetch only the hashed password from the database.
        $stmt = $pdo->prepare("SELECT password FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password'])) {
            // Update the last_login field on successful login
            $stmt = $pdo->prepare("UPDATE users SET last_login = UNIX_TIMESTAMP() WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();

            return [
                "success" => true,
                "message" => "Login successful!"
            ];
        } else {
            return [
                "success" => false,
                "message" => "Invalid username or password."
            ];
        }
    } catch (PDOException $e) {
        $error_message = 'Database error: ' . $e->getMessage();
        error_log($error_message);
        
        // Log the error to a local file as well
        $log_file = 'error.log';
        file_put_contents($log_file, date('[Y-m-d H:i:s] ') . $error_message . PHP_EOL, FILE_APPEND);
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
