<?php
require_once "../src/connect_db.php";
require_once "../src/user_db_functions.php";

if (isset($_SESSION["userID"])) {
    echo json_encode($_SESSION["username"]);
    return;
} elseif (isset($_COOKIE["SmartHome"])) {
    $userID = checkCookie($pdo, $_COOKIE["SmartHome"]);
    if ($userID !==  NULL) { // Kontrollera om checkCookie lyckades
        $user = getUserByID($pdo, $userID);
        $_SESSION["username"] = htmlspecialchars($user["username"]);
        $_SESSION["userID"] = $user["userID"];
        $_SESSION["email"] = $user["email"];

        echo json_encode($_SESSION["username"]);
        return;
    } else {
        // User not logged in
        http_response_code(401);
        echo json_encode("");
        return NULL;
    }
} else {
    // User not logged in
    http_response_code(401);
    echo json_encode("");
    return;
}
