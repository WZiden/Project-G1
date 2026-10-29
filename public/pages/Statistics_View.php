<?php

    $options = json_decode(file_get_contents(__DIR__ . '/../../cache/sensores.json'), true);

    $entities = [];
    $rooms = [];
    $types = array_diff(array_keys($options), ['unknown', 'enum']);

    sort($types);

    foreach ($options as $type => $sensors) {
        foreach ($sensors as $sensor) {
            $entities[$sensor['entity_id']] = $sensor['friendly_name'];

            if (isset($sensor['area_name']) && !in_array($sensor['area_name'], $rooms)){
                $rooms[] = $sensor['area_name'];
            }
        }
    }

    $rooms = array_unique($rooms);
    sort($rooms);

    $types = array_unique($types);
    sort($types);
?>



    <div class="stats-filters">
        <select id="statsType">
            <?php foreach($types as $type): ?>
                <option value="<?= htmlspecialchars($type) ?>"
                        <?= $type === 'temperature' ? 'selected' : '' ?>>
                    <?= ucfirst($type) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select id="statsRoom">
            <option value="">All Areas</option>
            <?php foreach($rooms as $room): ?>
                <option value="<?= htmlspecialchars($room) ?>">
                    <?= htmlspecialchars($room) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select id="statsPeriod">
            <option value="-24h">Day</option>
            <option value="-7 days">Week</option>
            <option value="-1 month">Month</option>
        </select>
    </div>

    <div class='chart-title'>
        <h3 id="statsTitle"></h3>
    </div>
    <div class="chart-container">
        <canvas id="statsChart"></canvas>
    </div>

    <div id="statsData" hidden data-options="<?= htmlspecialchars(json_encode($options), ENT_QUOTES) ?>">
    </div>
