<?php
require_once "../src/connect_db.php";
require_once "../src/user_db_functions.php";

// Check if a cookie is set
// Return code and userID
if (isset($_COOKIE["SmartHome"])) {
    $userID = checkCookie($pdo, $_COOKIE["SmartHome"]);
    if (isset($userID)) {
        $user = getUserByID($pdo, $userID);
        $_SESSION["username"] = htmlspecialchars($user["username"]);
        $_SESSION["userID"] = $user["userID"];
        $_SESSION["email"] = $user["email"];

        echo json_encode([1, $_SESSION["username"]]);
        exit();
    }
}
echo json_encode([0, "Not logged in"]);




