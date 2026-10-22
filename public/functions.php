<?php
    require_once __DIR__ . '/vendor/autoload.php';

    use WebSocket\Client;


    function getHomeAssistantDevices(string $haURL, string $haToken):array {
        $client = new Client($haURL);

        // Auth required
        $client->receive();

        // Auth
        $client->send(json_encode([
            "type" => "auth",
            "access_token" => $haToken
        ]));

        $auth = json_decode($client->receive(), true);

        if (($auth['type'] ?? null) !== 'auth_ok') {
            throw new Exception("Failed to authenticate with Home Assistant");
        }

        // Device registry
        $client->send(json_encode([
            "id" => 1,
            "type" => "config/device_registry/list"
        ]));

        $response = json_decode($client->receive(), true);

        if (
            !isset($response['success']) ||
            !$response['success']
        ) {
            throw new Exception("Failed to fetch devices");
        }

        $devices = [];
        $areas = getHomeAssistantAreas($haURL, $haToken);

        foreach ($response['result'] as $device)
        {
            // Hoppa över Home Assistants interna service-enheter
            if (($device['entry_type'] ?? null) === 'service')
            {
                continue;
            }    

            $devices[] = [
                'device_id'   => $device['id'],
                'device_name' => $device['name'],
                'area_id'     => $device['area_id'],
                'area_name'   => $areas[$device['area_id']] ?? null
            ];
        }

        return $devices;
    }

    function getHomeAssistantAreas(string $haURL, string $haToken):array {
        $client = new Client($haURL);

        $client->receive();

        $client->send(json_encode([
            "type" => "auth",
            "access_token" => $haToken
        ]));

        $auth = json_decode($client->receive(), true);

        if (($auth['type'] ?? null) !== 'auth_ok') {
            throw new Exception(
                "Failed to authenticate with Home Assistant"
            );
        }

        $client->send(json_encode([
            "id" => 1,
            "type" => "config/area_registry/list"
        ]));

        $response = json_decode(
            $client->receive(),
            true
        );

        if (
            !isset($response['success']) ||
            !$response['success']
        ) {
            throw new Exception(
                "Failed to fetch areas"
            );
        }

        $areas = [];

        foreach ($response['result'] as $area)
        {
            $areas[$area['area_id']] = $area['name'];
        }

        return $areas;
    }

    function getHomeAssistantEntities(string $haURL, string $haToken):array {

        $client = new Client($haURL);

        // Auth required
        $client->receive();

        // Auth
        $client->send(json_encode([
            "type" => "auth",
            "access_token" => $haToken
        ]));

        $auth = json_decode($client->receive(), true);

        if (($auth['type'] ?? null) !== 'auth_ok') {
            throw new Exception(
                "Failed to authenticate with Home Assistant"
            );
        }

        // Entity registry
        $client->send(json_encode([
            "id" => 1,
            "type" => "config/entity_registry/list"
        ]));

        $response = json_decode(
            $client->receive(),
            true
        );

        if (
            !isset($response['success']) ||
            !$response['success']
        ) {
            throw new Exception(
                "Failed to fetch entities"
            );
        }

        $entities = [];

        foreach ($response['result'] as $entity)
        {
            // Hoppa över entiteter utan device-koppling
            if (empty($entity['device_id']))
            {
                continue;
            }

            $entities[] = [
                'entity_id' => $entity['entity_id'],
                'device_id' => $entity['device_id']
            ];
        }

        return $entities;
    }

    function getHomeAssistantStates(string $haURL, string $haToken):array {
        $client = new Client($haURL);

        // auth required
        $client->receive();

        // auth
        $client->send(json_encode([
            'type' => 'auth',
            'access_token' => $haToken
        ]));

        $auth = json_decode(
            $client->receive(),
            true
        );

        if (($auth['type'] ?? null) !== 'auth_ok')
        {
            throw new Exception(
                'Failed to authenticate'
            );
        }

        $client->send(json_encode([
            'id' => 1,
            'type' => 'get_states'
        ]));

        $response = json_decode(
            $client->receive(),
            true
        );

        if (
            !isset($response['success']) ||
            !$response['success']
        ) {
            throw new Exception(
                'Failed to fetch states'
            );
        }

        return filterEntities(
            $response['result']
        );
    }

    function getAllRooms(PDO $pdo):array {
        $stmt = $pdo->query("
            SELECT DISTINCT area_name
            FROM devices
            WHERE area_name IS NOT NULL AND area_name != 'Simulator'
            ORDER BY area_name
        ");

        $rooms = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return $rooms;
    }

    function syncDevices(PDO $pdo, array $devices){
        $pdo->exec("
            UPDATE devices
            SET is_active = 0
        ");
    
        $stmt = $pdo->prepare("
            INSERT INTO devices (
                device_id,
                device_name,
                area_id,
                area_name,
                last_seen,
                is_active
            )
            VALUES (
                :device_id,
                :device_name,
                :area_id,
                :area_name,
                NOW(),
                1
            )
            ON DUPLICATE KEY UPDATE
                device_name = VALUES(device_name),
                area_id = VALUES(area_id),
                area_name = VALUES(area_name),
                last_seen = NOW(),
                is_active = 1
        ");

        foreach ($devices as $device)
        {
            $stmt->execute([
                ':device_id'   => $device['device_id'],
                ':device_name' => $device['device_name'],
                ':area_id'     => $device['area_id'],
                ':area_name'   => $device['area_name']
            ]);
        }
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

    function getAllDevices(PDO $pdo):array {
        $stmt = $pdo->query("
            SELECT *
            FROM devices
            WHERE is_active = 1
            ORDER BY device_name
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function indexEntitiesByDevice(array $entities):array {

        $indexed = [];

        foreach ($entities as $entity)
        {
            $indexed[
                $entity['device_id']
            ][] = $entity['entity_id'];
        }

        return $indexed;
    }

    function indexStatesByEntity(array $states):array {

        $indexed = [];

        foreach ($states as $state)
        {
            $indexed[
                $state['entity_id']
            ] = $state;
        }

        return $indexed;
    }

    function buildDevices(array $devices, array $entitiesByDevice, array $statesByEntity):array {
        $result = [];

        foreach ($devices as $device)
        {
            $deviceEntities = [];

            foreach (
                $entitiesByDevice[
                    $device['device_id']
                ] ?? []
                as $entityId
            )
            {
                if (
                    isset(
                        $statesByEntity[$entityId]
                    )
                ) {
                    $deviceEntities[] =
                        $statesByEntity[$entityId];
                }
            }

        if (empty($deviceEntities))
        {
            continue;
        }

        $result[$device['device_id']] = [
            'device_id' => $device['device_id'],
            'device_name' => $device['device_name'],
            'area_name' => $device['area_name'],
            'required_user_level' => $device['required_user_level'],
            'entities' => $deviceEntities
        ];
        }

        return $result;
    }

    function getFavorites(PDO $pdo, string $userID):array {
        $stmt = $pdo->prepare("
            SELECT f.`device_id`
            FROM `favorites` f
            JOIN `users` u ON u.`userID` = f.`userID`
            WHERE u.`username` = :username
        ");

        $stmt->execute([
            ':username' => $userID
        ]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    function renderDeviceCard(array $device, array $favorites):string {
        $entities = $device['entities'];

        $isFavorite = in_array($device['device_id'], $favorites, true);

        $mainEntity = null;
        $secondaryEntities = [];

        foreach ($entities as $entity)
        {
            $domain = explode('.', $entity['entity_id'], 2)[0];

            if (
                $mainEntity === null &&
                in_array($domain, ["switch", "light"] )
            ) {
                $mainEntity = $entity;
            }
            else
            {
                $secondaryEntities[] = $entity;
            }
        }

        $html = "<div class='device-card'>";

        $html .= "<div class='device-card-header'>";
        $html .= "<h2 class='device-title'>";
        $html .= htmlspecialchars($device['device_name']);
        $html .= "</h2>";

        $html .= "
            <form method='post' class='favorite-form'>
                <input type='hidden' name='device_id' value='" . htmlspecialchars(
                        (string)$device['device_id'],
                        ENT_QUOTES,
                        'UTF-8') . "'>

                <button type='submit' name='action' value='toggle_favorite' class='favorite-button' aria-pressed='
                " . ($isFavorite ? 'true' : 'false') . "'>
                    <i class='" . ($isFavorite ? 'fa-solid' : 'fa-regular') . " fa-star'></i>
                </button>
            </form>
        ";
        $html .= "</div>";

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        if ($mainEntity)
        {
            $html .= "<div class='device-status-label'>";
            $html .= "Status:";
            $html .= "</div>";

            $html .= "<div class='device-status-value'>";
            $html .= htmlspecialchars(ucfirst($mainEntity['state']));
            $html .= "</div>";
        }

        /*
        |--------------------------------------------------------------------------
        | ON/OFF knapp
        |--------------------------------------------------------------------------
        */

        if (
            $mainEntity &&
            (
                str_starts_with($mainEntity['entity_id'], 'light.') ||
                str_starts_with($mainEntity['entity_id'], 'switch.')
            )
        ) {
            $html .= "<div class='device-controls'>";

            $buttonText =
                $mainEntity['state'] === 'on'
                ? 'TURN OFF'
                : 'TURN ON';

            $buttonColor =
                $mainEntity['state'] === 'on'
                ? '#dc3545'
                : '#0e603a';

            $html .= "
                <form method='post'>
                    <input
                        type='hidden'
                        name='entity_id'
                        value='{$mainEntity['entity_id']}'
                    >

                    <input
                        type='hidden'
                        name='action'
                        value='" . (
                            $mainEntity['state'] === 'on'
                            ? 'turn_off'
                            : 'turn_on'
                        ) . "'>

                    <button
                        class='device-button'
                        type='submit'
                        style='
                            background-color: {$buttonColor};
                            color: white;
                            border: none;
                            border-radius: 6px;
                            padding: 8px 16px;
                            cursor: pointer;
                        '
                    >
                        {$buttonText}
                    </button>
                </form>
            ";

            $html .= "</div>";
        }

        $html .= "<div class='device-details'>";

        foreach ($secondaryEntities as $entity)
        {
            $friendlyName =
                $entity['attributes']['friendly_name']
                ?? $entity['entity_id'];

            $label = $friendlyName;

            if (
                str_starts_with(
                    $friendlyName,
                    $device['device_name']
                )
            ) {
                $label = trim(
                    substr(
                        $friendlyName,
                        strlen($device['device_name'])
                    )
                );
            }

            if ($label === '')
            {
                $label = $friendlyName;
            }

            $value = $entity['state'];

            if (
                !empty(
                    $entity['attributes']['unit_of_measurement']
                )
            ) {
                $value .= ' ' .
                    $entity['attributes']['unit_of_measurement'];
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
        return false;
    }
?>