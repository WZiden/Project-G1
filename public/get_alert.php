<?php
    session_start();

    if (!isset($_SESSION['userID']))
    {
        http_response_code(401);

        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit;
    }
    
    require_once __DIR__ . '/../src/functions.php';
    require_once __DIR__ . '/../src/connect_api.php';
    require_once __DIR__ . '/../src/connect_db.php';

    header('Content-Type: application/json');

    $devices = json_decode(file_get_contents(__DIR__ . '/../cache/devices.json'), true);
    $states = json_decode(file_get_contents(__DIR__ . '/../cache/entity_states.json'), true);

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
                $entityId = $entity['entity_id'];

                if (($entity['attributes']['device_class'] ?? '') === 'temperature' && is_numeric($states[$entityId]['state'] ?? null))
                {
                    $outsideTemp = (float)($states[$entityId]['state'] ?? 0);
                    break 2;
                }
            }
    }

    foreach ($devices as $device) {
        $rooms[$device['area_name']][] = $device;
    }

    foreach ($rooms as $roomName => $roomDevices) {
        $lightOn = false;
        $lightEntityId = null;
        $hasPeopleSensor = false;
        $peopleDetected = false;

        $roomTemp =  null;
        $radiatorTargetTemp = null;
        $radiatorEntityId = null;

        $windowOpen = false;
        $windowOpenedAt = null;

        foreach ($roomDevices as $device)
        {
            foreach ($device['entities'] as $entity)
            {
                $entityId = $entity['entity_id'];
                $currentState = $states[$entityId] ?? null;

                if (str_starts_with($entityId, 'light.') && ($states[$entityId]['state'] ?? '') === 'on') 
                {
                    $lightOn = true;
                    $lightEntityId = $entityId;
                }

                if (($entity['attributes']['device_class'] ?? '') === 'occupancy') 
                {
                    $hasPeopleSensor = true;

                    if (($states[$entityId]['state'] ?? '') === 'on')
                    {
                        $peopleDetected = true;
                    }
                }
                if (($entity['attributes']['device_class'] ?? '') === 'temperature')
                {
                    $deviceName = strtolower($device['device_name']);

                    if (!str_contains($deviceName, 'radiator') && !str_contains($deviceName, 'window'))
                    {
                        $roomTemp = (float)($states[$entityId]['state'] ?? 0);
                    }
                }
                if (str_starts_with($entity['entity_id'], 'climate.')) 
                {
                    $radiatorEntityId = $entity['entity_id'];
                    $radiatorTargetTemp = (float)($states[$entityId]['attributes']['temperature'] ?? 0);
                }
                if(($entity['attributes']['device_class'] ?? '') === 'window' && ($states[$entityId]['state'] ?? '') === 'on')
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
            createAlert($pdo, 'EMPTY_ROOM_LIGHT','Lights on in a empty room', $roomName,'Lights are on while no occupancy is detected', $lightEntityId);
        }

        /*Radiator is set on over 24 while the room temp already is over 24*/
        if ($roomTemp !== null && $radiatorTargetTemp !== null && $roomTemp >= 24 && $radiatorTargetTemp >= 24) 
        {
            createAlert($pdo, 'HEAT_WARNING', 'Heat warning', $roomName, "Radiator is set to {$radiatorTargetTemp}°C while room temperature is {$roomTemp}°C", $radiatorEntityId);
        }


        /*Window open for 5 minutes and the radiator temp is higher 5 than the outside temperature*/
        if ($windowOpen && $windowOpenMinutes >= 5 && $radiatorTargetTemp !== null && $outsideTemp != null && $radiatorTargetTemp >= ($outsideTemp + 5)) 
        {
            createAlert($pdo, 'WINDOW_OPEN_HEATING', 'Open window while heating', $roomName, "Window has been open for ". round($windowOpenMinutes) ." minutes. Radiator: ". $radiatorTargetTemp ."°C. Outdoor: ". $outsideTemp ."°C", $radiatorEntityId);
        }
    }

    function createAlert(PDO $pdo, string $ruleType, string $title, string $room, string $reason, ?string $entityId): void {
        $stmt = $pdo->prepare("SELECT id FROM alerts WHERE rule_type = ? AND entity_id = ? AND (status IN ('ACTIVE', 'ACKNOWLEDGED') OR (status IN ('DISMISSED', 'RESOLVED') AND updated_at >= DATE_SUB(NOW(), INTERVAL 35 MINUTE)))LIMIT 1");

        $stmt->execute([$ruleType, $entityId]);

        if ($stmt->fetchColumn() !== false) {
        return;
        }

        $stmt = $pdo->prepare("INSERT INTO alerts (rule_type, title, room_name, reason, entity_id, status) VALUES (?, ?, ?, ?, ?, 'ACTIVE')");
        
        $stmt->execute([$ruleType, $title, $room, $reason, $entityId]);

    }

    $stmt = $pdo->query("SELECT id, rule_type, entity_id, room_name AS room, title, reason, status, created_at AS time
        FROM alerts
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY created_at DESC");

    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($alerts, JSON_UNESCAPED_UNICODE);
?>
