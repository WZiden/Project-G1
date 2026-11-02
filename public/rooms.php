<?php
session_start();

if (!isset($_SESSION['userID'])) {
    http_response_code(401);

    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}
require_once "../src/connect_db.php";
require_once "../src/functions.php";

$rooms = getAllRooms($pdo);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($rooms);
