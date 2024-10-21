<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');
$config = include('dbClient.php');
// Change accordingly this is set to 1 hour
$ttl = 3600;

date_default_timezone_set('America/New_York');

function doLogin($username, $password) {
    global $config, $ttl;
    $dbhost = $config['DBHOST'];
    $logindb = $config['LOGINDATABASE'];
    $dbLogin = "mysql:host=$dbhost;dbname=$logindb";
    $dbUsername = $config['DBUSER'];
    $dbPassword = $config['DBPASSWORD'];

    try {
        // Connect to the database
        $pdo = new PDO($dbLogin, $dbUsername, $dbPassword);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $pdo->prepare("SELECT password FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify password and update last_login field for user
        if ($user && password_verify($password, $user['password'])) {
            $stmt = $pdo->prepare("UPDATE users SET last_login = TIME() WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();

            // Prepare token
            $token = bin2hex(random_bytes(32));
            $tokenExpire = time() + $ttl;

            // Store the session token in the database and update the token session if record already exists to avoid duplicates
            $stmt = $pdo->prepare("INSERT INTO sessions (username, session_token, expire_date) VALUES (:username, :token, :tokenExpire) ON DUPLICATE KEY UPDATE session_token = :token, expire_date = :tokenExpire");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':token', $token);
            $stmt->bindParam(':tokenExpire', $tokenExpire, PDO::PARAM_INT);
            $stmt->execute();

            return [
                "success" => true,
                "message" => "Login successful!",
                "session_token" => $token
            ];
        } else {
            return [
                "success" => false,
                "message" => "Invalid username or password."
            ];
        }
    } catch (PDOException $e) {
        return [
            "success" => false,
            "message" => "An error occurred: " . $e->getMessage()
        ];
    }
}

// Function to validate session token
function validateSession($sessionToken) {
    global $config;
    $dbhost = $config['DBHOST'];
    $logindb = $config['LOGINDATABASE'];
    $dbLogin = "mysql:host=$dbhost;dbname=$logindb";
    $dbUsername = $config['DBUSER'];
    $dbPassword = $config['DBPASSWORD'];

    try {
        $pdo = new PDO($dbLogin, $dbUsername, $dbPassword);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Validate the session token
        $stmt = $pdo->prepare("SELECT username, expire_date FROM sessions WHERE session_token = :token");
        $stmt->bindParam(':token', $sessionToken);
        $stmt->execute();

        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($session && $session['expire_date'] > time()) {
            return [
                "success" => true,
                "username" => $session['username']
            ];
        } else {
            return [
                "success" => false,
                "message" => "Session is invalid or expired."
            ];
        }
    } catch (PDOException $e) {
        return [
            "success" => false,
            "message" => "An error occurred: " . $e->getMessage()
        ];
    }
}

// Function to process incoming requests
function requestProcessor($request) {
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
            $response = validateSession($request['session_token']);
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
