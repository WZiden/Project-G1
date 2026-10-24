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
      $_POST["password"],
      $_POST["confirmpassword"]);
      echo(json_encode($result));
    break;
  case "changeName":
    $result = changeUserName(
      $pdo, 
      $_SESSION["userID"], 
      $_POST["newUserName"]);
      echo(json_encode($result));
    break;
  case "changePassword":
    $result = changeUserPassword(
      $pdo, 
      $_SESSION["userID"], 
      $_POST["oldPassword"],
      $_POST["newPassword"],
      $_POST["confirmPassword"]);
      echo(json_encode($result));
    break;
  default:
    //code block
}


