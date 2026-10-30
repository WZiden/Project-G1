<?php
session_start();

// Delete user
function deleteUser(PDO $pdo, int $userID): bool
{
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE userID = ?");
        $stmt->execute([$userID]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}


// Update user permission level
function changeUserLevel(PDO $pdo, int $userID, int $userLevel): bool
{
    try {
        $stmt = $pdo->prepare("UPDATE users SET permission_level = ? WHERE userID = ?");
        $stmt->execute([$userLevel, $userID]);

        // if ($stmt->rowCount() > 0) {
        //     return true;
        // } else {
        //     return false;
        // }
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Get username, userID and level for all users
function getAllUsersList(PDO $pdo)
{
    try {
        return $pdo->query("SELECT userID, username, permission_level FROM users");
    } catch (Exception $e) {
        return null;
    }
}

// Check if user has admin level permission
function isAdmin(PDO $pdo, int $userID): bool
{
    try {
        $stmt = $pdo->prepare("SELECT permission_level FROM users WHERE userID = ?");
        $stmt->execute([$userID]);
        $result = $stmt->fetch();
        if ($result["permission_level"] == 1) {
            return true;
        } else {
            return false;
        }
    } catch (Exception $e) {
        return false;
    }
}

// Get a user from the database by email
function getUserByEmail(PDO $pdo, string $email)
{
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
function getUserByID(PDO $pdo, int $userID)
{
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
function checkCookie(PDO $pdo, string $cookieToken)
{
    try {
        $stmt = $pdo->prepare("SELECT userID FROM users WHERE token = ?");
        $stmt->execute([$cookieToken]);
        $result = $stmt->fetch();
        return $result["userID"];
    } catch (Exception $e) {
        return NULL;
    }
}

// Add a user to the database
// Requires a PDO instance, a name, an email, an unhashed password
function addUser(PDO $pdo, string $name, string $email, string $password, string $confirmPassword): bool
{
    // Check for special characters in username
    if ($name === htmlspecialchars($name)) {
        if ($password === $confirmPassword) {
            // Lowercase, uppercase, number and >= 8 characters
            $regex = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/";
            if (preg_match($regex, $password)) {
                // Check that all mandatory fields are filled in
                if (!empty($pdo) && !empty($name) && !empty($email) && !empty($password)) {
                    $password = password_hash($password, PASSWORD_DEFAULT);
                    try {
                        $stmt = $pdo->prepare("INSERT INTO users (username, email, passwordHash, permission_level) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$name, $email, $password, 2]);

                        return true;
                    } catch (Exception $e) {
                        return false;
                    }
                }
            }
        }
    }
    return false;
}

// Verify that the userID and password for a user matches
function verifyUserWithID(PDO $pdo, string $userID, string $password): bool
{
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
function loginUser(PDO $pdo, string $email, string $password, string $remember = "off"): bool
{
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
                if (isAdmin($pdo, $savedUser["userID"])) {
                    setcookie("Admin", "yes");
                }

                return true;
            }
        }
    }
    return false;
}

// Destoy remember me-cookie
function destroyCookie()
{
    setcookie("SmartHome", "", time() - 60 * 10, "/");
    setcookie("Username", "", time() - 60 * 10, "/");
    setcookie("Admin", "", time() - 60 * 10, "/");
}

// Change username
function changeUserName(PDO $pdo, int $userID, string $newUserName): bool
{
    if ($newUserName !== htmlspecialchars($newUserName)) {
        http_response_code(400);
        return false;
    }
    try {
        $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE userID = ?");
        $stmt->execute([$newUserName, $userID]);
        $_SESSION["username"] = htmlspecialchars($newUserName);
        setcookie("Username", $_SESSION["username"]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Change user password
function changeUserPassword(PDO $pdo, int $userID, string $oldPassword, string $newPassword, string $confirmedPassword): bool
{
    // Compare both new password entries
    if ($newPassword === $confirmedPassword) {
        $regex = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/";
        if (preg_match($regex, $newPassword)) {
            // Verify the old password
            if (verifyUserWithID($pdo, $userID, $oldPassword)) {
                // Change password
                $newPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                try {
                    $stmt = $pdo->prepare("UPDATE users SET passwordHash = ? WHERE userID = ?");
                    $stmt->execute([$newPassword, $userID]);
                    return true;
                } catch (Exception $e) {
                    return false;
                }
            }
        }
    }
    return false;
}
