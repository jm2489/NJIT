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