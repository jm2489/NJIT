<?php
require_once('validater.php');

// Validate session
$response = validateSession();
$responseArray = json_decode($response, true);

// Continue with page content...
if (is_array($responseArray) && isset($responseArray['success']) && $responseArray['success']) {
    $username = $responseArray['username'];
    echo "Welcome, " . htmlspecialchars($username) . "!";
} else {
    // Handle invalid or unsuccessful response
    echo "Session validation failed.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Query Button</title>
</head>
<body>
    <form action="query.php" method="POST">
        <button type="submit">Kraken Query</button>
    </form>
</body>
</html>