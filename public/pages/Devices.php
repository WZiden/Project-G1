<?php 
    $haURL = "";
    $haToken = "";
    require_once '../../src/connect_api.php';
    require_once '../../src/connect_db.php';
    require_once '../functions.php';

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
        <?php foreach ($devices as $device): ?>
            <?= renderDeviceCard($device) ?>
        <?php endforeach; ?>
    </div>
