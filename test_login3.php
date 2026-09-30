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

// Step 3: Access ERKAP dashboard - follow redirects
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/erkap');
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
echo "Final status: $httpCode\n";
echo "Effective URL: $effectiveUrl\n";
echo "Response length: " . strlen($response) . "\n";

// Check if it's the ERKAP page
if (strpos($response, 'ERKAP') !== false) {
    echo "Page contains ERKAP content\n";
} elseif (strpos($response, 'login') !== false) {
    echo "Redirected to login page\n";
} else {
    echo "Page title: ";
    preg_match('/<title>([^<]+)<\/title>/', $response, $matches);
    echo ($matches[1] ?? 'Unknown') . "\n";
}

curl_close($ch);
