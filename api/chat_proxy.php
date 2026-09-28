<?php
/**
 * Chat Proxy - Forwards chat requests to Python chatbot service
 * This allows the PHP app to access the Python service via ngrok
 */

header('Content-Type: application/json');

// Get the request body
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

// Forward to Python service
$pythonApiUrl = 'http://localhost:8000/chat';

$ch = curl_init($pythonApiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to connect to chatbot service: ' . $error]);
    exit;
}

if ($httpCode !== 200) {
    http_response_code($httpCode);
    echo $response;
    exit;
}

echo $response;
