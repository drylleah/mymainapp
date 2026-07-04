<?php
/**
 * translate.php
 * Receives Tagalog text via POST and returns its English translation as JSON.
 *
 * Uses Google's free, unauthenticated "gtx" translate endpoint. It has no
 * official SLA and can be rate-limited, but it needs no API key and is
 * commonly used for small hobby / learning projects like this one.
 *
 * If you later want a more reliable production setup, swap translateText()
 * below to call the official Cloud Translation API (needs an API key)
 * or a self-hosted LibreTranslate instance.
 */

header('Content-Type: application/json; charset=utf-8');

// Only accept POST requests.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$text = isset($data['text']) ? trim($data['text']) : '';

if ($text === '') {
    echo json_encode(['translation' => '']);
    exit;
}

$translation = translateText($text, 'tl', 'en');

if ($translation === null) {
    http_response_code(502);
    echo json_encode(['error' => 'Translation service unavailable']);
    exit;
}

echo json_encode(['translation' => $translation]);


/**
 * Calls the free Google Translate "gtx" endpoint.
 *
 * @param string $text   Text to translate.
 * @param string $source Source language code (e.g. 'tl').
 * @param string $target Target language code (e.g. 'en').
 * @return string|null   Translated text, or null on failure.
 */
function translateText(string $text, string $source, string $target): ?string
{
    $url = 'https://translate.googleapis.com/translate_a/single'
        . '?client=gtx'
        . '&sl=' . urlencode($source)
        . '&tl=' . urlencode($target)
        . '&dt=t'
        . '&q=' . urlencode($text);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; CipherConsole/1.0)',
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false || $httpCode !== 200) {
        error_log('translateText failed: HTTP ' . $httpCode . ' ' . $curlError);
        return null;
    }

    $decoded = json_decode($response, true);

    if (!is_array($decoded) || !isset($decoded[0]) || !is_array($decoded[0])) {
        return null;
    }

    // The response is nested arrays of translated sentence fragments.
    // Each item in $decoded[0] looks like: ["translated chunk", "original chunk", ...]
    $translatedChunks = [];
    foreach ($decoded[0] as $chunk) {
        if (isset($chunk[0])) {
            $translatedChunks[] = $chunk[0];
        }
    }

    return implode('', $translatedChunks);
}
