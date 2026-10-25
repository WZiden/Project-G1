<?php 
    session_start();
    
    require_once __DIR__ . '/../../src/connect_api.php';
    require_once __DIR__ . '/../../src/connect_db.php';
    require_once __DIR__ . '/../../src/functions.php';

    if(isset($_SESSION['userID'])){
        $room = $_GET['room'] ?? '';

        $favorites = getFavorites($pdo, $_SESSION['username']);

        $devices = getAllDevices($pdo);
        $entities = getHomeAssistantEntities(
            $haURL,
            $haToken
        );
        $states = getHomeAssistantStates(
            $haURL,
            $haToken
        );
        $entitiesByDevice = indexEntitiesByDevice($entities);
        $statesByEntity = indexStatesByEntity($states);

        $devices = buildDevices(
            $devices,
            $entitiesByDevice,
            $statesByEntity
        );

        echo "<div class='device-grid'>";
        foreach ($devices as $device){
            if ($device['area_name'] === $room){
                echo renderDeviceCard($device, $favorites);
            }
        }
        echo "</div>";
    }

?>