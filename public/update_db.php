<?php

    require_once '../src/connect_db.php';
    require_once '../src/connect_api.php';
    require_once 'functions.php';

    $devices = getHomeAssistantDevices($haURL, $haToken);

    try {
        syncDevices($pdo, $devices);
        echo "Success!";
    }
    catch (Exception $e) {
        echo "Failure...";
    }
?>