<?php
    const MAIN_ENTITY_DOMAINS = [
        'light',
        'switch',
        'climate',
        'cover',
        'lock',
        'binary_sensor'
    ];


    function getAPI(CurlHandle $ch, string $haURL, string $haToken):array {
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

        if (!is_array($entities)) {
            die('Invalid JSON response from Home Assistant');
        }

        if (empty($entities)) {
            die('No entities returned from Home Assistant');
        }

        return $entities;
    }

    function getDevices(array $entities):array {

        $entities = filterEntities($entities);

        $objectIds = [];

        foreach ($entities as $entity) {

            $objectId = explode('.', $entity['entity_id'], 2)[1];

            $objectIds[$objectId] = true;
        }        

        return $objectIds;
    }

    function filterEntities(array $entities):array {
        $allowedDomains = [
            'light',
            'switch',
            'sensor',
            'binary_sensor',
            'camera',
            'climate',
            'cover',
            'lock',
            'vacuum',
            'weather',
            'select'
        ];

        $excludedPrefixes = [
            'sim_',
            'backup_',
            'sun_'
        ];

        $filtered = [];

        foreach ($entities as $entity) {
            if (!isset($entity['entity_id'])) {
                continue;
            }

            list($domain, $objectId) = explode('.', $entity['entity_id'], 2);

            // Keep allowed domains
            if (!in_array($domain, $allowedDomains)) {
                continue;
            }

            // Filter unwanted domains
            $skip = false;

            foreach ($excludedPrefixes as $prefix) {

                if (str_starts_with($objectId, $prefix)) {
                    $skip = true;
                    break;
                }
            }

            if ($skip) {
                continue;
            }

            $filtered[] = $entity;
        }

        return $filtered;
    }

    function getGroups(array $entities, array $objectIds):array {
        $groups = [];

        foreach ($entities as $entity) {

            $groupKey = getGroupKey(
                $entity['entity_id'],
                $objectIds
            );

            $groups[$groupKey][] = $entity;
        }

        ksort($groups);

        return $groups;
    }

    function getGroupKey(string $entityId, array $objectIds):string {
        $objectId = explode('.', $entityId, 2)[1];

        $parts = explode('_', $objectId);

        while (count($parts) > 1) {

            array_pop($parts);

            $candidate = implode('_', $parts);

            if (isset($objectIds[$candidate])) {
                return $candidate;
            }
        }

        return $objectId;
    }

    function updateDB(array $entities, PDO $pdo){

        $objectIds = getDevices($entities);

        $devices = getGroups(
            filterEntities($entities),
            $objectIds
        );

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
            attributes,
            device,
            last_seen
        )
        VALUES (
            :entity_id,
            :state,
            :friendly_name,
            :attributes,
            :device,
            NOW()
        )
        ON DUPLICATE KEY UPDATE
            device = VALUES(device),
            state = VALUES(state),
            friendly_name = VALUES(friendly_name),
            attributes = VALUES(attributes),
            last_seen = NOW()
        ";

        $stmt = $pdo->prepare($sql);

        if (!$stmt) {
            die('Could not prepare statement');
        }

        foreach ($devices as $deviceName => $deviceEntities) {

            foreach ($deviceEntities as $entity) {

                $filteredAttributes = [];

                foreach ($allowedAttributes as $key) {
                    if (array_key_exists($key, $entity['attributes'] ?? [])) {
                        $filteredAttributes[$key] = $entity['attributes'][$key];
                    }
                }

                $friendlyName =
                    $filteredAttributes['friendly_name']
                    ?? null;

                unset($filteredAttributes['friendly_name']);

                $stmt->execute([
                    'entity_id'     => $entity['entity_id'],
                    'device'        => $deviceName,
                    'state'         => $entity['state'] ?? null,
                    'friendly_name' => $friendlyName,
                    'attributes'    => json_encode(
                        $filteredAttributes,
                        JSON_UNESCAPED_UNICODE
                    )
                ]);
            }
        }
    }

    function getAllDevices(PDO $pdo):array {
        $stmt = $pdo->query("
            SELECT *
            FROM entities
            ORDER BY device, friendly_name
        ");

        $entities = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($entities as &$entity) {
            $entity['attributes'] = json_decode($entity['attributes'], true) ?? [];
        }

        $devices = [];

        foreach ($entities as $entity) {
            $devices[$entity['device']][] = $entity;
        }

        return $devices;
    }

    function filterDevices(array $devices, callable $callback):array {

        return array_filter($devices, $callback, ARRAY_FILTER_USE_BOTH);
    }

    function getMainEntity(array $deviceEntities):?array {
        foreach ($deviceEntities as $entity) {

            $domain = explode('.', $entity['entity_id'], 2)[0];
            if (in_array($domain, MAIN_ENTITY_DOMAINS)) {
                return $entity;
            }
        }
        return null;
    }

    function getSecondaryEntities(array $deviceEntities, ?array $mainEntity, array $excludeClasses = []):array {
        $secondary = [];

        foreach ($deviceEntities as $entity) {
            if ($mainEntity && $entity['entity_id'] === $mainEntity['entity_id']) {
                continue;
            }

            $deviceClass = $entity['attributes']['device_class'] ?? '';

            if (in_array($deviceClass, $excludeClasses)) {
                continue;
            }

            $secondary[] = $entity;
        }

        return $secondary;
    }

    function formatMeasurement(array $entity): string {

        $value = $entity['state'] ?? '';

        $unit =
            $entity['attributes']['unit_of_measurement']
            ?? '';

        return trim($value . ' ' . $unit);
    }

    function formatState(array $entity): string {
        $state = strtolower($entity['state'] ?? '');

        $deviceClass =
            $entity['attributes']['device_class']
            ?? '';

        switch ($deviceClass) {
            case 'door':
                return $state === 'on'
                    ? 'Open'
                    : 'Closed';
            case 'window':
                return $state === 'on'
                    ? 'Open'
                    : 'Closed';
            case 'motion':
                return $state === 'on'
                    ? 'Motion Detected'
                    : 'No Motion';
            case 'occupancy':
                return $state === 'on'
                    ? 'Occupied'
                    : 'Unoccupied';
        }

        return ucfirst($state);
    }

    function getShortLabel(string $friendlyName, string $deviceTitle):string {
        $label = str_replace( $deviceTitle, '', $friendlyName);

        $label = trim($label);

        return $label !== '' ? $label : $friendlyName;
    }

    function getDeviceTitle(string $deviceName, array $deviceEntities):string {
        foreach ($deviceEntities as $entity) {
            if (!empty($entity['friendly_name'])) {
                return $entity['friendly_name'];
            }
        }

        return ucfirst(str_replace('_', ' ', $deviceName));
    }

    function renderDeviceCard(string $deviceName, array $deviceEntities):string {

        $mainEntity = getMainEntity($deviceEntities);

        $secondaryEntities = getSecondaryEntities($deviceEntities, $mainEntity);

        $html = "<div class='device-card'>";
        
        $title = getDeviceTitle($deviceName, $deviceEntities);

        $html .= "<h2 class='device-title'>" . htmlspecialchars($title) ."</h2>";

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        if ($mainEntity) {
            $html .= "<div class='device-status-label'>";
            $html .= "Status: ";
            $html .= "</div>";
            $html .= "<div class='device-status-value'>";
            $html .= htmlspecialchars(formatState($mainEntity));
            $html .= "</div>";
        }

        /*
        |--------------------------------------------------------------------------
        | ON/OFF knapp
        |--------------------------------------------------------------------------
        */

        if ($mainEntity && (
            str_starts_with($mainEntity['entity_id'], 'light.') ||
            str_starts_with($mainEntity['entity_id'], 'switch.'))) {

            $html .= "<div class='device-controls'>";

            $buttonText = $mainEntity['state'] === 'on' ? 'TURN OFF' : 'TURN ON';

            $buttonColor = $mainEntity['state'] === 'on' ? '#dc3545' : '#198754';

            $html .= "<form method='post'>
                <input type='hidden' name='entity_id' value='{$mainEntity['entity_id']}'>
                    <input type='hidden' name='action' value='" . ($mainEntity['state'] === 'on' ? 'turn_off' : 'turn_on') . "'>
                        <button class='device-button' type='submit' style='
                                background-color: {$buttonColor};
                                color: white;
                                border: none;
                                border-radius: 6px;
                                padding: 8px 16px;
                                cursor: pointer;'>
                            {$buttonText}
                        </button>
            </form>";
            
            $html .= "</div>";
        }
        
        $html .= "<div class='device-details'>";

        foreach ($secondaryEntities as $entity) {
            $label = getShortLabel($entity['friendly_name'], $title);
            if (!empty($entity['attributes']['unit_of_measurement'])) {
                $value = formatMeasurement($entity);
            } else {
                $value = formatState($entity);
            }

            $html .= "<div class='device-detail-label'>";
            $html .= htmlspecialchars($label);
            $html .= "</div>";

            $html .= "<div class='device-detail-value'>";
            $html .= htmlspecialchars($value);
            $html .= "</div>";
        }

        $html .= "</div>";

        $html .= "</div>";

        return $html;
    }

    function controlDevice(string $entityId, string $action, string $haURL, string $haToken):bool {
        $allowedActions = [
            'turn_on',
            'turn_off'
        ];

        if (!in_array($action, $allowedActions)) {
            return false;
        }
        return true;
    }
?>