<?php
require('vendor/autoload.php');
require('src/PHPErrorHandler.php');

$cacheFolder = dirname(__FILE__).'/tmp/';

// contants for testing
define('ENVIRONMENT', 'development'); // development, testing or production
define('FS_CACHE', $cacheFolder);
define('DB_HOSTNAME', 'localhost');
define('DB_USERNAME', 'localdev');
define('DB_PASSWORD', '7bhyGvtjmT5a');
define('DB_DATABASE', 'test');
define('DB_PORT', 3306);
define('DB_CHARSET', 'utf8');

// set timezone
date_default_timezone_set("America/Chicago");

//INSTANTIATE ERROR HANDLER
use rothkj1022\PHPErrorHandler;
$errorHandlerConfig = (require('example.config.php')); //EDIT THIS FILE
$errorHandler = new PHPErrorHandler\PHPErrorHandler($errorHandlerConfig);

// TRIGGER SOME ERRORS

// trigger fatal error
trigger_error("Custom user fatal error", E_USER_ERROR);

// trigger warning
trigger_error("Custom user warning", E_USER_WARNING);

// trigger notice
trigger_error("Custom user notice", E_USER_NOTICE);

//trigger deprecated
trigger_error("Custom user deprecated message", E_USER_DEPRECATED);

// undefined variable
print_r($arra);

// No such file or directory
include_once 'file.php';

// division by zero
$i = 5 / 0;

// failed to open stream: No such file or directory
$file=fopen("welcome.txt","r");

if (!isset($myVar)) {
	$errorHandler->sendError('$myVar is not defined.', 'You should really define that variable.', __FILE__, __LINE__);
}

// trigger a mysql error
if (!isset($mysqli)) {
	$mysqli = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
	$mysqli->set_charset(DB_CHARSET);
}

if ($mysqli) {
	$sql = "select blah from products limit 3";
	$query = $mysqli->query($sql) or $errorHandler->mysqlError($mysqli->error, $sql, __FILE__, __LINE__);
	if ($query->num_rows > 0) {
		while ($row = $query->fetch_assoc()) {
			echo '<p>'.$result['prodfull'].'</p>'."\n";
		}
	}
	$query->close();
}
