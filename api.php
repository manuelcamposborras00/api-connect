<?php
header('Content-Type: application/json; charset=utf-8');

// Local: usa config.php. Producción: lee variables de entorno.
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
} else {
    define('AI_API_KEY',    getenv('AI_API_KEY')    ?: '');
    define('AI_MODEL',      getenv('AI_MODEL')      ?: 'gemini-1.5-flash');
    define('AI_MAX_TOKENS', (int)(getenv('AI_MAX_TOKENS') ?: 1024));
}

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

// Leer y validar input
$body   = json_decode(file_get_contents('php://input'), true);
$prompt = trim($body['prompt'] ?? '');

if ($prompt === '') {
    http_response_code(400);
    echo json_encode(['error' => 'El mensaje no puede estar vacío.']);
    exit;
}

if (mb_strlen($prompt) > 4000) {
    http_response_code(400);
    echo json_encode(['error' => 'El mensaje supera el límite de 4000 caracteres.']);
    exit;
}

// Llamada a la API de Google Gemini (Generative Language API)
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

// Endpoint de Gemini (v1beta por compatibilidad con modelos flash)
$url = "https://generativelanguage.googleapis.com/v1beta/models/" . AI_MODEL . ":generateContent?key=" . AI_API_KEY;

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

// Error de red
if ($raw === false || $curlErr !== '') {
    http_response_code(502);
    echo json_encode(['error' => 'No se pudo contactar con la API de Gemini. Inténtalo de nuevo.']);
    exit;
}

$data = json_decode($raw, true);

// Error devuelto por la API (Gemini suele devolverlo en un array con 'error')
if ($httpCode !== 200) {
    $apiMsg = $data['error']['message'] ?? 'Error desconocido de la API de Gemini.';
    http_response_code(502);
    echo json_encode(['error' => 'La API respondió con un error: ' . $apiMsg]);
    exit;
}

// Verificar que candidates existe y no está vacío (puede ocurrir si Gemini bloquea la respuesta)
if (empty($data['candidates'][0])) {
    $blockReason = $data['promptFeedback']['blockReason'] ?? 'desconocido';
    http_response_code(502);
    echo json_encode(['error' => 'La respuesta fue bloqueada por Gemini. Motivo: ' . $blockReason]);
    exit;
}

// Extraer texto de la respuesta
$text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

if ($text === '') {
    http_response_code(502);
    echo json_encode(['error' => 'La API de Gemini devolvió una respuesta vacía.']);
    exit;
}

echo json_encode(['response' => $text]);
