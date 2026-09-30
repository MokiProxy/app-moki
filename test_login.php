<?php

$baseUrl = 'http://127.0.0.1:8000';
$cookieFile = __DIR__ . '/cookies.txt';

function httpRequest($url, $method = 'GET', $data = [], $cookieFile = '') {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_HEADER, true);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }

    $response = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $httpCode, 'headers' => $headers, 'body' => $body];
}

// Step 1: Get login page and CSRF token
echo "=== Step 1: Get login page ===\n";
$result = httpRequest($baseUrl . '/', 'GET', [], $cookieFile);
echo "Status: {$result['code']}\n";

if (preg_match('/name="_token" value="([^"]+)"/', $result['body'], $matches)) {
    $token = $matches[1];
    echo "CSRF Token: $token\n\n";
} else {
    echo "ERROR: CSRF token not found\n";
    exit(1);
}

// Step 2: Login
echo "=== Step 2: Login as PPK ===\n";
$result = httpRequest($baseUrl . '/login', 'POST', [
    'username' => '2025082',
    'password' => 'password123',
    '_token' => $token
], $cookieFile);
echo "Status: {$result['code']}\n";

if (strpos($result['headers'], 'Location:') !== false) {
    preg_match('/Location: ([^\r\n]+)/', $result['headers'], $matches);
    echo "Redirect to: " . trim($matches[1]) . "\n";
}

if (strpos($result['body'], 'Login gagal') !== false) {
    echo "ERROR: Login failed\n";
    exit(1);
}

echo "Login successful!\n\n";

// Step 3: Access ERKAP dashboard
echo "=== Step 3: Access ERKAP Dashboard ===\n";
$result = httpRequest($baseUrl . '/erkap', 'GET', [], $cookieFile);
echo "Status: {$result['code']}\n";
if ($result['code'] == 200) {
    echo "ERKAP dashboard accessible\n";
} elseif ($result['code'] == 302) {
    preg_match('/Location: ([^\r\n]+)/', $result['headers'], $matches);
    echo "Redirect to: " . trim($matches[1]) . "\n";
} else {
    echo "ERROR: Cannot access ERKAP\n";
    echo substr($result['body'], 0, 500) . "\n";
}
