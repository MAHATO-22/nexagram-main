<?php
$query = $_SERVER['QUERY_STRING'] ?? '';
$destination = 'php/index.php';
if ($query !== '') {
    $destination .= '?' . $query;
}

header('Location: ' . $destination);
exit;
