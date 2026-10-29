<?php

require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/connect_api.php';

header('Content-Type: application/json');

$start = $_GET['start'] ?? '-7 days';
$type = $_GET['class'] ?? 'temperature';
$ent = $_GET['ent'] ?? null;
$area = $_GET['area'] ?? null;
 
$entities = json_decode(file_get_contents(__DIR__ . '/../cache/sensores.json'), true);

$startDate = new DateTime('now', new DateTimeZone('Europe/Stockholm'));
$startDate->modify($start);
$startDate->setTime(0, 0, 0);

$endDate = new DateTime('now', new DateTimeZone('Europe/Stockholm'));
$endDate->setTime(0, 0, 0);

$entities = $entities[$type] ?? [];

if(!empty($ent)){
    $entities = array_filter($entities, fn($entity) => $entity['entity_id'] === $ent);
}else if(!empty($area)){
    $entities = array_filter($entities, fn($entity) => $entity['area_name'] === $area);
}

$history = getEntityHistory($restURL, $entities, $startDate->format('Y-m-d\TH:i:sP'), $endDate->format('Y-m-d\TH:i:sP'), $haToken);


echo json_encode(sortHistoryData($start, $history, $entities));