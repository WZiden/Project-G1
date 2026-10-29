# Project-G1
Group 1 Project

Alla behöver skapa en fil som heter .env som ska ligga högst upp (över public etc). Där ska ni skriva på: 
första raden användarnamnet till databasen,
andra raden lösenordet,
tredje raden namnet på databasen,
fjärde raden URL till api,
femte raden token till api.

I public ska alla filer ligga som användaren behöver komma åt (ex index.html, login.js) och i src ska alla filer ligga som användaren inte behöver komma åt (ex filer som pratar med databasen).

För att skapa tabellen som innehåller användarna:
CREATE TABLE users (userID int PRIMARY KEY NOT NULL AUTO_INCREMENT, username varchar(255) NOT NULL, email varchar(255) NOT NULL UNIQUE, passwordHash varchar(255) NOT NULL, permission_level tinyint NOT NULL, token varchar(255))

Ny användare blir automatiskt users (nivå 2)

Hej. jag har tagit bort de filerna som heter index mobil och style mobil. då allt nu ligger i projekt istället :) fast såg nu att de inte försvann från public filen. men de två filerna ska inte inkluderas i projektet så de ska bort//Malin

OBS. Ni kan ta bort tabellen för enheter. Lägg istället till denna:
CREATE TABLE `devices` (
  `device_id` varchar(64) NOT NULL PRIMARY KEY,
  `device_name` varchar(255) NOT NULL UNIQUE,
  `area_id` varchar(64) DEFAULT NULL,
  `area_name` varchar(255) NOT NULL,
  `required_user_level` tinyint(4) NOT NULL DEFAULT 2,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_seen` timestamp NULL DEFAULT NULL
)

Tabell för favoriter:
CREATE TABLE favorites (
    userID INT NOT NULL,
    device_id VARCHAR(255) NOT NULL,

    PRIMARY KEY (userID, device_id),

    FOREIGN KEY (userID)
        REFERENCES users(userID)
        ON DELETE CASCADE,

    FOREIGN KEY (device_id)
        REFERENCES devices(device_id)
        ON DELETE CASCADE
)

hej här är tabellen för userlog
CREATE TABLE `userlog` (
  `logID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `permission` tinyint(1) NOT NULL,
  `device_name` varchar(100) NOT NULL,
  `oldValue` varchar(20) NOT NULL,
  `newValue` varchar(20) NOT NULL,
  `changeConfirmed` tinyint(1) NOT NULL,
  `time` datetime NOT NULL
)
man måste lägga till uniqe på device_name i devices för att de ska gå att lägga in FK mellan tabellerna

ALTER TABLE `userlog`
  ADD CONSTRAINT `device_name` FOREIGN KEY (`device_name`) REFERENCES `devices` (`device_name`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `userID` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;



Här är tabellen för alerts:
CREATE TABLE alerts (
    id INT AUTO_INCREMENT PRIMARY KEY, 
    rule_type VARCHAR(50) NOT NULL, 
    entity_id VARCHAR(255) NULL, 
    room_name VARCHAR(100) NOT NULL, 
    title VARCHAR(255) NOT NULL,
    reason TEXT NULL,
    status ENUM(
        'ACTIVE',
        'ACKNOWLEDGED',
        'DISMISSED',
        'RESOLVED'
    ) DEFAULT 'ACTIVE',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);
