<?php

$baseUrl = 'http://127.0.0.1:8000';
$cookieFile = __DIR__ . '/cookies.txt';

if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_HEADER, true);

// Step 1: Get login page
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/');
curl_setopt($ch, CURLOPT_HTTPGET, true);
$response = curl_exec($ch);
preg_match('/name="_token" value="([^"]+)"/', $response, $matches);
$token = $matches[1] ?? '';

// Step 2: Login
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'username' => '2025082',
    'password' => 'password123',
    '_token' => $token
]));
curl_exec($ch);

// Step 3: Access ERKAP dashboard
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/erkap');
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_POST, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

echo "ERKAP access status: $httpCode\n";

if ($httpCode == 200) {
    if (strpos($body, 'ERKAP') !== false) {
        echo "SUCCESS: ERKAP dashboard accessible!\n";
    } else {
        echo "Page loaded but may not be ERKAP\n";
        preg_match('/<title>([^<]+)<\/title>/', $body, $matches);
        echo "Title: " . ($matches[1] ?? 'Unknown') . "\n";
    }
} elseif ($httpCode == 302) {
    preg_match('/Location: ([^\r\n]+)/', $headers, $matches);
    echo "Redirect to: " . trim($matches[1]) . "\n";
}

curl_close($ch);
