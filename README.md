# Project-G1
Group 1 Project

Alla behöver skapa en fil som heter .env. Den ska ligga högst upp (över public etc). Där ska ni på första raden skriva användarnamnet till databasen, på andra raden lösenordet och på tredje raden namnet på databasen. Denna fil kommer inte laddas upp till Git.

I public ska alla filer ligga som användaren behöver komma åt (ex index.html, login.js) och i src ska alla filer ligga som användaren inte behöver komma åt (ex filer som pratar med databasen).

För att skapa tabellen som innehåller användarna:
CREATE TABLE users (userID int PRIMARY KEY NOT NULL AUTO_INCREMENT, username varchar(255) NOT NULL, email varchar(255) NOT NULL UNIQUE, passwordHash varchar(255) NOT NULL)

