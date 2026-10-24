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
        $stmt = $pdo->prepare("SELECT userID FROM users WHERE token = ?");
        $stmt->execute([$cookieToken]);
        $result = $stmt->fetch();
        return $result["userID"];
    } catch (Exception $e) { 
        return "Unable to check cookie";
    }
}

// Add a user to the database
// Requires a PDO instance, a name, an email, an unhashed password
// Returns an array with: 1/0 (success/failure code), String (success/failure message)
function addUser(PDO $pdo, string $name, string $email, string $password, string $confirmPassword) {
    // Check for special characters in username
    if ($name !== htmlspecialchars($name)) {
        return [0, "The name must not contain any special characters"];
    }
    if ($password != $confirmPassword) {
        return [0, "Passwords do not match"];
    }
    $regex = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/";
    if (!preg_match($regex, $password)) {
        return [0, "Password does not meet the requirements"];
    }
    // Check that all mandatory fields are filled in
    if (!empty($pdo) && !empty($name) && !empty($email) && !empty($password)) {
        $password = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, passwordHash, permission_level) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $password, 2]);

            return [1, "User added"];
        } catch (Exception $e) {
            return [0, "User not added"];
        }
    } else {
        return [0, "All fields must be filled in"];
    }
}

// Verify that the userID and password for a user matches
function verifyUserWithID(PDO $pdo, string $userID, string $password) {
    $savedUser = getUserByID($pdo, $userID);
    if (!empty($savedUser)) {
        // Check the password
        if (password_verify($password, $savedUser["passwordHash"])) {
            return true;
        }           
    }
    return false;
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
                $_SESSION["userID"] = $savedUser["userID"];
                $_SESSION["email"] = $savedUser["email"];

                 // Set persistent cookie for 1h if 'remember me' is checked
                if ($remember === "on") {
                    // Set cookie
                    $salt = "!s9:(K*+ji<%-D";
                    $token = hash("sha256", $salt . time() . $salt);
                    setcookie("SmartHome", $token, time() + 60 * 60, "/");

                    // Add cookie to database
                    $stmt = $pdo->prepare("UPDATE users SET token = ? WHERE userID = ?");
                    $stmt->execute([$token, $savedUser["userID"]]);
                }
                setcookie("Username", $_SESSION["username"]);
                    
                return [1, "User logged in", $_SESSION["username"]];
            }           
        }
        return [0, "Incorrect email or password"];
    } else {
        return [0, "All fields must be filled in"];
    }   
}

// Destoy cookie
function destroyCookie() {
    setcookie("SmartHome", "", time() - 60 * 10, "/");
}

// Change username
function changeUserName(PDO $pdo, int $userID, string $newUserName) {
    if ($newUserName !== htmlspecialchars($newUserName)) {
        return [0, "The name must not contain any special characters"];
    }
    try {
        $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE userID = ?");
        $stmt->execute([$newUserName, $userID]);
        $_SESSION["username"] = htmlspecialchars($newUserName);
        setcookie("Username", $_SESSION["username"]);
        return [1, "Username changed", $newUserName];
    } catch (Exception $e) {
        return [0, "Username not changed"];
    } 
} 

// Change user password
function changeUserPassword(PDO $pdo, int $userID, string $oldPassword, string $newPassword, string $confirmedPassword) {
    // Compare both new password entries
    if ($newPassword === $confirmedPassword) {
        $regex = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/";
        if (!preg_match($regex, $newPassword)) {
            return [0, "Password does not meet the requirements"];
        }
        // Verify the old password
        if (verifyUserWithID($pdo, $userID, $oldPassword)) {
            // Change password
            $newPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("UPDATE users SET passwordHash = ? WHERE userID = ?");
                $stmt->execute([$newPassword, $userID]);
                return [1, "Password changed"];
            } catch (Exception $e) {
            return [0, "Password not changed"];
            } 
        } else {
            return [0, "Wrong password"];
        }
    } else {
        return [0, "Passwords do not match"];
    }
}
    
//require_once "connect_db.php";


