#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');
$requestsCounter = 0;
date_default_timezone_set('America/New_York');

function doLogin($username,$password)
{
    try {
      // Database connection details
      $dbLogin = 'mysql:host=10.0.0.10;dbname=logindb';
      $dbUsername = 'rabbit';
      $dbPassword = 'rabbitIT490!';

      $pdo = new PDO($dbLogin, $dbUsername, $dbPassword);
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

      // Only fetch hashed password from database.
      $stmt = $pdo->prepare("SELECT password FROM users WHERE username = :username");
      $stmt->bindParam(':username', $username);
      $stmt->execute();

      // Using password_verify to compare the hashed password to the database.
      $user = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($user) {
        if (password_verify($password, $user['password'])) {
            // Update the last_login field on successful login
            $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            return "Login successful!";
        } else {
            return "Login failed. Invalid password.";
        }
      } else {
          return "Login failed. User " . $user . " not found.";
      }

  } catch (PDOException $e) {
      error_log('Database error: ' . $e->getMessage());
      return false;
  }
}

function requestProcessor($request)
{
  // Added a message log file to keep track of messages
  // As well as a different ouput message on the server side
  global $requestsCounter;
  $logFile = __DIR__ . '/received_messages.log';
  $logTime = date('m-d-Y H:i:s');
  $logRequest = "[" . $logTime . "] Received request: " . print_r($request, true) . PHP_EOL;
  file_put_contents($logFile, $logRequest, FILE_APPEND);
  
  // Clear the screen. Keeping the clutter minimal.
  // Will probably not need this when automating and persistence.
  system('clear');
  $requestsCounter += 1;
  echo "Received request. Messages parsed: " . $requestsCounter . PHP_EOL;
  // var_dump($request);
  
  if(!isset($request['type']))
  {
    return "ERROR: unsupported message type";
  }
  switch ($request['type'])
  {
    case "login":
      return doLogin($request['username'],$request['password']);
    case "validate_session":
      return doValidate($request['sessionId']);
  }
  return array("returnCode" => '0', 'message'=>"Server received request and processed");
}

$server = new rabbitMQServer("testRabbitMQ.ini","testServer");

echo "testRabbitMQServer BEGIN".PHP_EOL;
$server->process_requests('requestProcessor');
echo "testRabbitMQServer END".PHP_EOL;
exit();
?>

