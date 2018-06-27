# php-error-handler

PHP Error Handler can send you comprehensive error reports via email as well as output to the screen if you so choose.

### Features:

* Get notified via email of php errors occurring on your website
* Option to use mysqli or pdo database connection
* Send error reports via email, display to the screen, or both
* Can log all errors in a database, with customizable retention period
* Flood control makes sure you don't get blasted with multiple emails with the same error within a configurable time period
* Send to one or more email recipients, including cc and bcc options
* Only send reports for the error types you choose (errors, warnings, notices, deprecations)

Written by: Kevin Roth - [https://kevinroth.com](https://kevinroth.com)

### License
Released under the MIT license - http://opensource.org/licenses/MIT

## Requirements

* PHP >= 5.4

### Optional

* MySQL or other PDO compatible database for logging & flood control features

## Installation
Run the following command in your command line shell in your php project

```sh
$ composer require rothkj1022/php-error-handler
```

Done.

You may also edit composer.json manually then perform ```composer update```:

```
"require": {
    "rothkj1022/php-error-handler": "^2.0.0"
}
```

## Getting started

### Example usage with composer
```php
require('vendor/autoload.php');
use rothkj1022\PHPErrorHandler;

$errorHandler = new PHPErrorHandler\PHPErrorHandler();
```

### Example usage without composer
```php
require('src/class.errorhandler.php');
use rothkj1022\PHPErrorHandler;

$errorHandler = new PHPErrorHandler\PHPErrorHandler();
```

### Example with email and database configuration

After including the class file via autoload.php or directly, instantiate the object with a json array like this:

```php
$errorHandler = new PHPErrorHandler\PHPErrorHandler([
	'displayErrors' => false,
	'emailErrors' => true,
	'logErrors' => true, //requires database
	'purgeLogTimeout' => '1 DAY', //use mysql date_add interval syntax or set to false
	//'cacheFolder' => '', //needed for flood control
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
				]
		],
		'from' => [
			'address' => 'noreply@domain.com',
			'name' => 'No Reply'
		]
	]
]);
```

## Configuration options

### General

#### displayErrors

Display error details to screen (not recommended for production)

```
default: false
options: boolean (true / false)
```

#### emailErrors

Email error reports to configured recipient(s)

```
default: false
options: boolean (true / false)
```

#### logErrors

Log errors to configured database

```
default: false
options: boolean (true / false)
```

#### purgeLogInterval

If configured, purges logs in database older than the given interval.

Use mysql date_add interval syntax or set to false

```
default: '1 DAY'
options: string (date_add interval syntax), false
```

#### floodInterval

If database is configured, does not send repeat errors via email within set interval

Use mysql date_add interval syntax or set to false

```
default: '15 MINUTE'
options: string (date_add interval syntax), false
```

### database

Json array of database configuration options

```
default: []
options: array
```

##### MySQL Example:

```
[
	'driver' => 'mysql',
	'hostname' => 'localhost',
	'username' => 'mysqluser',
	'password' => 'mysqlpass',
	'database' => 'mydatabase',
	'port' => 3306,
	'charset' => 'utf8',
	'table' => 'error_reports'
]
```

##### PDO (with mysql) Example:

```
[
	'driver' => 'pdo',
	'dsn' => 'mysql:host=localhost;dbname= mydatabase;port=3306;charset=UTF8',
	'username' => 'mysqluser',
	'password' => 'mysqlpass'
]
```

#### driver

Driver to be used for the database connection

```
default: 'mysql'
options: 'mysql', 'pdo'
```

#### dsn

DSN connection string for PDO connections

```
default: ''
options: string (pdo dsn connection string)
```

#### hostname

Host name of the database server

```
default: 'localhost'
options: string (server host name)
```

#### username

Database user name

```
default: ''
options: string (db username)
```

#### password

Database password

```
default: ''
options: string, (db password)
```

#### database

Database name

```
default: ''
options: string (db name)
```

#### port

Database port

```
default: 3306
options: integer (port number)
```

#### charset

Database character set

```
default: 'utf8'
options: string (db charset)
```

#### table

Database table name for logging error reports

```
default: 'error_reports'
options: string (db table name)
```

### email



#### recipients

Array of to, cc, or bcc types

##### to, cc, & bcc

Array of contacts (name, address)

##### address

Email address of the contact

```
default: null
options: string (valid email address)
```

##### name

Name of the contact

```
default: null
options: string
```

#### from

##### address

Email address of the contact

```
default: null
options: string (valid email address)
```

##### name

Name of the contact

```
default: null
options: string
```

#### replyTo

##### address

Email address of the contact

```
default: null
options: string (valid email address)
```

##### name

Name of the contact

```
default: null
options: string
```

#### subject

```
default: 'PHP Error Report from ' . $_SERVER['SERVER_NAME']
options: string
```

#### PHPMailer

Json array of email configuration options.  See [PHPMailer documentation](https://github.com/PHPMailer/PHPMailer/wiki) for detailed default options.

##### CharSet

```
default: 'utf-8'
options: string
```

##### isSMTP

```
default: false
options: boolean (true, false)
```

##### Host

```
default: 'localhost'
options: string
```

##### Port

```
default: 25
options: integer
```

##### SMTPDebug

Get debug info for SMTP sending.  See [SMTP Debugging](https://github.com/PHPMailer/PHPMailer/wiki/SMTP-Debugging) for more info.

See also PHPMailer [SMTPDebug property](http://phpmailer.github.io/PHPMailer/classes/PHPMailer.PHPMailer.PHPMailer.html#property_SMTPDebug) documentation

```
default: 0 (no output)
options: integer
```

##### SMTPAutoTLS

Whether to enable TLS encryption automatically if a server supports it, even if \`SMTPSecure\` is not set to 'tls'.

See also PHPMailer [SMTPAutoTLS property](http://phpmailer.github.io/PHPMailer/classes/PHPMailer.PHPMailer.PHPMailer.html#property_SMTPAutoTLS) documentation

```
default: true
options: boolean (true, false)
```

##### SMTPAuth

Enable SMTP authorization

```
default: false
options: boolean (true, false)
```

##### Username

SMTP account username / email address

```
default: ''
options: string
```

##### Password

SMTP Password

```
default: ''
options: string
```

##### SMTPSecure

Type of encryption used for SMTP sending

```
default: 'tls'
options: string ('ssl', 'tls')
```

##### SMTPOptions

See PHPMailer [SMTPOptions property](http://phpmailer.github.io/PHPMailer/classes/PHPMailer.PHPMailer.PHPMailer.html#property_SMTPOptions
) documentation

```
default: []
options: array
```

###### Example

```
// Disable verification for self-signed ssl certificates
[
	'ssl' => [
		'verify_peer' => false,
		'verify_peer_name' => false,
		'allow_self_signed' => true
	]
]
```

#### errorTypes

Array of PHP error types that you want to be handled

```
default: [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR]
options: array
```

#### warningTypes

Array of PHP warning types that you want to be handled

```
default: [E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING, E_RECOVERABLE_ERROR]
options: array
```

#### noticeTypes

Array of PHP warning types that you want to be handled

```
default: [E_NOTICE, E_USER_NOTICE, E_DEPRECATED, E_USER_DEPRECATED]
options: array
```

#### handleErrors

Whether or not to process errors

```
default: true
options: boolean (true, false)
```

#### handleWarnings

Whether or not to process warnings

```
default: true
options: boolean (true, false)
```

#### handleNotices

Whether or not to process notices

```
default: false
options: boolean (true, false)
```

### Public methods

<table>
<thead><tr>
  <th>Plugin method</th> <th>Description</th>
</tr></thead>
<tbody>
  <tr>
    <th>mysqlError($errorMsg, $sql, $errfile = null, $errline = 0, $die = false)</th>
    <td>
      Send a MySQL-specific error report, including the query. $errorMsg = the error message to send, usually $mysqli->error. $sql = the query. $errfile = the file in which the error occurred, called by using __FILE__. $errline = the line of the file on which the error occurred, called by using __LINE__. $die = whether or not to stop processing the script after sending the error. See example below.
    </td>
  </tr>
  <tr>
    <th>sendError($errorMsg, $msgDetails = '', $errfile = null, $errline = 0, $die = false)</th>
    <td>
      Send a custom error report. $errorMsg = the error message to send. $msgDetails = further details regarding your custom error. $errfile = the file in which the error occurred, called by using __FILE__. $errline = the line of the file on which the error occurred, called by using __LINE__. $die = whether or not to stop processing the script after sending the error. See example below.
    </td>
  </tr>
</table>

##### mysqlError Example:

```
$mysqli = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);

$sql = "select blah from products limit 3";
$query = $mysqli->query($sql) or $errorHandler->mysqlError($mysqli->error, $sql, __FILE__, __LINE__);

```

##### sendError Example:

```
$errorHandler->sendError('$myVar is not defined.', 'You should really define that variable.', __FILE__, __LINE__);
```

## Changelog

### Version 2.0.5

* Added config var for allowing change of PHPMailer SMTPAutoTLS setting

### Version 2.0.4

* Added config vars for allowing change of reply-to address

### Version 2.0.3

* Added config vars to disable processing errors, warnings, and notices

### Version 2.0.1 & 2.0.2

* Fixes for composer integration

### Version 2.0.0

* Code overhaul with composer integration
* Added changelog, readme documentation
* Enhancement: added PDO database option

