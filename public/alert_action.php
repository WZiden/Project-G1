<?php

    session_start();
    
    require_once __DIR__ . '/../src/connect_db.php';
    require_once __DIR__ . '/../src/connect_api.php';
    require_once __DIR__ . '/../src/functions.php';


    function getDeviceNameByEntityId(string $entityId, array $devices): string
    {
        foreach ($devices as $device)
        {
            foreach ($device['entities'] as $entity)
            {
                if ($entity['entity_id'] === $entityId)
                {
                    return $device['device_name'];
                }
            }
        }
        return $entityId;
    }



    header('Content-Type: application/json');

    $alert_id = (int)($_POST['alert_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if (!$alert_id || !$action)
    {
        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'Missing parameters'
        ]);

        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM alerts WHERE id = ?");

    $stmt->execute([$alert_id]);

    $alert = $stmt->fetch(PDO::FETCH_ASSOC);

    $userID = $_SESSION['userID'] ?? 0;
    $userName = $_SESSION['username'] ?? 'Unknown';

    if (!$alert)
    {
        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Alert not found'
        ]);

        exit;
    }

    try{
        switch ($action)
        {
            case 'ACKNOWLEDGE':
                $stmt = $pdo->prepare("UPDATE alerts SET status = 'ACKNOWLEDGED', handled_by = ? WHERE id = ?");
                $stmt->execute([$userName, $alert_id]);
                break;

            case 'DISMISS':
                $stmt = $pdo->prepare("UPDATE alerts SET status = 'DISMISSED', handled_by = ? WHERE id = ?");
                $stmt->execute([$userName, $alert_id]);
                break;

            case 'RESOLVE':

                if(empty($alert['entity_id']))
                {
                    throw new Exception(
                        'Alert has no entity id'
                    );
                }

                $success = false;
                $resolutionMessage = '';

                switch ($alert['rule_type'])
                {
                    case 'EMPTY_ROOM_LIGHT':
                        $devices = json_decode(file_get_contents(__DIR__ . '/../cache/devices.json'), true);
                        $success = true;

                        $states = indexStatesByEntity(getHomeAssistantStates($haURL, $haToken));
                       
                        foreach ($devices as $device)
                        {
                            if ($device['area_name'] !== $alert['room_name'])
                            {
                                continue;
                            }

                            foreach ($device['entities'] as $entity)
                            {
                                if (str_starts_with($entity['entity_id'], 'light.'))
                                {
                                    $oldValue = extractOldValue($states, $entity['entity_id']);
                                    if ($oldValue !== 'on')
                                        {
                                            continue;
                                        }
                                    $result = controlDevice($entity['entity_id'], 'turn_off', $haURL, $haToken, []);
                                    if (!$result)
                                    {
                                        $success = false;
                                        continue;
                                    }
                                    $newValue = 'off';
                                    logDeviceAttempt($pdo, $userID, true, $device['device_name'], $oldValue, $newValue, true);
                                }
                            }
                        }
                        $resolutionMessage = 'All lights in the room have been turned off.';
                        break;

                    case 'HEAT_WARNING':
                        $states = indexStatesByEntity(getHomeAssistantStates($haURL, $haToken));
                        $oldValue = extractOldValue($states, $alert['entity_id']);
                        $targetTemp = 18;

                        $success = controlDevice($alert['entity_id'], 'set_temperature', $haURL, $haToken, ['temperature' => $targetTemp]);
                        $newValue = $targetTemp;

                        $devices = json_decode(file_get_contents(__DIR__ . '/../cache/devices.json'), true);
                        $deviceName = getDeviceNameByEntityId($alert['entity_id'], $devices);

                        if ($success)
                        {
                            logDeviceAttempt($pdo, $userID, true, $deviceName, $oldValue, $newValue, true);
                        }
        
                        $resolutionMessage = 'Radiator temperature was lowered to 18°C.';
                        break;
                    
                    case 'WINDOW_OPEN_HEATING':
                        $states = indexStatesByEntity(getHomeAssistantStates($haURL, $haToken));
                        $oldValue = extractOldValue($states, $alert['entity_id']);

                        $newValue = 'off';

                        $success = controlDevice($alert['entity_id'], 'set_hvac_mode', $haURL, $haToken, ['hvac_mode' => 'off']);
                        
                        $devices = json_decode(file_get_contents(__DIR__ . '/../cache/devices.json'),true);
                        $deviceName = getDeviceNameByEntityId($alert['entity_id'],$devices);
                        if ($success)
                        {
                            logDeviceAttempt($pdo, $userID, true, $deviceName, $oldValue, $newValue, true);
                        }

                        $resolutionMessage = 'Radiator was turned off because the window is open.';
                        break;
                    
                    default:
                        throw new Exception(
                            'Unsupported rule type'
                        );
                }

                if(!$success)
                {
                    throw new Exception('Failed to resolve alert');
                }

                $stmt = $pdo->prepare("UPDATE alerts SET status = 'RESOLVED', handled_by = ?, reason = ? WHERE id = ?");
                $stmt->execute([$userName, $resolutionMessage, $alert_id]);
                break;

            default:
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Unknown action'
                ]);
                exit;
        }
        echo json_encode([
        'success' => true
        ]);
    }
    catch (Exception $e)
    {
        http_response_code(500);

        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
?>