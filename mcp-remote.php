<?php

$url = getenv('MCP_REMOTE_URL') ?: 'https://kirikaadigital.com/mcp';
$envFile = getenv('HOME') . '/.env';

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with($line, 'HRM_PRODUCTION_PASSWORD=') || str_starts_with($line, 'MCP_REMOTE_AUTH=')) {
            putenv(trim($line));
        }
    }
}

$auth = getenv('MCP_REMOTE_AUTH');
$rawPassword = getenv('HRM_PRODUCTION_PASSWORD');

if (!$auth && $rawPassword) {
    $auth = 'Basic ' . base64_encode('ai-manager:' . $rawPassword);
}
while ($line = fgets(STDIN)) {
    $line = trim($line);
    if (!$line) continue;
    
    $ch = curl_init($url);
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];
    if ($auth) {
        $headers[] = 'Authorization: ' . $auth;
    }
    
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $line);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response !== false) {
        echo $response . "\n";
    }
}
