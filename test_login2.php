<?php

$baseUrl = 'http://127.0.0.1:8000';
$cookieFile = __DIR__ . '/cookies.txt';

// Clean cookie file
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
echo "=== Step 1: Get login page ===\n";
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/');
curl_setopt($ch, CURLOPT_HTTPGET, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "Status: $httpCode\n";

preg_match('/name="_token" value="([^"]+)"/', $response, $matches);
$token = $matches[1] ?? '';
echo "CSRF Token: $token\n\n";

// Step 2: Login
echo "=== Step 2: Login as PPK ===\n";
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'username' => '2025082',
    'password' => 'password123',
    '_token' => $token
]));
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "Status: $httpCode\n";

$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);
if (preg_match('/Location: ([^\r\n]+)/', $headers, $matches)) {
    echo "Redirect to: " . trim($matches[1]) . "\n";
}
echo "\n";

// Step 3: Access ERKAP dashboard
echo "=== Step 3: Access ERKAP Dashboard ===\n";
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/erkap');
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_POST, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "Status: $httpCode\n";

$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

if (preg_match('/Location: ([^\r\n]+)/', $headers, $matches)) {
    echo "Redirect to: " . trim($matches[1]) . "\n";
}

if ($httpCode == 200) {
    echo "ERKAP dashboard accessible!\n";
    // Check if it's the ERKAP page
    if (strpos($body, 'ERKAP') !== false || strpos($body, 'Dashboard') !== false) {
        echo "Page contains ERKAP content\n";
    }
} else {
    echo "Response body (first 500 chars):\n";
    echo substr($body, 0, 500) . "\n";
}

curl_close($ch);
