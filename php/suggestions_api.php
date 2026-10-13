<?php
session_start();
require_once 'config.php';
require_once 'suggestion_helper.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $suggestions = getPeopleSuggestions($pdo, (int)$_SESSION['user_id'], 6);
    foreach ($suggestions as &$suggestion) {
        $path = $suggestion['profile_image'] ?? '';
        $suggestion['avatar_url'] = $path === ''
            ? null
            : (preg_match('/^https?:\\/\\//i', $path) ? $path : '../' . ltrim($path, '/'));
    }
    unset($suggestion);

    echo json_encode(['success' => true, 'suggestions' => $suggestions], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
} catch (Throwable $error) {
    error_log('People suggestions request failed: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to load suggestions']);
}
