<?php
require_once "../src/connect_db.php";
require_once "../src/functions.php";
require_once "../src/user_db_functions.php";


if (isset($_SESSION["userID"]) && isAdmin($pdo, $_SESSION["userID"])) {
    $result = getAllDevices($pdo);
    if (!$result) {
        http_response_code(400);
    }
    echo json_encode($result);
} else {
    http_response_code(401);
}
