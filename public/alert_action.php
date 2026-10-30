<?php

    require_once __DIR__ . '/../src/connect_db.php';
    require_once __DIR__ . '/../src/connect_api.php';
    require_once __DIR__ . '/../src/functions.php';

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
                $stmt = $pdo->prepare("UPDATE alerts SET status = 'ACKNOWLEDGED' WHERE id = ?");
                $stmt->execute([$alert_id]);
                break;

            case 'DISMISS':
                $stmt = $pdo->prepare("UPDATE alerts SET status = 'DISMISSED' WHERE id = ?");
                $stmt->execute([$alert_id]);
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
                        $success = controlDevice($alert['entity_id'], 'turn_off', $haURL, $haToken, []);
                        $resolutionMessage = 'The light has been turned off.';
                        break;

                    case 'HEAT_WARNING':
                        $success = controlDevice($alert['entity_id'], 'set_temperature', $haURL, $haToken, ['temperature' => 18]);
                        $resolutionMessage = 'Radiator temperature was lowered to 18°C.';
                        break;
                    
                    case 'WINDOW_OPEN_HEATING':
                        $success = controlDevice($alert['entity_id'], 'set_hvac_mode', $haURL, $haToken, ['hvac_mode' => 'off']);
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

                $stmt = $pdo->prepare("UPDATE alerts SET status = 'RESOLVED', reason = ? WHERE id = ?");
                $stmt->execute([$resolutionMessage, $alert_id]);
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