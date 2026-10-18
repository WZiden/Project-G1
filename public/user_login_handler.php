<?php
require_once "../src/connect_db.php";
require_once "../src/user_db_functions.php";

// Check which form was submitted
switch ($_POST["type"]) {
  case "login":
    $remember = $_POST["remember"] ?? "off";
    $result = loginUser(
      $pdo, 
      $_POST["email"], 
      $_POST["password"],
      $remember);
      echo(json_encode($result));
    break;
  case "register":
    $result = addUser(
      $pdo, 
      $_POST["username"], 
      $_POST["email"], 
      password_hash($_POST["password"], 
      PASSWORD_DEFAULT));
      echo(json_encode($result));
    break;
  case "forgotten":
    //code block
    break;
  default:
    //code block
}


