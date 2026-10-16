<?php 
    include "connect_db.php";
    include "connect_api.php";

    curl_setopt_array($ch, [
        CURLOPT_URL => $haURL . "states",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer {$haToken}",
            "Content-Type: application/json"
        ]
    ]);

    $response = curl_exec($ch);

    if ($response === false){
        die ('Could not connect to Home Assistant: ' . curl_error($ch));
    }

    curl_close($ch);

    $entities = json_decode($response, true);

    if (!is_array($entities)){
        die('Invalid JSON response from Home Assistant');
    } else if (empty($entities)){
        die('No entities returned from Home Assistant');
    }

    $allowedAttributes = [
        'friendly_name',
        'device_class',
        'state_class',
        'unit_of_measurement',
        'icon'
    ];

    $sql = "
    INSERT INTO entities (
        entity_id,
        state,
        friendly_name,
        attributes)
    VALUES (
        :entity_id,
        :state,
        :friendly_name,
        :attributes)
        ON DUPLICATE KEY UPDATE
            state = VALUES(state),
            friendly_name = VALUES(friendly_name),
            attributes = VALUES(attributes)";
    
    $stmt = $pdo->prepare($sql);

    if (!$stmt){
        die('Could not prepare statement');
    }

    foreach ($entities as $entity){
       $filteredAttributes = [];
       $friendly_name = "";

       foreach ($allowedAttributes as $key){
            if (isset($entity['attributes'][$key])){
                $filteredAttributes[$key] = $entity['attributes'][$key];
            }
        }    

        $friendly_name = $filteredAttributes['friendly_name'] ?? null;
        unset($filteredAttributes['friendly_name']);

         $stmt->execute([
            'entity_id'      => $entity['entity_id'],
            'state'          => $entity['state'] ?? null,
            'friendly_name'  => $friendly_name ?? null,
            'attributes'     => json_encode(
                $filteredAttributes,
                JSON_UNESCAPED_UNICODE
            )
        ]);
    }
?>