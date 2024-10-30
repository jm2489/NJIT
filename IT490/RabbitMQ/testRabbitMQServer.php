<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');
require_once('Kraken/KrakenAPIClient.php');
$config = include('dbClient.php');
$apiConfig = require_once('Kraken/k_API');
// Change accordingly this is set to 1 hour
$ttl = 3600;

date_default_timezone_set('America/New_York');

function doLogin($username, $password)
{
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
            // Update last_login field with UNIX epoch time
            $unixEpoch = time();
            $stmt = $pdo->prepare("UPDATE users SET last_login = :unixEpoch WHERE username = :username");
            $stmt->bindParam(':unixEpoch', $unixEpoch, PDO::PARAM_INT);
            $stmt->bindParam(':username', $username);
            $stmt->execute();

            $token = bin2hex(random_bytes(32));
            $tokenExpire = $unixEpoch + $ttl;

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

function validateSession($sessionToken)
{
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

function krakenQuery($requests)
{
    global $apiConfig;
    $key = $apiConfig['API_KEY'];
    $secret = $apiConfig['API_SECRET'];

    // Set which platform to use (beta or standard)
    $beta = false;
    $url = $beta ? 'https://api.beta.kraken.com' : 'https://api.kraken.com';
    $sslverify = $beta ? false : true;
    $version = 0;

    try {
        // Initialize KrakenAPI
        $kraken = new \Payward\KrakenAPI($key, $secret, $url, $version, $sslverify);
        switch ($requests) {
            case "query";
                $response = $kraken->QueryPrivate('Balance');
                break;
            case "public":
                $response = $kraken->QueryPublic('Time');
                break;
            default:
                $response = [
                    "success" => false,
                    "message" => "Invalid transaction type."
                ];
        }
        // Check if the response contains an error
        if (isset($response['error']) && !empty($response['error'])) {
            return [
                "success" => false,
                "message" => "Kraken API error: " . implode(', ', $response['error'])
            ];
        }
        return [
            "success" => true,
            "data" => $response
        ];
    } catch (Exception $e) {
        return [
            "success" => false,
            "message" => "Exception occurred: " . $e->getMessage()
        ];
    }
}

function doLogout($sessionToken){
    global $config;
    $dbhost = $config['DBHOST'];
    $logindb = $config['LOGINDATABASE'];
    $dbLogin = "mysql:host=$dbhost;dbname=$logindb";
    $dbUsername = $config['DBUSER'];
    $dbPassword = $config['DBPASSWORD'];

    try {
        $pdo = new PDO($dbLogin, $dbUsername, $dbPassword);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("DELETE FROM sessions WHERE session_token = :token");
        $stmt->bindParam(':token', $sessionToken);
        $stmt->execute();

        return [
            "success" => true,
            "message" => "Logout successful!"
        ];
    } catch (PDOException $e) {
        return [
            "success" => false,
            "message" => "An error occurred: " . $e->getMessage()
        ];
    }
}

function requestProcessor($request)
{
    $logFile = __DIR__ . '/received_messages.log';
    $logTime = date('m-d-Y H:i:s');
    // Log requests
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
        case "krakenQuery":
            $response = krakenQuery($request['transaction']);
            break;
        case "logout":
            $response = doLogout($request['session_token']);
            break;
        default:
            $response = [
                "success" => false,
                "message" => "ERROR: Unknown request type"
            ];
    }
    // Log response
    $logResponse = "[" . $logTime . "] Received response: " . print_r($response, true) . PHP_EOL;
    file_put_contents($logFile, $logResponse, FILE_APPEND);
    return json_encode($response);
}

$server = new rabbitMQServer("testRabbitMQ.ini", "testServer");

echo "testRabbitMQServer BEGIN" . PHP_EOL;
$server->process_requests('requestProcessor');
echo "testRabbitMQServer END" . PHP_EOL;
exit();
