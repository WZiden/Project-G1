<?php
$credentials = explode("\n", file_get_contents(__DIR__ . "/../.env"));
$haURL = $credentials[3];
$haToken = $credentials[4];
