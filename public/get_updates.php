<?php
session_start();

if (!isset($_SESSION['userID'])) {
    http_response_code(401);

    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

header('Content-Type: application/json');

$cacheFile = '../cache/entity_states.json';

if (!file_exists($cacheFile))
{
    echo '{}';
    exit;
}

echo file_get_contents($cacheFile);