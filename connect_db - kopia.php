<?php 
    $pdo = new PDO(
        "mysql:host=localhost;dbname=*databas_namnet*;charset=utf8mb4",
        "username",
        "password",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );
?>