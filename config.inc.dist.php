<?php
// ini_set('display_errors', 1);
// error_reporting(E_ALL);
define('MAR_DSN', 'mysql://username:password@hostname/databasename');
define('MAR_LIMIT', 10000);
define('MAR_CHARSET', 'UTF-8');
define('DB_CHARSET', 'utf8');
date_default_timezone_set('UTC');
//this is the inflector from CakePHP
require_once('lib/Inflector.php');
//you may also use this one instead
//require_once('MiniInflector.php');
require_once('lib/MiniActiveRecord.php');
//include your classes here
require_once('models/Car.php');
require_once('models/Driver.php');
?>