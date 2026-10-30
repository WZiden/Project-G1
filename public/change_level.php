<?php
require_once "../src/connect_db.php";
require_once "../src/functions.php";
require_once "../src/user_db_functions.php";

if (isset($_SESSION["userID"]) && isAdmin($pdo, $_SESSION["userID"])) {
    $jsonData = file_get_contents("php://input");
    $data = json_decode($jsonData, true);
    $newLevel = ($data["newLevel"] === "admin") ? 1 : 2;
    switch ($data["type"]) {
        case "User":
            // Do not change your own role
            if ($data["typeID"] != $_SESSION["userID"]) {
                $result = changeUserLevel(
                    $pdo,
                    $data["typeID"],
                    $newLevel
                );
                if (!$result) {
                    http_response_code(400);
                }                
            } else {
                http_response_code(400);
            }
            break;
        case "Device":
            $result = changeDeviceLevel(
                $pdo,
                $data["typeID"],
                $newLevel
            );
            if (!$result) {
                http_response_code(400);
            }
            break;
        case "Delete":
            $result = deleteUser(
                $pdo,
                $data["typeID"]);
            if (!$result) {
                http_response_code(400);
            }
            break;
        default:
            http_response_code(400);
    }
} else {
    http_response_code(401);
}
