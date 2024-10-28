<?php
require_once('validater.php');

// Validate session
$response = validateSession();
$responseArray = json_decode($response, true);

// Continue with page content...
if (is_array($responseArray) && isset($responseArray['success']) && $responseArray['success']) {
    $username = $responseArray['username'];
    echo "<div id='welcome'>Welcome, " . htmlspecialchars($username) . "!</div>";
} else {
    // Handle invalid or unsuccessful response
    echo "<div id='welcome'>Session validation failed.</div>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Info</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin-top: 50px;
        }
        #data-container {
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #ccc;
            display: inline-block;
        }
    </style>
</head>
<body>
    <h1>Dashboard</h1>

    <button onclick="fetchKrakenData()">Kraken Query</button>

    <div id="data-container">Click the button to retrieve Kraken data.</div>

    <script>
        async function fetchKrakenData() {
            try {
                const response = await fetch('query.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    }
                });
                const data = await response.json();
                const container = document.getElementById('data-container');
                container.innerHTML = JSON.stringify(data, null, 2);
            } catch (error) {
                console.error('Error fetching data:', error);
                document.getElementById('data-container').innerText = 'Failed to retrieve data.';
            }
        }
    </script>
</body>
</html>
