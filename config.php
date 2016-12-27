<?php

ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);
ini_set("default_charset", 'utf-8');
date_default_timezone_set('America/Mexico_City');
define('MODE', 'dev');
define('EOL',(PHP_SAPI == 'cli') ? PHP_EOL : "\n<br/>");
define('eol',(PHP_SAPI == 'cli') ? PHP_EOL : "\n<br/>");


$DBServer = '127.0.0.1';
$DBUser = 'root';
$DBPass = 'root';
$DBName = 'ssa5522_se';

 ?>
