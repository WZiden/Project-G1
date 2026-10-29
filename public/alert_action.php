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
        /*
        * Här kommer Home Assistant-logiken senare
        */
                $stmt = $pdo->prepare("UPDATE alerts SET status = 'RESOLVED' WHERE id = ?");
                $stmt->execute([$alert_id]);
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