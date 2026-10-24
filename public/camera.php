<?php
    require_once __DIR__ . '/../src/connect_api.php';

    $entityId = $_GET['entity_id'] ?? '';

    $url = $restURL . '/camera_proxy/' . $entityId;

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $haToken
        ]
    ]);

    $image = curl_exec($ch);

    

    $contentType = curl_getinfo(
        $ch,
        CURLINFO_CONTENT_TYPE
    );

    header(
        'Content-Type: ' .
        ($contentType ?: 'image/jpeg')
    );

    echo $image;

?>