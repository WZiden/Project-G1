<?php
session_start();

if (!isset($_SESSION['userID'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}
require_once '../../src/connect_db.php';
header('Content-Type: application/json');

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

$rows = $stmt-> fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as &$row) {
    if($row['permission']==0) {
        $row['message'] = "{$row['username']} try to change {$row['device_name']} without permission.";
    }
    else if ($row['changeConfirmed'] ==0) {
        $row['message'] = "{$row['username']} try to change {$row['device_name']} without succes.";
    }
    else if ($row['oldValue']=== 'off' &&$row['newValue']=== 'off' ||$row['oldValue']=== 'on' && $row['newValue']=== 'on') {
        $row['message'] = "{$row['username']} change {$row['device_name']} succesfully.";
    }
    else {
        $row['message'] = "{$row['username']} change {$row['device_name']} from {$row['oldValue']} to {$row['newValue']} succesfully.";
    }
}
echo json_encode($rows);