<?php 
    session_start();

    require_once __DIR__ . '/../../src/connect_db.php';
    require_once __DIR__ . '/../../src/functions.php';

    if(isset($_SESSION['userID'])){
        $devices = json_decode(file_get_contents(__DIR__ . '/../../cache/devices.json'), true);

        $favorites = getFavorites($pdo, $_SESSION['userID']);

        echo "<div class='device-grid'>";
        foreach ($devices as $device){
            echo renderDeviceCard($device, $favorites);
        }
        echo "</div>";
    }
?>    

