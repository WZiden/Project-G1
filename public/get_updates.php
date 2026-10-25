<?php

header('Content-Type: application/json');

$cacheFile = '../cache/entity_states.json';

if (!file_exists($cacheFile))
{
    echo '{}';
    exit;
}

echo file_get_contents($cacheFile);