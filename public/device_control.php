<?php
session_start();

require_once '../src/connect_db.php';
require_once '../src/connect_api.php';
require_once 'functions.php';

header('Content-Type: application/json; charset=utf-8');

$entityId = $_POST['entity_id'] ?? '';

$service = $_POST['service'] ?? '';

$serviceData = json_decode($_POST['service_data'] ?? '{}', true);

$deviceName = $_POST['device'];

$userID = $_SESSION['userID'];

if (!$entityId || !$service){
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Saknar parametrar'
    ]);

    exit;
}

if (getPermission($pdo, $userID, $deviceName)){
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
}else {
    http_response_code(403);

    echo json_encode([
        'success' => false,
        'message' => 'You do not have permission to control this device.'
    ]);
    exit;
}