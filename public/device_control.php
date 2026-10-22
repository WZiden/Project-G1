<?php

require_once '../src/connect_api.php';
require_once 'functions.php';

header('Content-Type: application/json; charset=utf-8');

$entityId = $_POST['entity_id'] ?? '';

$service = $_POST['service'] ?? '';

$serviceData = json_decode($_POST['service_data'] ?? '{}', true);

if (!$entityId || !$service){
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Saknar parametrar'
    ]);

    exit;
}

try{

    $success = controlDevice(
        $entityId,
        $service,
        $haURL,
        $haToken,
        $serviceData
    );

    echo json_encode(['success' => $success]);
}
catch (Exception $e){
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}