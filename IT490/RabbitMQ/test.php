<?php
$output = shell_exec("python3 balance.py");
$data = json_decode($output, true);

if ($data !== null) {
    header('Content-Type: application/json');
    echo json_encode($data) . "\n";
} else {
    echo json_encode(["error" => "Failed to retrieve data"]);
}