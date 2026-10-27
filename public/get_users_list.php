<?php
require_once "../src/connect_db.php";
require_once "../src/user_db_functions.php";

if (isset($_SESSION["userID"]) && isAdmin($pdo, $_SESSION["userID"])) {
    $result = getAllUsersList($pdo);
    if (!$result) {
        http_response_code(400);
    }
    $resArray = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $resArray[] = $row;
    }
    echo json_encode($resArray);
} else {
    http_response_code(401);
}
