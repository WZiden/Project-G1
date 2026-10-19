<?php 
    require_once "../../src/connect_api.php";
    require_once "../../src/connect_db.php";
    require_once "../functions.php";

    $devices = getAllDevices($pdo);

?>    
    
    <div class='device-grid'>
        <?php foreach ($devices as $deviceName => $deviceEntities): ?>
            <?= renderDeviceCard(
                $deviceName,
                $deviceEntities
            ) ?>
        <?php endforeach; ?>
    </div>
