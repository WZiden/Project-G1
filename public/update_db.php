<?php

    require_once __DIR__ . '/../src/connect_db.php';
    require_once __DIR__ . '/../src/connect_api.php';
    require_once __DIR__ . '/../src/functions.php';

    $stmt = $pdo->query("
        SELECT MAX(last_seen)
        FROM devices");

    $lastUpdate = strtotime($stmt->fetchColumn());

    if (time() - $lastUpdate > 1800) {
        $devices = getHomeAssistantDevices($haURL, $haToken);
        $entities = $entitiesByDevice = indexEntitiesByDevice(getHomeAssistantEntities($haURL, $haToken));
        $states = indexStatesByEntity(getHomeAssistantStates($haURL, $haToken));

        $devices = buildDevices($devices, $entities, $states);

        syncDevices($pdo, $devices);
    }
?>