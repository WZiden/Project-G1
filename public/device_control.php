<?php
session_start();

require_once __DIR__ . '/../src/connect_db.php';
require_once __DIR__ . '/../src/connect_api.php';
require_once __DIR__ . '/../src/functions.php';

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
//Get the old value before it changes
$states= json_decode(file_get_contents(__DIR__ . '/../cache/entity_states.json'), true);
$oldValue = extractOldValue($states, $entityId );

if (getPermission($pdo, $userID, $deviceName)){
    try{  
      
        $success = controlDevice(
            $entityId,
            $service,
            $haURL,
            $haToken,
            $serviceData
        );  

        $newValue = extractNewValue($entityId, $service, $serviceData);

        echo json_encode(['success' => $success]);
        logDeviceAttempt ( $pdo, $userID, 1, $deviceName, $oldValue, $newValue, $success );

    }
    catch (Exception $e){
        http_response_code(500);

        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
           
        ]);
        $newValue = extractNewValue($entityId, $service, $serviceData); 
        logDeviceAttempt ( $pdo, $userID, 1, $deviceName, $oldValue, $newValue, 0 );
    }
    
}
else {
    http_response_code(403);

    echo json_encode([
        'success' => false,
        'message' => 'You do not have permission to control this device.'
    ]);
    $newValue = extractNewValue($entityId, $service, $serviceData);
    logDeviceAttempt ( $pdo, $userID, 0, $deviceName, $oldValue, $newValue, 0 );
    exit;  
}