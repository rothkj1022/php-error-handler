<?php
return [
	'displayErrors' => ((ENVIRONMENT == 'production') ? false : true),
	'emailErrors' => ((ENVIRONMENT == 'production') ? true : false),
	'logErrors' => true, //requires database
	'cacheFolder' => FS_CACHE, //must end with slash
	'database' => [
		'driver' => 'pdo', //pdo or mysql
		'dsn' => 'mysql:host='.DB_HOSTNAME.';dbname='.DB_DATABASE.';port='.DB_PORT.';charset=UTF8',
		'username' => DB_USERNAME,
		'password' => DB_PASSWORD
	],
	'email' => [
		'recipients' => [
			'to' => [
				[
					'address' => 'email@domain.com',
					'name' => 'To Name'
				]
			]
		],
		'from' => [
			'address' => 'from@domain.com',
			'name' => 'From Name'
		],
		'replyTo' => [
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
];
