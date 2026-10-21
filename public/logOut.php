<?php
require_once "../src/user_db_functions.php";
session_unset();
session_destroy();
destroyCookie();
