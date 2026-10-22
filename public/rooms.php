<?php
require_once "../src/connect_db.php";
require_once "functions.php";

$rooms = getAllRooms($pdo);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($rooms);

?>