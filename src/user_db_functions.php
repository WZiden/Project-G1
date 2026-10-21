<?php
session_start();

// Get a user from the database by email
function getUserByEmail(PDO $pdo, string $email) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $result = $stmt->fetch();
        return $result;
    } catch (Exception $e) { 
        return NULL;
    }
}

// Get a user from the database by userID
function getUserByID(PDO $pdo, int $userID) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE userID = ?");
        $stmt->execute([$userID]);
        $result = $stmt->fetch();
        return $result;
    } catch (Exception $e) { 
        return NULL;
    }
}

// Check if a cookie exists in the database
// Return userID or NULL
function checkCookie(PDO $pdo, string $cookieToken) {
    try {
        $stmt = $pdo->prepare("SELECT userID FROM cookies WHERE token = ?");
        $stmt->execute([$cookieToken]);
        $result = $stmt->fetch();
        return $result["userID"];
    } catch (Exception $e) { 
        return "kunde inte ansluta till datbasen för att kolla kookie";
    }
}

// Add a user to the database
// Requires a PDO instance, a name, an email, an unhashed password
// Returns an array with: 1/0 (success/failure code), String (success/failure message)
function addUser(PDO $pdo, string $name, string $email, string $password) {
    // Check for special characters in username
    if ($name !== htmlspecialchars($name)) {
        return [0, "The name must not contain any special characters"];
    }
    // Check that all mandatory fields are filled in
    if (!empty($pdo) && !empty($name) && !empty($email) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, passwordHash) VALUES (?, ?, ?)");
            $stmt->execute([$name, $email, $password]);

            return [1, "User added"];
        } catch (Exception $e) {
            return [0, "User not added"];
        }
    } else {
        return [0, "All fields must be filled in"];
    }
}

// Log in user
// Requires a PDO instance, an email, an unhashed password, a checkbox value ('on' if checked)
// Returns an array with: 1/0 (success/failure code), String (success/failure message), Username (only on success)
function loginUser(PDO $pdo, string $email, string $password, string $remember = "off") {
    // Check that all mandatory fields are filled in 
    // and verify whether the user exists in the database
    if (!empty($pdo) && !empty($email) && !empty($password)) {
        $savedUser = getUserByEmail($pdo, $email);
        if (!empty($savedUser)) {

            // Check the password
            if (password_verify($password, $savedUser["passwordHash"])) {
                $_SESSION["username"] = htmlspecialchars($savedUser["username"]);

                 // Set persistent cookie for 1h if 'remember me' is checked
                if ($remember === "on") {
                    // Set cookie
                    $salt = "!s9:(K*+ji<%-D";
                    $token = hash("sha256", $salt . time() . $salt);
                    setcookie("SmartHome", $token, time() + 60 * 60, "/");

                    // Add cookie to database
                    $stmt = $pdo->prepare("INSERT INTO cookies (userID, token) VALUES (?, ?) ON DUPLICATE KEY UPDATE token=?");
                    $stmt->execute([$savedUser["userID"], $token, $token]);
                }
                return [1, "User logged in", $_SESSION["username"]];
            }           
        }
        return [0, "Incorrect email or password"];
    } else {
        return [0, "All fields must be filled in"];
    }   
}

// Destoy cokie
function destroyCookie() {
    setcookie("SmartHome", "", time() - 60 * 10, "/");
}