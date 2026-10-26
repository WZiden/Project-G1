<?php
    require_once __DIR__ . '/../src/functions.php';
    require_once __DIR__ . '/../src/connect_api.php';

    header('Content-Type: application/json');

    $devices = getHomeAssistantDevices($haURL, $haToken);

    $entities = getHomeAssistantEntities($haURL, $haToken);

    $states = getHomeAssistantStates($haURL, $haToken);

    $devices = buildDevices($devices, indexEntitiesByDevice($entities), indexStatesByEntity($states));

    $alerts = [];

    foreach ($devices as $device)
    {
        foreach ($device['entities'] as $entity)
        {
            if (
                str_starts_with(
                    $entity['entity_id'],
                    'light.'
                )
                &&
                $entity['state'] === 'on'
            )
            {
                $alerts[] = [
                    'title' => 'Light is on',
                    'room' => $device['area_name'],
                    'time' => date('H:i')
                ];
            }
        }
    }
    echo json_encode($alerts);
?>