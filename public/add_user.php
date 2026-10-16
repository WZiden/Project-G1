<?php
require_once "../src/connect_db.php";
session_start();

// All fields are required
if (!empty($_POST["username"]) && !empty($_POST["email"]) && !empty($_POST["password"])) {

    // Get form data
    $userName = htmlspecialchars($_POST["username"]);
    $email = htmlspecialchars($_POST["email"]);
    $password = password_hash(htmlspecialchars($_POST["password"]), PASSWORD_DEFAULT);

    try {
        // Add new user to database
        $stmt = $pdo->prepare("INSERT INTO users (username, email, passwordHash) VALUES (?, ?, ?)");
        $stmt->execute([$userName, $email, $password]);

        // Close connection
        $stmt = null;
        $dsn = null;

    } catch (PDOException $e) {
        //error_log($e->get_message());         // Server log
        //echo($e->getMessage() . "<br><br>");    // TODO Ta bort
        echo ($e->getCode() . "\n\n");    // TODO Ta bort

        //http_response_code(500);
        exit("Error talking to database");
    }

}
