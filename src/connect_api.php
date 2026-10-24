<?php
$credentials = explode("\n", file_get_contents(__DIR__ . "/../.env"));
$haURL = trim($credentials[3]);
$haToken = trim($credentials[4]);
