<?php
    
    require_once __DIR__ . '/../src/connect_api.php';
    require_once __DIR__ . '/../src/connect_db.php';
    require_once __DIR__ . '/../src/functions.php';

    $stmt = $pdo->query("
        SELECT device_id
        FROM devices
    ");

    $allowedDevices = array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));

    $entities = getHomeAssistantEntities($haURL, $haToken);

    $entityDeviceMap = [];

    foreach ($entities as $entity){
        $entityDeviceMap[$entity['entity_id']] = $entity['device_id'];
    }

    $client = connectHA($haURL, $haToken);

    $client->send(json_encode([
        'id' => 1,
        'type' => 'subscribe_events',
        'event_type' => 'state_changed'
    ]));

    $response = json_decode($client->receive(), true);

    if (!isset($response['success']) || !$response['success']){
        throw new Exception('Failed to subscribe');
    }

    echo "Listening...\n";

    $cacheFile = '../cache/entity_states.json';

    $states = [];

    if (file_exists($cacheFile)){
        $states = json_decode(file_get_contents($cacheFile), true) ?? [];
    }

    while (true){
        try{
            $message = json_decode($client->receive(), true);

            if (($message['type'] ?? null) !== 'event'){
                continue;
            }

            $entityId = $message['event']['data']['entity_id'] ?? null;

            $deviceId = $entityDeviceMap[$entityId] ?? null;

            if (!$deviceId){
            continue;
            }

            if (!isset($allowedDevices[$deviceId])){
                continue;
            }

            $newState = $message['event']['data']['new_state'] ?? null;

            if (!$entityId){
                continue;
            }

            if ($newState === null){
                unset($states[$entityId]);

                file_put_contents($cacheFile, json_encode($states, JSON_PRETTY_PRINT));

                continue;
            }

            $states[$entityId] = ['state' => $newState['state'],'attributes' => $newState['attributes']];

            file_put_contents($cacheFile, json_encode($states, JSON_PRETTY_PRINT));
        }
        catch (\WebSocket\TimeoutException $e){
            continue;
        }
    }