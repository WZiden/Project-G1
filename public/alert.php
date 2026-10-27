<?php
    require_once __DIR__ . '/../src/functions.php';
    require_once __DIR__ . '/../src/connect_api.php';

    header('Content-Type: application/json');

    $devices = getHomeAssistantDevices($haURL, $haToken);

    $entities = getHomeAssistantEntities($haURL, $haToken);

    $states = getHomeAssistantStates($haURL, $haToken);

    $devices = buildDevices($devices, indexEntitiesByDevice($entities), indexStatesByEntity($states));

    $alerts = [];

    $rooms = [];

    foreach ($devices as $device) {
        $rooms[$device['area_name']][] = $device;
    }

    foreach ($rooms as $roomName => $roomDevices) {
        $lightOn = false;
        $hasPeopleSensor = false;
        $peopleDetected = false;

        $roomTemp =  null;
        $radiatorTargetTemp = null;

        $windowOpen = false;
        $windowOpenedAt = null; 
        $outsideTemp = null;

        foreach ($roomDevices as $device)
        {
            foreach ($device['entities'] as $entity)
            {
                $entityId = $entity['entity_id'];

                if (str_starts_with($entityId, 'light.') && $entity['state'] === 'on') 
                {
                    $lightOn = true;
                }

                if (($entity['attributes']['device_class'] ?? '') === 'occupancy') 
                {
                    $hasPeopleSensor = true;

                    if ($entity['state'] === 'on')
                    {
                        $peopleDetected = true;
                    }
                }
                if (($entity['attributes']['device_class'] ?? '') === 'temperature')
                {
                    if (!str_contains(strtolower($device['device_name']), 'window'))
                    {
                        $roomTemp = (float)$entity['state'];
                    }
                }
                if (str_starts_with($entity['entity_id'], 'climate.')) 
                {
                    $radiatorTargetTemp = (float)$entity['attributes']['temperature'];
                }
                if(($entity['attributes']['device_class'] ?? '') === 'window' && $entity['state'] === 'on')
                {
                    $windowOpen = true;
                    $windowOpenedAt = strtotime($entity['last_changed']);
                }
                if(($entity['attributes']['device_class'] ?? '') === 'temperature' && $device['area_name'] === 'Outdoor') 
                {
                    $outsideTemp = (float)$entity['state'];
                }
            }
        }

        $windowOpenMinutes = 0;
        if ($windowOpenedAt != null)
        {
            $windowOpenMinutes = (time() - $windowOpenedAt) / 60;
        }


        /*Lights on but no one in the room*/
        if ($hasPeopleSensor &&$lightOn && !$peopleDetected)
        {
            $alerts[] = [
                'title' => 'Lights on in an empty room',
                'room' => $roomName,
                'time' => date('Y-m-d H:i')
            ];
        }

        /*Radiator is set on over 24 while the room temp already is over 24*/
        if ($roomTemp !== null && $radiatorTargetTemp !== null && $roomTemp >= 24 && $radiatorTargetTemp >= 24) 
        {
            $alerts[] = [
                'title' => "Heat warning, radiator is set to {$radiatorTargetTemp}°C and the room temperature is {$roomTemp}°C or more",
                'room' => $roomName,
                'time' => date('Y-m-d H:i')
            ];
        }


        /*Window open for 5 minutes and the radiator temp is higher than the outside temperature*/
        if ($windowOpen && $windowOpenMinutes >= 5 && $radiatorTargetTemp !== null && $outsideTemp != null && $radiatorTargetTemp > $outsideTemp) 
        {
            $alerts[] = [
                'title' => 'A window has been open for 5 or more minutes while the radiator is set higher than th eoutside temperature',
                'room' => $roomName,
                'time' => date('Y-m-d H:i')
            ];
        }
    }

    echo json_encode($alerts);
?>