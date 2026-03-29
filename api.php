<?php
header('Content-Type: application/json; charset=utf-8');

// Locally uses config.php. In production reads from environment variables.
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
} else {
    define('AI_API_KEY',    getenv('AI_API_KEY')    ?: '');
    define('AI_MODEL',      getenv('AI_MODEL')      ?: 'gemini-1.5-flash-8b');
    define('AI_MAX_TOKENS', (int)(getenv('AI_MAX_TOKENS') ?: 1024));
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

// Read and validate input
$body   = json_decode(file_get_contents('php://input'), true);
$prompt = trim($body['prompt'] ?? '');

if ($prompt === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Message cannot be empty.']);
    exit;
}

if (mb_strlen($prompt) > 4000) {
    http_response_code(400);
    echo json_encode(['error' => 'Message exceeds the 4000 character limit.']);
    exit;
}

// Build request payload for Google Gemini API
$payload = json_encode([
    'contents' => [
        [
            'parts' => [
                ['text' => $prompt]
            ]
        ]
    ],
    'generationConfig' => [
        'maxOutputTokens' => AI_MAX_TOKENS
    ],
    'safetySettings' => [
        ['category' => 'HARM_CATEGORY_HARASSMENT',        'threshold' => 'BLOCK_ONLY_HIGH'],
        ['category' => 'HARM_CATEGORY_HATE_SPEECH',       'threshold' => 'BLOCK_ONLY_HIGH'],
        ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_ONLY_HIGH'],
        ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_ONLY_HIGH'],
    ]
]);

// Gemini endpoint
$url = "https://generativelanguage.googleapis.com/v1/models/" . AI_MODEL . ":generateContent?key=" . AI_API_KEY;

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json'
    ],
]);

$raw      = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

// Network error
if ($raw === false || $curlErr !== '') {
    http_response_code(502);
    echo json_encode(['error' => 'Could not reach the API. Please try again.']);
    exit;
}

$data = json_decode($raw, true);

// API-level error (Gemini returns it inside an 'error' array)
if ($httpCode !== 200) {
    $apiMsg = $data['error']['message'] ?? 'Unknown API error.';
    http_response_code(502);
    echo json_encode(['error' => 'API error: ' . $apiMsg]);
    exit;
}

// Check candidates exists and is not empty (can happen when Gemini blocks the response)
if (empty($data['candidates'][0])) {
    $blockReason = $data['promptFeedback']['blockReason'] ?? 'unknown';
    http_response_code(502);
    echo json_encode(['error' => 'Response blocked by Gemini. Reason: ' . $blockReason]);
    exit;
}

// Extract text from response
$text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

if ($text === '') {
    http_response_code(502);
    echo json_encode(['error' => 'The API returned an empty response.']);
    exit;
}

echo json_encode(['response' => $text]);
