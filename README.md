# Project-G1
Group 1 Project

To make the program work you must first create a file called .env in the top folder (same folder as this readme). In that file write your database and Home Assistant information, one variable per line, in this order:

*Database username*
*Database password*
*Database name*
*Home Assistant Websocket URL (like: wss://YOUR-haURL/api/websocket)*
*Home Assistant Token*
*Home Assistant REST URL (like: https://YOUR-haURL/api)*

The 'public' folder contains all the pages and funktions the website user will need to access (like the site pages and javascript funktions). The 'src' folder contains the files that the user do not need to know about (like database and api connect funktions).


***Database Tables***

Bellow you will find all SQL statements to create the required tables for the application.

**Users**
CREATE TABLE users (
  userID int PRIMARY KEY NOT NULL AUTO_INCREMENT, 
  username varchar(255) NOT NULL, 
  email varchar(255) NOT NULL UNIQUE, 
  passwordHash varchar(255) NOT NULL, 
  permission_level tinyint NOT NULL, 
  token varchar(255)
  )

Note that new users will automaticaly be sett to permission_level = 1 (admin). To change to user account you will have to manualy change the role in the Settings/Admin page. An admin can only change the role of other accounts. 

**Devices**
CREATE TABLE devices (
  device_id varchar(64) NOT NULL PRIMARY KEY,
  device_name varchar(255) NOT NULL UNIQUE,
  area_id varchar(64) DEFAULT NULL,
  area_name varchar(255) NOT NULL,
  required_user_level tinyint(4) NOT NULL DEFAULT 2,
  is_active tinyint(1) NOT NULL DEFAULT 1,
  last_seen timestamp NULL DEFAULT NULL
)

**Favorites**
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

**User log**
CREATE TABLE userlog (
  logID int(11) AUTO_INCREMENT PRIMARY KEY,
  userID int(11) NOT NULL,
  permission tinyint(1) NOT NULL,
  device_name varchar(100) NOT NULL,
  oldValu varchar(20) NOT NULL,
  newValue varchar(20) NOT NULL,
  changeConfirmed tinyint(1) NOT NULL,
  time datetime NOT NULL,

  ADD CONSTRAINT device_name
    FOREIGN KEY (device_name)
      REFERENCES devices (device_name) 
      ON DELETE CASCADE 
      ON UPDATE CASCADE,

  ADD CONSTRAINT userID 
    FOREIGN KEY (userID) 
      REFERENCES users (userID) 
      ON DELETE CASCADE 
      ON UPDATE CASCADE
)

**Alerts**
CREATE TABLE alerts (
    id INT AUTO_INCREMENT PRIMARY KEY, 
    rule_type VARCHAR(50) NOT NULL, 
    entity_id VARCHAR(255) NULL, 
    room_name VARCHAR(100) NOT NULL, 
    title VARCHAR(255) NOT NULL,
    reason TEXT NULL,
    handled_by VARCHAR(100) NULL,
    status ENUM(
        'ACTIVE',
        'ACKNOWLEDGED',
        'DISMISSED',
        'RESOLVED'
    ) DEFAULT 'ACTIVE',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
)


***Live stataus updating***
In order to get live updates of the status of each device you vill need to run the `ha_listener.php` file (located in the public folder) as a consol command.
