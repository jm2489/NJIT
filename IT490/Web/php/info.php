<?php
require_once('validater.php');

// Validate session
$username = validateSession();

// Continue with page-specific logic
echo "Welcome, " . htmlspecialchars($username) . "!";
