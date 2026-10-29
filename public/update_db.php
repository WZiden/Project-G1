<?php

    require_once __DIR__ . '/../src/connect_db.php';
    require_once __DIR__ . '/../src/connect_api.php';
    require_once __DIR__ . '/../src/functions.php';

    $stmt = $pdo->query("
        SELECT MAX(last_seen)
        FROM devices");

    $lastUpdate = strtotime($stmt->fetchColumn());

    if (!$lastUpdate ||time() - $lastUpdate > 1800) {
        $devices = getHomeAssistantDevices($haURL, $haToken);
        $entities = indexEntitiesByDevice(getHomeAssistantEntities($haURL, $haToken));
        $states = indexStatesByEntity(getHomeAssistantStates($haURL, $haToken));

        $devices = buildDevices($devices, $entities, $states);

        $entities = [];

        foreach ($devices as $device) {
            foreach ($device['entities'] as $entity) {
                $domain = explode('.', $entity['entity_id'], 2)[0];

                if($domain == 'sensor'){                    
                    $deviceClass = $entity['attributes']['device_class'] ?? 'unknown';

                    $entities[$deviceClass][] = [
                        'entity_id'     => $entity['entity_id'],
                        'friendly_name' => $entity['attributes']['friendly_name'] ?? '',
                        'area_name'     => $device['area_name'],
                        'unit'          => $entity['attributes']['unit_of_measurement'] ?? ''
                    ];
                }
            }
        }

        usort($devices, function ($a, $b) {
            return strcasecmp($a['device_name'], $b['device_name']);
        });
        
        syncDevices($pdo, $devices);

        file_put_contents(
            '../cache/devices.json',
            json_encode($devices, JSON_PRETTY_PRINT)
        );

        file_put_contents(
            '../cache/sensores.json',
            json_encode($entities, JSON_PRETTY_PRINT)
        );
    }
?>