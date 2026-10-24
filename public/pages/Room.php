<?php 
    session_start();
    $haURL = "";
    $haToken = "";
    
    require_once "../../src/connect_api.php";
    require_once "../../src/connect_db.php";
    require_once "../../src/functions.php";

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

?>

    <div class='device-grid'>
        <?php foreach ($devices as $device){
            if ($device['area_name'] === $room){
                echo renderDeviceCard($device, $favorites);
            }
        }?>
    </div>