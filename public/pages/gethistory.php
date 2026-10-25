<?php
session_start();

if (!isset($_SESSION['userID'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}
require_once '../../src/connect_db.php';
header('Content-Type: application/json');


//kod för behörighetsnivå
//if ($_SESSION['role'] !== 'admin') {
//    http_response_code(403);
//    echo json_encode(['error' => 'Access denied']);
//    exit;
//}
$stmt =$pdo->prepare ("
SELECT
   userlog.time,
   userlog.device_name,
   userlog.oldValue,
   userlog.newValue,
   userlog.permission,
   userlog.changeConfirmed,
   users.username
   FROM userlog
   JOIN users ON userlog.userID = users.userID
   ORDER BY userlog.time DESC
    ");
$stmt->execute();
echo json_encode($stmt ->fetchAll(PDO::FETCH_ASSOC));