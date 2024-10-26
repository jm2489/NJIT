<?php
/**
 * @author jm2489
 * @date 2024-10-25
 * @description Socket server handling JSON-encoded messages and querying the database.
 */

try {
    require('dbClient.php');
} catch (Error $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Configuration file error.',
        'details' => $e->getMessage()
    ]);
    exit;
}

function dbConnect() {
    try {
        $dsn = 'mysql:host=' . DBHOST . ';dbname=' . DBNAME;
        $pdo = new PDO($dsn, DBUSER, DBPASSWORD);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed.',
            'details' => $e->getMessage()
        ]);
        exit;
    }
}

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if ($socket === false) {
    echo "Failed to create socket: " . socket_strerror(socket_last_error()) . "\n";
    exit;
}

$host = '0.0.0.0'; 
$port = 8888;
if (!socket_bind($socket, $host, $port)) {
    echo "Failed to bind socket: " . socket_strerror(socket_last_error($socket)) . "\n";
    exit;
}

if (!socket_listen($socket, 3)) {
    echo "Failed to listen: " . socket_strerror(socket_last_error($socket)) . "\n";
    exit;
}

echo "Listening for connections on $host:$port...\n";

// Main loop to handle incoming connections
while (true) {
    $connection = socket_accept($socket);
    if ($connection === false) {
        echo "Failed to accept connection: " . socket_strerror(socket_last_error($socket)) . "\n";
        continue;
    }

    // echo "Connection accepted.\n"; // Debug

    $data = socket_read($connection, 4096, PHP_NORMAL_READ);
    if ($data === false) {
        echo "Failed to read data: " . socket_strerror(socket_last_error($connection)) . "\n";
        socket_close($connection);
        continue;
    }

    $data = trim($data);
    // echo "Received data: $data\n"; // Debug

    $jsonData = json_decode($data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $response = json_encode([
            'success' => false,
            'message' => 'Invalid JSON format.'
        ]);
        socket_write($connection, $response, strlen($response));
        socket_close($connection);
        continue;
    }

    // Check for a query field
    if (isset($jsonData['query'])) {
        $query = $jsonData['query'];

        $pdo = dbConnect();

        try {
            $stmt = $pdo->prepare($query);
            $stmt->execute();

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response = json_encode([
                'success' => 'success',
                'data' => $results
            ]);
        } catch (PDOException $e) {
            $response = json_encode([
                'success' => false,
                'message' => 'Query execution failed.',
                'details' => $e->getMessage()
            ]);
        }
        socket_write($connection, $response, strlen($response));
    } else {
        $response = json_encode([
            'success' => false,
            'message' => 'Missing "query" field in request.'
        ]);
        socket_write($connection, $response, strlen($response));
    }
    // socket_shutdown is needed to make sure the connection closes properly
    socket_shutdown($connection);
    socket_close($connection);
}
socket_close($socket);
