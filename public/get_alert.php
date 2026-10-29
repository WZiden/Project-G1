<?php
    require_once __DIR__ . '/../src/functions.php';
    require_once __DIR__ . '/../src/connect_api.php';
    require_once __DIR__ . '/../src/connect_db.php';

    header('Content-Type: application/json');

    $devices = getHomeAssistantDevices($haURL, $haToken);
    $entities = getHomeAssistantEntities($haURL, $haToken);
    $states = getHomeAssistantStates($haURL, $haToken);
    $devices = buildDevices($devices, indexEntitiesByDevice($entities), indexStatesByEntity($states));

    $alerts = [];
    $rooms = [];

    $outsideTemp = null;

    foreach ($devices as $device) {
        if ($device['area_name'] !== 'Outdoor')
            {
                continue;
            }
            foreach ($device['entities'] as $entity)
            {
                if (($entity['attributes']['device_class'] ?? '') === 'temperature' && is_numeric($entity['state'] ?? null))
                {
                    $outsideTemp = (float)$entity['state'];
                    break 2;
                }
            }
    }

    foreach ($devices as $device) {
        $rooms[$device['area_name']][] = $device;
    }

    foreach ($rooms as $roomName => $roomDevices) {
        $lightOn = false;
        $hasPeopleSensor = false;
        $peopleDetected = false;

        $roomTemp =  null;
        $radiatorTargetTemp = null;

        $windowOpen = false;
        $windowOpenedAt = null;

        foreach ($roomDevices as $device)
        {
            foreach ($device['entities'] as $entity)
            {
                $entityId = $entity['entity_id'];

                if (str_starts_with($entityId, 'light.') && $entity['state'] === 'on') 
                {
                    $lightOn = true;
                }

                if (($entity['attributes']['device_class'] ?? '') === 'occupancy') 
                {
                    $hasPeopleSensor = true;

                    if ($entity['state'] === 'on')
                    {
                        $peopleDetected = true;
                    }
                }
                if (($entity['attributes']['device_class'] ?? '') === 'temperature')
                {
                    if (!str_contains(strtolower($device['device_name']), 'window'))
                    {
                        $roomTemp = (float)$entity['state'];
                    }
                }
                if (str_starts_with($entity['entity_id'], 'climate.')) 
                {
                    $radiatorTargetTemp = (float)$entity['attributes']['temperature'];
                }
                if(($entity['attributes']['device_class'] ?? '') === 'window' && $entity['state'] === 'on')
                {
                    $windowOpen = true;
                    $windowOpenedAt = strtotime($entity['last_changed']);
                }
            }
        }

        $windowOpenMinutes = 0;
        if ($windowOpenedAt != null)
        {
            $windowOpenMinutes = (time() - $windowOpenedAt) / 60;
        }


        /*Lights on but no one in the room*/
        if ($hasPeopleSensor &&$lightOn && !$peopleDetected)
        {
            createAlert($pdo, 'EMPTY_ROOM_LIGHT','Lights on in a empty room', $roomName,'Lights are on while no occupancy is detected');
        }

        /*Radiator is set on over 24 while the room temp already is over 24*/
        if ($roomTemp !== null && $radiatorTargetTemp !== null && $roomTemp >= 24 && $radiatorTargetTemp >= 24) 
        {
            createAlert($pdo, 'HEAT_WARNING', 'Heat warning', $roomName, "Radiator is set to {$radiatorTargetTemp}°C while room temperature is {$roomTemp}°C");
        }


        /*Window open for 5 minutes and the radiator temp is higher 5 than the outside temperature*/
        if ($windowOpen && $windowOpenMinutes >= 5 && $radiatorTargetTemp !== null && $outsideTemp != null && $radiatorTargetTemp >= ($outsideTemp + 5)) 
        {
            createAlert($pdo, 'WINDOW_OPEN_HEATING', 'Open window while heating', $roomName, "Window has been open for ". round($windowOpenMinutes) ." minutes. Radiator: ". $radiatorTargetTemp ."°C. Outdoor: ". $outsideTemp ."°C");
        }
    }

    function createAlert(PDO $pdo, string $ruleType, string $title, string $room, string $reason): void {
        
        $stmt = $pdo->prepare("SELECT id FROM alerts WHERE rule_type = ? AND room_name = ? AND status IN ('ACTIVE', 'ACKNOWLEDGED') LIMIT 1");

        $stmt->execute([$ruleType, $room]);

        if ($stmt->fetchColumn() !== false) {
        return;
        }

        $stmt = $pdo->prepare("INSERT INTO alerts (rule_type, title, room_name, reason, status) VALUES (?, ?, ?, ?, 'ACTIVE')");
        
        $stmt->execute([$ruleType, $title, $room, $reason]);

    }

    $stmt = $pdo->query("SELECT id, rule_type, room_name AS room, title, reason, status, created_at AS time
        FROM alerts
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY created_at DESC");

    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($alerts, JSON_UNESCAPED_UNICODE);
?>