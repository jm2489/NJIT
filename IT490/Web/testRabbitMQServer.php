#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

function doLogin($username,$password)
{
    // May need to implement sanitation for increased security. I'm too lazy right now.
    try {
      // Database connection details
      $dsn = 'mysql:host=172.104.212.164;dbname=logindb';
      $dbUsername = 'rabbit';
      $dbPassword = 'rabbitIT490!';

      // Create a new PDO instance
      $pdo = new PDO($dsn, $dbUsername, $dbPassword);
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

      // Prepare and execute the query
      $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username AND password = :password");
      $stmt->bindParam(':username', $username);
      $stmt->bindParam(':password', $password);  // Need to replace as hash. I was using basic login example

      $stmt->execute();

      // Check if any rows were returned
      if ($stmt->rowCount() > 0) {
          return true; // Login successful
      } else {
          return false; // Login failed
      }

  } catch (PDOException $e) {
      // Log error
      error_log('Database error: ' . $e->getMessage());
      return false;
  }
}

function requestProcessor($request)
{
  echo "received request".PHP_EOL;
  var_dump($request);
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

