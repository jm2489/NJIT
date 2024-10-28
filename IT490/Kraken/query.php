<?php
require_once 'KrakenAPIClient.php';
$config = require_once('k_API');

// your api credentials
$key = $config['API_KEY'];
$secret = $config['API_SECRET'];

// Set which platform to use (beta or standard)
$beta = false; 
$url = $beta ? 'https://api.beta.kraken.com' : 'https://api.kraken.com';
$sslverify = $beta ? false : true;
$version = 0;

// Initialize KrakenAPI
$kraken = new \Payward\KrakenAPI($key, $secret, $url, $version, $sslverify);

// Query assets
$res = $kraken->QueryPrivate('Balance');

// Make the output more readable
echo '<pre>';
print_r($res);
echo '</pre>';
