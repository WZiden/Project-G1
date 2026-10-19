# Project-G1
Group 1 Project

Alla behöver skapa en fil som heter .env som ska ligga högst upp (över public etc). Där ska ni på första raden skriva användarnamnet till databasen, på andra raden lösenordet och på tredje raden namnet på databasen. Denna fil kommer inte laddas upp till Git.

I public ska alla filer ligga som användaren behöver komma åt (ex index.html, login.js) och i src ska alla filer ligga som användaren inte behöver komma åt (ex filer som pratar med databasen).

För att skapa tabellen som innehåller användarna:
CREATE TABLE users (userID int PRIMARY KEY NOT NULL AUTO_INCREMENT, username varchar(255) NOT NULL, email varchar(255) NOT NULL UNIQUE, passwordHash varchar(255) NOT NULL)


Hej. jag har tagit bort de filerna som heter index mobil och style mobil. då allt nu ligger i projekt istället :) fast såg nu att de inte försvann från public filen. men de två filerna ska inte inkluderas i projektet så de ska bort//Malin

För att skapa tabellen som innehåller cookies:
CREATE TABLE cookies (userID int PRIMARY KEY NOT NULL, token varchar(255) NOT NULL)

För att skapa tabellen för alla enheter:
CREATE TABLE entities (entity_id varchar(255) NOT NULL, state varchar(255) NOT NULL, friendly_name varchar(255) NOT NULL, attributes longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`attributes`)), device varchar(255) NOT NULL, last_seen datetime NOT NULL DEFAULT current_timestamp())
