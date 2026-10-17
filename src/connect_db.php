<?php
$secrets = fopen("../.env", "r") or die("Unable to open file!");
$dbName = trim(fgets($secrets));
$dbPassword = trim(fgets($secrets));
$dsn = "mysql:host=localhost;dbname=" . trim(fgets($secrets)) . ";charset=utf8mb4";
fclose($secrets);

$pdo = new PDO(
    $dsn,
    $dbName,
    $dbPassword,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]
);
