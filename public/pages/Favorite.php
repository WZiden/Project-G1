<?php 
    session_start();

    require_once __DIR__ . '/../../src/connect_db.php';
    require_once __DIR__ . '/../../src/functions.php';

    if(isset($_SESSION['userID'])){
        $favorites = getFavorites($pdo, $_SESSION['username']);
        $devices = json_decode(file_get_contents(__DIR__ . '/../../cache/devices.json'), true);

        echo "<div class='device-grid'>";
        foreach ($devices as $device){
            if(in_array($device['device_id'], $favorites, true)){
                echo renderDeviceCard($device, $favorites);
            }
        }
        echo "</div>";
    }
?>