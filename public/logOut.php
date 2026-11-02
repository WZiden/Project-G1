<?php
session_start();

if (!isset($_SESSION['userID'])) {
    http_response_code(401);

    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}
require_once "../src/user_db_functions.php";
session_unset();
session_destroy();
destroyCookie();
