<?php
session_start();
require_once 'lang.php';

header('Content-Type: application/json; charset=UTF-8');

function translationResponse($status, $payload)
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    translationResponse(401, ['error' => 'login_required']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    translationResponse(405, ['error' => 'method_not_allowed']);
}

$request = json_decode(file_get_contents('php://input'), true);
$text = is_array($request) ? ($request['text'] ?? null) : null;
if (!is_string($text) || trim($text) === '' || mb_strlen($text, 'UTF-8') > 5000) {
    translationResponse(400, ['error' => 'invalid_text']);
}

$languageCodes = [
    'en-US' => 'en',
    'ja-JP' => 'ja',
    'zh-CN' => 'zh',
    'ko-KR' => 'ko',
    'hi-IN' => 'hi',
    'bn-BD' => 'bn',
    'ne-NP' => 'ne',
    'ru-RU' => 'ru',
    'fr-FR' => 'fr',
    'si-LK' => 'si',
    'ta-LK' => 'ta',
    'my-MM' => 'my',
    'mn-MN' => 'mn',
    'vi-VN' => 'vi',
];
$targetLanguage = $languageCodes[$current_lang] ?? null;
if ($targetLanguage === null) {
    translationResponse(400, ['error' => 'unsupported_language']);
}

require_once 'env.php';
$endpointSetting = getenv('NEXAGRAM_TRANSLATION_ENDPOINT');
$apiKeySetting = getenv('NEXAGRAM_TRANSLATION_API_KEY');
$endpoint = is_string($endpointSetting) && trim($endpointSetting) !== ''
    ? rtrim(trim($endpointSetting), '/')
    : (isset($translation_endpoint) && is_string($translation_endpoint) ? rtrim(trim($translation_endpoint), '/') : '');
$apiKey = is_string($apiKeySetting) && trim($apiKeySetting) !== ''
    ? trim($apiKeySetting)
    : (isset($translation_api_key) && is_string($translation_api_key) ? trim($translation_api_key) : '');

if (!function_exists('curl_init')) {
    error_log('The PHP cURL extension is required for the translation service.');
    translationResponse(503, ['error' => 'translation_failed']);
}

$useLibreTranslate = $endpoint !== '';
if ($useLibreTranslate) {
    $endpointParts = parse_url($endpoint);
    if (
        !is_array($endpointParts)
        || !isset($endpointParts['scheme'], $endpointParts['host'])
        || !in_array(strtolower($endpointParts['scheme']), ['https', 'http'], true)
    ) {
        error_log('Translation endpoint configuration is invalid.');
        translationResponse(503, ['error' => 'translation_not_configured']);
    }

    $url = $endpoint . (preg_match('~/translate$~i', $endpoint) ? '' : '/translate');
    $payload = [
        'q' => trim($text),
        'source' => 'auto',
        'target' => $targetLanguage,
        'format' => 'text',
    ];
    if ($apiKey !== '') {
        $payload['api_key'] = $apiKey;
    }
} else {
    $googleTargetLanguage = $targetLanguage === 'zh' ? 'zh-CN' : $targetLanguage;
    $url = 'https://translate.googleapis.com/translate_a/single?' . http_build_query([
        'client' => 'gtx',
        'sl' => 'auto',
        'tl' => $googleTargetLanguage,
        'dt' => 't',
        'q' => trim($text),
    ], '', '&', PHP_QUERY_RFC3986);
}

$curl = curl_init($url);
if ($curl === false) {
    error_log('Could not initialize the translation service request.');
    translationResponse(502, ['error' => 'translation_failed']);
}

curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 8,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
]);
if ($useLibreTranslate) {
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
    ]);
} else {
    curl_setopt($curl, CURLOPT_HTTPGET, true);
}

$responseBody = curl_exec($curl);
$curlError = curl_error($curl);
$status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
curl_close($curl);

$useMyMemory = false;
if (!$useLibreTranslate && (!is_string($responseBody) || $status < 200 || $status >= 300)) {
    $myMemoryLanguage = $targetLanguage === 'zh' ? 'zh-CN' : $targetLanguage;
    $sourceLanguage = 'en';
    if (preg_match('/[\x{3040}-\x{30FF}\x{3400}-\x{4DBF}\x{4E00}-\x{9FFF}]/u', $text)) {
        $sourceLanguage = 'ja';
    } elseif (preg_match('/\x{AC00}-\x{D7AF}/u', $text)) {
        $sourceLanguage = 'ko';
    } elseif (preg_match('/\x{0400}-\x{04FF}/u', $text)) {
        $sourceLanguage = 'ru';
    } elseif (preg_match('/\x{0E00}-\x{0E7F}/u', $text)) {
        $sourceLanguage = 'th';
    }
    $myMemoryUrl = 'https://api.mymemory.translated.net/get?' . http_build_query([
        'q' => trim($text),
        'langpair' => $sourceLanguage . '|' . $myMemoryLanguage,
    ], '', '&', PHP_QUERY_RFC3986);

    $fallbackCurl = curl_init($myMemoryUrl);
    if ($fallbackCurl !== false) {
        curl_setopt_array($fallbackCurl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPGET => true,
        ]);
        $fallbackBody = curl_exec($fallbackCurl);
        $fallbackStatus = (int)curl_getinfo($fallbackCurl, CURLINFO_RESPONSE_CODE);
        curl_close($fallbackCurl);

        if (is_string($fallbackBody) && $fallbackStatus >= 200 && $fallbackStatus < 300) {
            $responseBody = $fallbackBody;
            $status = $fallbackStatus;
            $useMyMemory = true;
        }
    }
}

if (!is_string($responseBody) || $status < 200 || $status >= 300) {
    error_log('Translation service request failed' . ($curlError !== '' ? ': ' . $curlError : ' (HTTP ' . $status . ').'));
    translationResponse(502, ['error' => 'translation_failed']);
}

$response = json_decode($responseBody, true);
$translatedText = null;
if ($useLibreTranslate) {
    $translatedText = is_array($response) && isset($response['translatedText']) && is_string($response['translatedText'])
        ? $response['translatedText']
        : null;
} elseif ($useMyMemory) {
    $translatedText = null;
    if (
        is_array($response)
        && isset($response['responseData']['translatedText'])
        && is_string($response['responseData']['translatedText'])
        && trim($response['responseData']['translatedText']) !== ''
    ) {
        $translatedText = $response['responseData']['translatedText'];
    } elseif (is_array($response) && isset($response['matches']) && is_array($response['matches'])) {
        foreach ($response['matches'] as $match) {
            if (
                is_array($match)
                && isset($match['translation'])
                && is_string($match['translation'])
                && trim($match['translation']) !== ''
            ) {
                $translatedText = $match['translation'];
                break;
            }
        }
    }
} elseif (is_array($response) && isset($response[0]) && is_array($response[0])) {
    $segments = [];
    foreach ($response[0] as $segment) {
        if (is_array($segment) && isset($segment[0]) && is_string($segment[0])) {
            $segments[] = $segment[0];
        }
    }
    $translatedText = $segments !== [] ? implode('', $segments) : null;
}

if (!is_string($translatedText) || $translatedText === '') {
    error_log('Translation service returned an invalid response.');
    translationResponse(502, ['error' => 'translation_failed']);
}

translationResponse(200, ['translated_text' => $translatedText]);
