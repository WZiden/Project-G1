<?php
    session_start();

    require_once __DIR__ . '/../src/functions.php';
    require_once __DIR__ . '/../src/connect_api.php';
    require_once __DIR__ . '/../src/connect_db.php';

    header('Content-Type: application/json');

    if(isset($_SESSION['userID'])){    
        $search = trim($_GET['q'] ?? '');

        if ($search === '') {
            echo json_encode(['devices' => [],'rooms' => []]);
            exit;
        }

        $devices = searchDevices($pdo, $search);

        $rooms = searchRooms($pdo, $search);

        echo json_encode([
            'devices' => $devices,
            'rooms' => $rooms
        ]);
}
?>