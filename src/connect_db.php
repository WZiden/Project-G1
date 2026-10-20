<?php
$credentials = explode("\n", file_get_contents(__DIR__ . "/../.env"));
$dbName = $credentials[0];
$dbPassword = $credentials[1];
$dsn = "mysql:host=localhost;dbname=" . $credentials[2] . ";charset=utf8mb4";


$pdo = new PDO(
    $dsn,
    $dbName,
    $dbPassword,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]
);
