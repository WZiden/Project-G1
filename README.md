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
CREATE TABLE users (userID int PRIMARY KEY NOT NULL AUTO_INCREMENT, username varchar(255) NOT NULL, email varchar(255) NOT NULL UNIQUE, passwordHash varchar(255) NOT NULL)


Hej. jag har tagit bort de filerna som heter index mobil och style mobil. då allt nu ligger i projekt istället :) fast såg nu att de inte försvann från public filen. men de två filerna ska inte inkluderas i projektet så de ska bort//Malin

För att skapa tabellen som innehåller cookies:
CREATE TABLE cookies (userID int PRIMARY KEY NOT NULL, token varchar(255) NOT NULL)

OBS. Ni kan ta bort tabellen för enheter. Lägg istället till denna:
CREATE TABLE `devices` (
  `device_id` varchar(64) NOT NULL,
  `device_name` varchar(255) NOT NULL,
  `area_id` varchar(64) DEFAULT NULL,
  `area_name` varchar(255) NOT NULL,
  `required_user_level` tinyint(4) NOT NULL DEFAULT 2,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_seen` timestamp NULL DEFAULT NULL
)
