
<?php

session_start();

require_once __DIR__ . '/../src/connect_db.php';

header('Content-Type: application/json; charset=utf-8');

$username = $_SESSION['username'] ?? null;
$deviceId = $_POST['device_id'] ?? null;
$action = $_POST['action'] ?? '';

if (
    !$username ||
    !$deviceId ||
    !in_array($action, ['add_favorite', 'remove_favorite'], true)
) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Ogiltiga parametrar.'
    ]);

    exit;
}

try {
    if ($action === 'add_favorite') {
        $stmt = $pdo->prepare("
            INSERT INTO favorites (userID, device_id)
            SELECT u.userID, :device_id
            FROM users u
            WHERE u.username = :username
        ");

        $stmt->execute([
            'device_id' => $deviceId,
            'username' => $username
        ]);

        $isFavorite = true;

    } else {
        $stmt = $pdo->prepare("
            DELETE f
            FROM favorites f
            INNER JOIN users u ON u.userID = f.userID
            WHERE u.username = :username
              AND f.device_id = :device_id
        ");

        $stmt->execute([
            'username' => $username,
            'device_id' => $deviceId
        ]);

        $isFavorite = false;
    }

    echo json_encode([
        'success' => true,
        'isFavorite' => $isFavorite
    ]);

} catch (PDOException $e) {
    error_log($e->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Databasen kunde inte uppdatera favoriten.'
    ]);
}

exit;
