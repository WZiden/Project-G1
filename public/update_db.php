<?php

    require_once '../src/connect_db.php';
    require_once '../src/connect_api.php';
    require_once 'functions.php';

    $stmt = $pdo->query("
        SELECT MAX(last_seen)
        FROM devices");

    $lastUpdate = strtotime($stmt->fetchColumn());

    if (time() - $lastUpdate > 1800) {
        $devices = getHomeAssistantDevices($haURL, $haToken);

        syncDevices($pdo, $devices);
    }
?>