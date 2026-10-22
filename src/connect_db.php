<?php
$credentials = explode("\n", file_get_contents(__DIR__ . "/../.env"));
$dbName = trim($credentials[0]);
$dbPassword = trim($credentials[1]);
$dsn = "mysql:host=localhost;dbname=" . trim($credentials[2]) . ";charset=utf8mb4";


$pdo = new PDO(
    $dsn,
    $dbName,
    $dbPassword,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]
);
?>