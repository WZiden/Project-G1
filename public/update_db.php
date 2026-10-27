<?php

    require_once __DIR__ . '/../src/connect_db.php';
    require_once __DIR__ . '/../src/connect_api.php';
    require_once __DIR__ . '/../src/functions.php';

    $stmt = $pdo->query("
        SELECT MAX(last_seen)
        FROM devices");

    $lastUpdate = strtotime($stmt->fetchColumn());

    if (!$lastUpdate ||time() - $lastUpdate > 1800) {
        $devices = getHomeAssistantDevices($haURL, $haToken);
        $entities = indexEntitiesByDevice(getHomeAssistantEntities($haURL, $haToken));
        $states = indexStatesByEntity(getHomeAssistantStates($haURL, $haToken));

        $devices = buildDevices($devices, $entities, $states);

        usort($devices, function ($a, $b) {
            return strcasecmp($a['device_name'], $b['device_name']);
        });
        
        syncDevices($pdo, $devices);

        file_put_contents(
            '../cache/devices.json',
            json_encode($devices, JSON_PRETTY_PRINT)
        );
    }
?>