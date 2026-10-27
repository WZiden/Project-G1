<?php

require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/connect_api.php';

header('Content-Type: application/json');

$endDate = new DateTime('now');
$timestamp = date('c', strtotime('-12 months'));

$devices = json_decode(file_get_contents(__DIR__ . '/../cache/devices.json'), true);

$entities = '';

foreach($devices as $device){
    foreach($device['entities'] as $entity){
        $entities .= $entity['entity_id'] . ',';
    }
}

$url = $restURL . '/history/period/' . urlencode($timestamp) . '&filter_entity_id=' . $entities;

$ch = curl_init($url);

curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $haToken, 'Content-Type: application/json']]);

$response = curl_exec($ch);

if (curl_errno($ch)) {

    echo json_encode([
        'error' => curl_error($ch)
    ]);

    curl_close($ch);
    exit;
}

curl_close($ch);

echo '<pre>';
print_r(json_decode($response, true));
echo '</pre>';
exit;

$data = json_decode($response, true);

$history = $data[0] ?? [];

$groups = [];

foreach ($history as $entry) {
    $state = $entry['state'] ?? null;

    if (!is_numeric($state)) {
        continue;
    }

    $timestamp = strtotime($entry['last_changed']);

    switch ($groupBy) {
        case 'month':
            $key = date('Y-m', $timestamp);
            break;

        case 'day':
            $key = date('Y-m-d', $timestamp);
            break;

        case 'hour':
        default:
            $key = date('Y-m-d H:00', $timestamp);
            break;
    }

    $groups[$key][] = (float)$state;
}

$labels = [];
$values = [];

foreach ($groups as $label => $states){ 
    $labels[] = $label;

    $values[] = round(array_sum($states) / count($states), 2);
}

$unit = $history[0]['attributes']['unit_of_measurement'] ?? '';

print_r(json_encode([
    'labels' => $labels,
    'values' => $values,
    'unit' => $unit
]));