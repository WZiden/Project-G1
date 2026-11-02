<?php
session_start();

if (!isset($_SESSION['userID'])) {
    http_response_code(401);

    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

require_once "../src/connect_db.php";
require_once "../src/user_db_functions.php";

// Check which form was submitted
switch ($_POST["type"]) {
    case "changeName":
        $result = changeUserName(
            $pdo,
            $_SESSION["userID"],
            $_POST["newUserName"]
        );
        if (!$result) {
            http_response_code(400);
        }
        break;
    case "changePassword":
        $result = changeUserPassword(
            $pdo,
            $_SESSION["userID"],
            $_POST["oldPassword"],
            $_POST["newPassword"],
            $_POST["confirmPassword"]
        );
        if (!$result) {
            http_response_code(400);
        }
        break;
    default:
        http_response_code(400);
}
