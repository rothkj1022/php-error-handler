<?php
require('vendor/autoload.php');
require('src/PHPErrorHandler.php');

use rothkj1022\PHPErrorHandler;

// contants for testing
define('ENVIRONMENT', 'development'); // development, testing or production
define('DB_HOSTNAME', 'localhost');
define('DB_USERNAME', 'localdev');
define('DB_PASSWORD', '7bhyGvtjmT5a');
define('DB_DATABASE', 'test');
define('DB_PORT', 3306);
define('DB_CHARSET', 'utf8');

// set timezone
date_default_timezone_set("America/Chicago");

//INSTANTIATE ERROR HANDLER
if (ENVIRONMENT == 'production') {
	$displayErrors = false;
	$emailErrors = true;
} else {
	$displayErrors = true;
	$emailErrors = false;
}

$errorHandler = new PHPErrorHandler\PHPErrorHandler([
	'displayErrors' => $displayErrors,
	'emailErrors' => $emailErrors,
	'logErrors' => true, //requires database
	'purgeLogTimeout' => '1 DAY', //use mysql date_add interval syntax or set to false
	'floodControl' => '15 MINUTE', //use mysql date_add interval syntax or set to false
	'database' => [
		/*'driver' => 'mysql', //pdo or mysql
		'hostname' => DB_HOSTNAME,
		'username' => DB_USERNAME,
		'password' => DB_PASSWORD,
		'database' => DB_DATABASE,
		'port' => DB_PORT,
		'charset' => DB_CHARSET,*/
		'driver' => 'pdo', //pdo or mysql
		'dsn' => 'mysql:host='.DB_HOSTNAME.';dbname='.DB_DATABASE.';port='.DB_PORT.';charset=UTF8',
		'username' => DB_USERNAME,
		'password' => DB_PASSWORD
	],
	'email' => [
		'recipients' => [
			'to' => [
				[
					'address' => 'testguy@domain.com',
					'name' => 'Test Guy'
				],
				[
					'address' => 'testgal@domain.com',
					'name' => 'Test Gal'
				]
			],
			'cc' => [
				[
					'address' => 'cc@domain.com',
					'name' => 'Test CC'
				]
			],
			'bcc' => [
				[
					'address' => 'bcc@domain.com',
					'name' => 'Test BCC'
				]
			]
		],
		'from' => [
			'address' => 'noreply@domain.com',
			'name' => 'No Reply'
		],
		'PHPMailer' => [
			'isSMTP' => true,
			//'SMTPDebug' => ((ENVIRONMENT == 'production') ? 0 : 2), // More info: https://github.com/PHPMailer/PHPMailer/wiki/SMTP-Debugging
			'Port' => ((ENVIRONMENT == 'production') ? 25 : 1025)
		]
	],
	'handleNotices' => true
]);

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
