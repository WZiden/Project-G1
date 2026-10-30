<?php
    require_once __DIR__ . '/../vendor/autoload.php';

    use WebSocket\Client;

    const ALLOWED_SERVICES = [
        'light' => [
            'turn_on',
            'turn_off'
        ],

        'switch' => [
            'turn_on',
            'turn_off'
        ],

        'climate' => [
            'set_temperature'
        ],

        'cover' => [
            'set_cover_position',
            'stop_cover'
        ],

        'lock' => [
            'lock',
            'unlock'
        ],

        'select' => [
            'select_option'
        ]
    ];

    function connectHA(string $haURL, string $haToken): Client {

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

        return $client;
    }

    function getHomeAssistantDevices(string $haURL, string $haToken):array {
        $client = connectHA($haURL, $haToken);

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
        $client = connectHA($haURL, $haToken);

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

        $client = connectHA($haURL, $haToken);

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

    function getEntityHistory(string $url, array $entities, string $start, string $end, string $haToken):array {
        $entityIds = array_column($entities, 'entity_id');

        $url .= '/history/period/' . urlencode($start) . '?filter_entity_id=' . urlencode(implode(',', $entityIds)) . '&end_time=' . urlencode($end) . '&significant_changes_only=0&no_attributes';

        $ch = curl_init($url);

        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $haToken, 'Content-Type: application/json']]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new RuntimeException(curl_error($ch));
        }

        $data = json_decode($response, true);

        return is_array($data) ? $data : []; 
    }

    function sortHistoryData(string $time, array $history, array $entities):array {
        switch ($time) {
            case '-24h':
                $format = 'Y-m-d H:00';
                break;

            case '-7 days':
            case '-1 month':
                $format = 'Y-m-d';
                break;

            default:
                return [];
        }

        $nameMap = [];
        $unitMap = [];

        foreach ($entities as $entity) {
            $nameMap[$entity['entity_id']] = $entity['friendly_name'];
            $unitMap[$entity['entity_id']] = $entity['unit'] ?? '';
        }

        $grouped = [];

        foreach ($history as $entityHistory) {
            foreach ($entityHistory as $state) {
                if (!is_numeric($state['state'])) {
                    continue;
                }
                $entityId = $state['entity_id'];
                $bucket = date(
                    $format,
                    strtotime($state['last_changed'])
                );

                if (!isset($grouped[$entityId][$bucket])) {
                    $grouped[$entityId][$bucket] = [
                        'sum' => 0,
                        'count' => 0
                    ];
                }

                $grouped[$entityId][$bucket]['sum'] += (float)$state['state'];
                $grouped[$entityId][$bucket]['count']++;
            }
        }

        // Samla alla labels
        $labels = [];

        foreach ($grouped as $entityBuckets) {
            $labels = array_merge(
                $labels,
                array_keys($entityBuckets)
            );
        }

        $labels = array_unique($labels);
        sort($labels);

        // Bygg datasets
        $datasets = [];

        foreach ($grouped as $entityId => $entityBuckets) {

            $data = [];

            foreach ($labels as $label) {

                if (isset($entityBuckets[$label])) {

                    $avg =
                        $entityBuckets[$label]['sum']
                        / $entityBuckets[$label]['count'];

                    $data[] = round($avg, 2);

                } else {

                    $data[] = null;
                }
            }

            $datasets[] = [
                'label' => $nameMap[$entityId] ?? $entityId,
                'entity_id' => $entityId,
                'unit' => $unitMap[$entityId] ?? '',
                'data' => $data
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets
        ];
    }

    function getAllRooms(PDO $pdo):array {
        $stmt = $pdo->query("
            SELECT DISTINCT area_name
            FROM devices
            WHERE area_name IS NOT NULL AND area_name != 'Simulator' AND is_active = 1
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

    function searchDevices(PDO $pdo, string $search):array {
        $stmt = $pdo->prepare("
            SELECT
                *
            FROM devices
            WHERE
                is_active = 1
                AND device_name LIKE :search
            ORDER BY device_name
            LIMIT 50
        ");

        $stmt->execute([':search' => "%{$search}%"]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function searchRooms(PDO $pdo, string $search):array {
        $stmt = $pdo->prepare("
            SELECT DISTINCT area_name
            FROM devices
            WHERE
                area_name IS NOT NULL
                AND area_name LIKE :search
            ORDER BY area_name
            LIMIT 20
        ");

        $stmt->execute([':search' => "%{$search}%"]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
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

    function getPermission(PDO $pdo, string $userID, mixed $device):bool {
        $stmt = $pdo->prepare("
            SELECT u.permission_level <= d.required_user_level AS has_permission
            FROM users u
            JOIN devices d ON d.device_name = :device
            WHERE u.userID = :userID
        ");

        $stmt->execute([
            ':userID' => $userID,
            ':device' => $device
        ]);

        $result = $stmt->fetchColumn();

        return $result !== false && (bool) $result;
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
            'area_id' => $device['area_id'],
            'area_name' => $device['area_name'],
            'required_user_level' => $device['required_user_level'] ?? 2,
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

        $controllableEntities = [];
        $infoEntities = [];

        $mainEntityDomains = [
            'light',
            'switch',
            'climate',
            'cover',
            'camera',
            'lock',
            'select'
        ];
   
        $html = "<div class='device-card'>";

        foreach ($entities as $entity){
            $domain = explode('.', $entity['entity_id'], 2)[0];

            if (in_array($domain, $mainEntityDomains, true)){
                $controllableEntities[] = $entity;
            }
            else{
                $infoEntities[] = $entity;
            }
        }

        $html .= "<div class='device-card-header'>";
        $html .= "<h2 class='device-title'>";
        $html .= htmlspecialchars($device['device_name']);
        $html .= "</h2>";

        $html .= "<form method='post' class='favorite-form'>
                <input type='hidden' name='device_id' value='" . htmlspecialchars((string)$device['device_id'], ENT_QUOTES, 'UTF-8') . "'>
                <button type='submit' name='action' value='toggle_favorite' class='favorite-button' aria-pressed='" . ($isFavorite ? 'true' : 'false') . "'>
                    <i class='" . ($isFavorite ? 'fa-solid' : 'fa-regular') . " fa-star'></i>
                </button>
            </form>";
        $html .= "</div>";

        foreach ($controllableEntities as $entity){
            $domain = explode('.', $entity['entity_id'], 2)[0];

            switch ($domain){
                case 'lock':
                    $html .= renderLockControls($entity, $device['device_name']);
                    break;
                case 'cover':
                    $html .= renderCoverControls($entity, $device['device_name']);
                    break;
                    case 'light':
                        $modes = $entity['attributes']['supported_color_modes'] ?? [];
                        if (in_array('rgb', $modes, true)) {
                            $html .= renderRGBLightControls( $entity, $device['device_name']);
                        } elseif (in_array('brightness', $modes, true)) {
                            $html .= renderDimmableLightControls($entity, $device['device_name']);
                        } else {
                            $html .= renderOnOffControls($entity, $device['device_name']);
                        }
                        break;
                case 'switch':
                    $html .= renderOnOffControls($entity, $device['device_name']);
                    break;
                case 'climate':
                    $html .= renderClimateControls($entity, $device['device_name']);
                    break;
                case 'camera':
                    $html .= renderCameraCard($entity);
                    break;
                case 'select':
                    $html .= renderSelectControls($entity, $device['device_name']);
                    break;
            }
        }               

        $html .= "<div class='device-details'>";

        foreach ($infoEntities as $entity)
        {
            $friendlyName = $entity['attributes']['friendly_name'] ?? $entity['entity_id'];
            $label = $friendlyName;
            $entityID = htmlspecialchars($entity['entity_id'], ENT_QUOTES, 'UTF-8');

            if (str_starts_with($friendlyName, $device['device_name'])){
                $label = trim(substr($friendlyName, strlen($device['device_name'])));
            }

            if ($label === ''){
                $label = $friendlyName;
            }

            $value = $entity['state'];

            if (!empty($entity['attributes']['unit_of_measurement'])){
                $value .= ' ' . $entity['attributes']['unit_of_measurement'];
            }

            $html .= "<div class='device-detail-label'>";
            $html .= htmlspecialchars($label);
            $html .= "</div>";

            $html .= "<div class='device-detail-value entity-state' data-entity-id='{$entityID}'>";
            $html .= htmlspecialchars($value);
            $html .= "</div>";
        }

        $html .= "</div>";
        $html .= "</div>";

        return $html;
    }

    function renderOnOffControls(array $mainEntity, string $device):string {
        $isOn = $mainEntity['state'] === 'on';

        $entityID = htmlspecialchars($mainEntity['entity_id'], ENT_QUOTES, 'UTF-8');

        $service = $isOn ? 'turn_off' : 'turn_on';

        $state = ucfirst(htmlspecialchars($mainEntity['state'], ENT_QUOTES, 'UTF-8'));

        $html = renderEntityHeader($mainEntity);

        $html .= "<div class='toggle-controls'>
                <div class='cover-status entity-status' data-entity-id='{$entityID}'>
                    <p>{$state}</p>
                </div>  
                <form class='device-control-form' data-device-name='{$device}'>
                    <input type='hidden' name='entity_id' value='{$entityID}'>
                    <input type='hidden' name='service' value='{$service}'>
                    <button type='submit' class='toggle-button' data-entity-id='{$entityID}' data-state='" . ($isOn ? 'on' : 'off') . "'>
                        <i class='fa-solid fa-toggle-on toggle-on-icon'></i>
                        <i class='fa-solid fa-toggle-off toggle-off-icon'></i>
                    </button>
                </form>
            </div>";

        return $html;
    }

    function renderClimateControls(array $mainEntity, string $device):string {
        $setTemp = $mainEntity['attributes']['temperature'] ?? 0;

        $currentTemp = $mainEntity['attributes']['current_temperature'] ?? 0;

        $minTemp = $mainEntity['attributes']['min_temp'] ?? 5;

        $maxTemp = $mainEntity['attributes']['max_temp'] ?? 30;

        $step = $mainEntity['attributes']['target_temp_step'] ?? 0.5;

        $entityID = htmlspecialchars($mainEntity['entity_id'], ENT_QUOTES, 'UTF-8');

        $html = renderEntityHeader($mainEntity);

        $html .= "<div class='device-controls climate-controls' data-device-name='{$device}'>
                <div class='climate-target-temp' data-entity='{$entityID}' data-entity-id='{$entityID}'>
                    {$setTemp}°C
                </div>
                <input type='range' class='climate-slider' data-entity='{$entityID}' data-entity-id='{$entityID}'
                    min='{$minTemp}'
                    max='{$maxTemp}'
                    step='{$step}'
                    value='{$setTemp}'>
                <div class='climate-current-temp' data-entity-id='{$entityID}'>
                    Current Temperature: {$currentTemp}°C
                </div>
            </div>";

        return $html;
    }

    function renderCoverControls(array $mainEntity, string $device):string {
        $position = $mainEntity['attributes']['current_position'] ?? 0;

        $entityID = htmlspecialchars($mainEntity['entity_id'], ENT_QUOTES, 'UTF-8');

        $state = ucfirst(htmlspecialchars($mainEntity['state'], ENT_QUOTES, 'UTF-8'));

        $html = renderEntityHeader($mainEntity);

        $html .="<div class='cover-status entity-status' data-entity-id='{$entityID}'>
                    <p>{$state}</p>
                </div>
                <div class='cover-controls' data-device-name='{$device}'>
                    <div class='cover-slider-container'>
                        <div class='cover-position-label' data-entity='{$entityID}' data-entity-id='{$entityID}' >
                            {$position}%
                        </div>
                        <div class='cover-slider-container' data-device-name='{$device}'>
                        <input type='range' class='cover-slider' data-entity='{$mainEntity['entity_id']}' data-entity-id='{$entityID}'
                            min='0'
                            max='100'
                            step='1'
                            value='{$position}'>
                        </div>
                    </div>

                </div>                    
                <div class='cover-controls' data-device-name='{$device}'>
                <button type='button' class='cover-stop-button' data-entity='{$mainEntity['entity_id']}'>
                    STOP
                </button>
                </div>";

        return $html;
    }

    function renderLockControls(array $mainEntity, string $device):string {
        $isLocked = $mainEntity['state'] === 'locked';

        $entityID = htmlspecialchars($mainEntity['entity_id'], ENT_QUOTES, 'UTF-8');

        $state = ucfirst(htmlspecialchars($mainEntity['state'], ENT_QUOTES, 'UTF-8'));

        $service = $isLocked ? 'unlock' : 'lock';

        $html = renderEntityHeader($mainEntity);

        $html .="<div class='lock-controls'>
                <p class='entity-status' data-entity-id='{$entityID}'>{$state}</p>
                <form class='device-control-form' data-device-name='{$device}'>
                    <input type='hidden' name='entity_id' value='{$entityID}'>
                    <input type='hidden' name='service' value='{$service}'>
                    <button type='submit' class='lock-button' data-entity-id='{$entityID}' data-locked='" . ($isLocked ? 'true' : 'false') . "'>
                    <i class='fa-solid fa-lock lock-icon'></i>
                    <i class='fa-solid fa-lock-open unlock-icon'></i>
                    </button>
                </form>
            </div>";

        return $html;
    }

    function renderCameraCard(array $mainEntity):string {
        $entityID = htmlspecialchars($mainEntity['entity_id'], ENT_QUOTES, 'UTF-8');

        $html = renderEntityHeader($mainEntity);

        $html .= "<div class='camera-container'>
                <img src='camera.php?entity_id={$entityID}' class='camera-feed' data-entity-id='{$entityID}' alt='Camera feed'>
            </div>";

        return $html;
    }

    function renderSelectControls(array $entity, string $device):string {
        $entityID = htmlspecialchars($entity['entity_id'], ENT_QUOTES, 'UTF-8');
        $currentValue = htmlspecialchars($entity['state'], ENT_QUOTES, 'UTF-8');

        $options = $entity['attributes']['options'] ?? [];

        $html = renderEntityHeader($entity);

        $html .= "<div class='entity-status' data-entity-id='{$entityID}' data-device-name='{$device}'>
                {$currentValue}
            </div>
            <form class='select-control-form' data-device-name='{$device}' data-entity-id='{$entityID}'>
                <div class='select-control-option select-control'>
                <input type='hidden' name='entity_id' value='{$entityID}'>
                <select name='option' class='entity-select'>";

        foreach ($options as $option) {
            $selected = ($option === $currentValue) ? 'selected': '';

            $safeOption = htmlspecialchars( $option, ENT_QUOTES, 'UTF-8');

            $html .= "<option value='" . ucfirst($safeOption) . "' {$selected}>{$safeOption}</option>";
        }

        $html .= "</select>
                </div>
                <div class='select-control-button select-control'>
                <button type='submit'>
                    Save
                </button>
                </div>
            </form>";

        return $html;
    }

    function renderDimmableLightControls(array $mainEntity, string $device):string {
        $isOn = $mainEntity['state'] === 'on';

        $entityID = htmlspecialchars($mainEntity['entity_id'], ENT_QUOTES, 'UTF-8');

        $brightness = $mainEntity['attributes']['brightness'] ?? 255;

        $service = $isOn ? 'turn_off' : 'turn_on';

        $state = ucfirst(htmlspecialchars( $mainEntity['state'], ENT_QUOTES, 'UTF-8'));

        $html = renderEntityHeader($mainEntity);

        $html .= "<div class='entity-status' data-entity-id='{$entityID}'>
                {$state}
            </div>
            <div class='light-controls rgb-controls' data-device-name='{$device}'>
                <input type='range' class='brightness-slider' data-entity-id='{$entityID}' min='1' max='255' step='1' value='{$brightness}'>
                <form class='device-control-form' data-device-name='{$device}'>
                    <input type='hidden' name='entity_id' value='{$entityID}'>
                    <input type='hidden' name='service' value='{$service}'>
                    <button type='submit' class='toggle-button' data-entity-id='{$entityID}' data-state='" . ($isOn ? 'on' : 'off') . "'>
                        <i class='fa-solid fa-toggle-on toggle-on-icon'></i>
                        <i class='fa-solid fa-toggle-off toggle-off-icon'></i>
                    </button>
                </form>
            </div>
        <div class='brightness-value' data-entity-id='{$entityID}'>
            <p>Brightness: {$brightness}</p>
        </div>";

        return $html;
    }

    function renderRGBLightControls(array $mainEntity, string $device):string {
        $isOn = $mainEntity['state'] === 'on';

        $entityID = htmlspecialchars($mainEntity['entity_id'], ENT_QUOTES, 'UTF-8');

        $brightness = $mainEntity['attributes']['brightness'] ?? 255;

        $rgb = $mainEntity['attributes']['rgb_color'] ?? [255,255,255];

        $r = $rgb[0] ?? 255;
        $g = $rgb[1] ?? 255;
        $b = $rgb[2] ?? 255;

        $service = $isOn ? 'turn_off' : 'turn_on';

        $state = ucfirst(htmlspecialchars( $mainEntity['state'], ENT_QUOTES, 'UTF-8'));

        $hexColor = sprintf('#%02X%02X%02X', $r, $g, $b); //detta är rad 821

        $html = renderEntityHeader($mainEntity);

        $html .= "<div class='entity-status' data-entity-id='{$entityID}'>
                {$state}
            </div>
                <div class='rgb-controls' data-device-name='{$device}'>
                <input type='color' class='rgb-picker' data-entity-id='{$entityID}' value='{$hexColor}'>
                <input type='range' class='brightness-slider' data-entity-id='{$entityID}' min='1' max='255' step='1' value='{$brightness}'>
                <form class='device-control-form' data-device-name='{$device}'>
                    <input type='hidden' name='entity_id' value='{$entityID}'>
                    <input type='hidden' name='service' value='{$service}'>
                    <button type='submit' class='toggle-button' data-entity-id='{$entityID}' data-state='" . ($isOn ? 'on' : 'off') . "'>
                        <i class='fa-solid fa-toggle-on toggle-on-icon'></i>
                        <i class='fa-solid fa-toggle-off toggle-off-icon'></i>
                    </button>
                </form>
            </div>
            <div class='brightness-value' data-entity-id='{$entityID}'>
                <p>Brightness: {$brightness}</p>
            </div>";

        return $html;
    }

    function renderEntityHeader(array $entity):string {
        $friendlyName = $entity['attributes']['friendly_name'] ?? $entity['entity_id'];

        $friendlyName = htmlspecialchars($friendlyName, ENT_QUOTES, 'UTF-8');

        return "<div class='device-detail-label'>
                    {$friendlyName}:
                </div>";

    }

    function controlDevice(string $entityId, string $service, string $haURL, string $haToken, array $serviceData = []):bool {
        $domain = explode('.', $entityId)[0];

        if (!isset(ALLOWED_SERVICES[$domain]) || !in_array($service, ALLOWED_SERVICES[$domain], true)){
            throw new Exception('Service not allowed');
        }

        $client = connectHA($haURL, $haToken);

        $client->send(json_encode([
            "id" => 2,
            "type" => "call_service",
            "domain" => $domain,
            "service" => $service,
            "service_data" => array_merge(
                [
                    "entity_id" => $entityId
                ],
                $serviceData
            )
        ]));

        $response = json_decode($client->receive(), true);

        return (isset($response['success'])&& $response['success']);
    }

    

    //funktioner för userlog
    
    // då state innehåller hela raden så måste vi ta ut de vi är ute efter
    function extractOldValue(array $states,string $entityId  ):mixed{
        $state =null;

        foreach($states as $item){
            if ($item['entity_id'] === $entityId ){
                $state=$item;
                break;
            }
        }
        if($state === null) {
            throw new Exception("Entity not found: $entityId");
        }

        $domain =explode ('.', $state ['entity_id'])[0];
        switch ($domain) {
            case 'light':
                if($state['state'] === 'on' || $state ['state'] === 'off'){
                    return $state ['state'];
                }
                return $state['attributes']['brightness']??null;
            case 'switch':
                return $state['state'];
            case 'climate':
                return $state['attributes']['temperature']??null;
            case 'cover':
                return $state['attributes']['current_position']??null;
            case 'lock':
            return $state ['state'];
            default:
                return $state ['state'];
        }
        
    }

    function extractNewValue(string $entityId, string $service, array $serviceData ): mixed {
        $domain =explode ('.', $entityId)[0];
        switch ($domain) {
            case 'light':
                if($service === 'turn_on') return 'on';
                if($service === 'turn_off')return 'off';
                
                return $serviceData['brightness'] ?? 'unknown';

            case 'switch':
                if($service === 'turn_on') return 'on';
                if($service === 'turn_off')return 'off';
                return 'unknown';
            case 'climate':
                return $serviceData['temperature'] ?? 'unknown';
            case 'cover':
                return $serviceData['position']?? $serviceData['current_position']?? 'unknown';
            case 'lock':
                if($service === 'lock') return 'locked';
                if($service === 'unlock')return 'unlocked';
                return 'unknown';
            default:
                return 'unknown';
        }
    
    }
            
    function logDeviceAttempt ( PDO $pdo, int $userID, bool $permissionGranted, string $deviceName, mixed $oldValue, mixed $newValue,bool $success ):void {
        $stmt = $pdo-> prepare("
        INSERT INTO userlog (
        userID, permission, device_name, oldValue, newValue, changeConfirmed, time)
        VALUES (?, ?, ?, ?, ?, ?, now())
        ");

        $stmt ->execute ([
            $userID,
            $permissionGranted ? 1:0,
            $deviceName,
            $oldValue,
            $newValue,
            $success ? 1: 0
        ]);

    }

    // Update device required permission level
    function changeDeviceLevel(PDO $pdo, string $deviceID, int $deviceLevel): bool
    {
            try {
            $stmt = $pdo->prepare("
            UPDATE devices 
            SET required_user_level = ? 
            WHERE device_id = ?");
            $stmt->execute([$deviceLevel, $deviceID]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }


?>

