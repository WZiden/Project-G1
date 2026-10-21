<?php

    require_once 'connect_db.php';
    require_once 'connect_api.php';
    require_once 'functions.php';

    $stmt = $pdo->query("
        SELECT MAX(last_seen)
        FROM entities");

    $lastUpdate = strtotime($stmt->fetchColumn());

    if (time() - $lastUpdate > 10) {
        $entites = getAPI($ch, $haURL, $haToken);

        updateDB($entites, $pdo);

        echo "Updated at " . date('H:i:s');
    }


?>