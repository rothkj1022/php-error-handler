<?php
namespace rothkj1022\PHPErrorHandler;

class PHPErrorHandler {
	private $config = [];
	private $dbConn;

	// Class constructor
	public function __construct($config = []) {
		//set default config
		$this->config = $defaultConfig = [
			'displayErrors' => false,
			'emailErrors' => false,
			'logErrors' => false, //requires database
			'purgeLogInterval' => '1 DAY', //use mysql date_add interval syntax or set to false
			'floodInterval' => '15 MINUTE', //use mysql date_add interval syntax or set to false
			'cacheFolder' => false,
			'database' => [
				'driver' => 'mysql', //pdo or mysql
				'dsn' => '',
				'hostname' => 'localhost',
				'username' => '',
				'password' => '',
				'database' => '',
				'port' => 3306,
				'charset' => 'utf8',
				'table' => 'error_reports'
			],
			'ipinfoToken' => '',
			'email' => [
				'recipients' => [
					'to' => [
						[
							'address' => '',
							'name' => ''
						]
					],
					'cc' => [],
					'bcc' => []
				],
				'from' => [
					'address' => '',
					'name' => ''
				],
				'replyTo' => [
					'address' => '',
					'name' => ''
				],
				'subject' => 'PHP Error Report from ' . $_SERVER['SERVER_NAME'],
				'PHPMailer' => [
					'CharSet' => 'utf-8',
					'isSMTP' => false,
					'Host' => 'localhost',
					'Port' => 25,
					'SMTPDebug' => 0,
					'SMTPAutoTLS' => true,
					'SMTPAuth' => false,
					'Username' => '', // SMTP account username / email address
					'Password' => '', // SMTP password
					'SMTPSecure' => 'tls',
					'SMTPOptions' => []
				]
			],
			'errorTypes' => [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR],
			'warningTypes' => [E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING, E_RECOVERABLE_ERROR],
			'noticeTypes' => [E_NOTICE, E_USER_NOTICE, E_DEPRECATED, E_USER_DEPRECATED],
			'handleErrors' => true,
			'handleWarnings' => true,
			'handleNotices' => false
		];

		// Merge settings
		if (sizeof($config) > 0) {
			$this->config = $this->array_merge_recursive_distinct($this->config, $config);
		}

		// Set the db connection
		if ($this->config['logErrors']) {
			$dbConfig = $this->config['database'];
			if ($dbConfig['driver'] == 'pdo') {
				// establish pdo connection
				$this->dbConn = new \PDO($dbConfig['dsn'], $dbConfig['username'], $dbConfig['password']);
				//set pdo error mode
				$this->dbConn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			} else if ($dbConfig['driver'] == 'mysql') {
				// establish mysqli connection
				$this->dbConn = new \mysqli($dbConfig['hostname'], $dbConfig['username'], $dbConfig['password'], $dbConfig['database'], $dbConfig['port']);
				$this->dbConn->set_charset($dbConfig['charset']);
			}
		}

		//catch and handle all warnings
		error_reporting(implode('|', $this->config['warningTypes']));
		set_error_handler([$this, 'genericErrorHandler']);

		//handle exceptions
		restore_exception_handler();

		//handle fatal errors
		//ob_start([$this, 'fatalErrorHandler']);
		register_shutdown_function([$this, 'fatalErrorShutdownHandler']);
	}

	private function errorString($errno, $errstr, $errfile, $errline) {
		$output = false;
		if (in_array($errno, $this->config['errorTypes']) && $this->config['handleErrors']) {
			$output = "<b>Fatal Error:</b> $errstr in <b>$errfile</b> on line <b>$errline</b>";
		} else if (in_array($errno, $this->config['warningTypes']) && $this->config['handleWarnings']) {
			$output = "<b>Warning:</b> $errstr in <b>$errfile</b> on line <b>$errline</b>";
		} else if (in_array($errno, $this->config['noticeTypes']) && $this->config['handleNotices']) {
			$output = "<b>Notice:</b> $errstr in <b>$errfile</b> on line <b>$errline</b>";
		} else if (in_array($errno, array(E_STRICT))) {
			//$output = "<b>Strict Standards:</b> $errstr in <b>$errfile</b> on line <b>$errline</b>";
		} else {
			//$output = "<b>Unknown error type [$errno]:</b> $errstr in <b>$errfile</b> on line <b>$errline</b>";
		}
		return $output;
	}

	private function errorMsgOutput($errorMsg, $msgDetails = '') {
		$output = 'An error has occured.<br /><br />' . "\n\n";
		$output .= 'Time: ' . date("m/d/y g:i:s a") . '<br /><br />' . "\n\n";
		$output .= 'Message: ' . $errorMsg . '<br /><br />' . "\n\n";
		if (!empty($msgDetails)) {
			$output .= 'Details: ' . $msgDetails . '<br /><br />' . "\n\n";
		}

		$arrayVars = [
			'$_GET' => (isset($_GET) ? $_GET : array()),
			'$_POST' => (isset($_POST) ? $_POST : array()),
			//'$_REQUEST' => (isset($_SESSION) ? $_SESSION : array())$_REQUEST,
			'$_FILES' => (isset($_FILES) ? $_FILES : array()),
			'$_COOKIE' => (isset($_COOKIE) ? $_COOKIE : array()),
			'$_SESSION' => (isset($_SESSION) ? $_SESSION : array()),
			'$_SERVER' => (isset($_SERVER) ? $_SERVER : array())
		];
		//for ($i = 0; $i < sizeof($arrayVars); $i++) {
		foreach ($arrayVars as $aryKey => $aryTmp) {
			if (is_array($aryTmp) && sizeof($aryTmp) > 0) {
				$output .= '<b>' . $aryKey . ':</b><br />' . "\n";
				foreach ($aryTmp as $key => $val) {
					//$output .= "\t" . $key . ': ' . ((empty($val)) ? $val : htmlentities($val)) . "\n";
					if (is_array($val) && sizeof($val) > 0) {
						//$val = print_r($val, true);
						ob_start();
						var_dump($val);
						$val = ob_get_clean();
						$val = str_replace("  ", ' &nbsp; &nbsp;', $val);
						$val = nl2br($val);
					}
					$output .= "\t" . $key . ': ' . $val . '<br />' . "\n";
				}
				//$output .= print_r($aryTmp, true);
				$output .= '<br />' . "\n";
			}
		}

		//display ip location info
		$ip = $_SERVER['REMOTE_ADDR'];
		$ipData = [];
		$uri = "https://ipinfo.io/{$ip}/json";
		$uri .= ((!empty($this->config['ipinfoToken'])) ? '?token='.$this->config['ipinfoToken'] : '');
		if ($this->config['cacheFolder'] && is_dir($this->config['cacheFolder'])) {
			//cache ip lookups
			$cache = new \rothkj1022\FileCache\FileCache($this->config['cacheFolder']);
			$ipData = $cache->file_get_contents($uri); //default ttl is 1 hr
		} else {
			//no caching
			$ipData = $this->file_get_contents($uri);
		}
		$output .= '<b>IP Details:</b><br />' . "\n";
		if ($ipInfo = json_decode($ipData)) {
			foreach ($ipInfo as $key => $val) {
				$output .= "\t" . $key . ': ' . $val . '<br />' . "\n";
			}
			$output .= '<br /><br />'."\n";
		} else {
			$output .= $ipData;
		}
		$output .= '<hr />'."\n";

		return $output;
	}

	private function logErrorReport($errno, $errstr, $errfile, $errline, $errdetails) {
		//logs error report, returns whether report has already been logged in last x mins
		$alreadyLogged = false;

		restore_error_handler();

		$dbDriver = $this->config['database']['driver'];
		$dbTable = $this->config['database']['table'];

		// connect to the MySQL server
		if ($dbDriver == 'mysql') {
			$mysqli = $this->dbConn;
		} else if ($dbDriver == 'pdo') {
			$pdo = $this->dbConn;
		}

		//create the table if necessary
		$sql = "CREATE TABLE IF NOT EXISTS `".$dbTable."` ( ".
			"`error_id` int(11) NOT NULL AUTO_INCREMENT, ".
			"`domain` varchar(255) NOT NULL, ".
			"`timestamp` datetime NOT NULL, ".
			"`error_level` varchar(32) NOT NULL, ".
			"`error_msg` varchar(2000) NOT NULL, ".
			"`error_file` varchar(255) NOT NULL, ".
			"`error_line` int(11) DEFAULT NULL, ".
			"`error_details` longtext, ".
			"PRIMARY KEY (`error_id`) ".
			") ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;";
		if ($dbDriver == 'mysql') {
			$mysqli->query($sql) or die('Error: '.$mysqli->error);
		} else if ($dbDriver == 'pdo') {
			try {
				$pdo->exec($sql);
			} catch (PDOException $e) {
				die($e->getMessage().'. Query: '.$sql);
			}
		}

		//purge logs older than purgeLogInterval
		//prevents logs from getting out of control
		if ($this->config['purgeLogInterval']) {
			$sql = "delete from ".$dbTable." where timestamp < date_add(now(), INTERVAL -".$this->config['purgeLogInterval'].")";
			if ($dbDriver == 'mysql') {
				$mysqli->query($sql);
			} else if ($dbDriver == 'pdo') {
				try {
					$pdo->exec($sql);
				} catch (PDOException $e) {
					die($e->getMessage().'. Query: '.$sql);
				}
			}
		}

		if ($dbDriver == 'mysql') {
			//check if error has already been logged in past floodInterval mins
			$sql = "select * from ".$dbTable." where domain = '".$_SERVER['SERVER_NAME']."' and error_level = ".$errno." and error_msg = '".$mysqli->real_escape_string($errstr)."' and error_file = '".$mysqli->real_escape_string($errfile)."' and error_line = ".$errline." and timestamp > date_add(now(), INTERVAL -".$this->config['floodInterval'].");";
			//echo $sql;
			$query = $mysqli->query($sql);
			if ($query->num_rows > 0) {
				//there is already an error logged within the last floodInterval
				$alreadyLogged = true;
			} else {
				//error has not been logged in last floodInterval, log it
				$sql = "insert into ".$dbTable." (domain, timestamp, error_level, error_msg, error_file, error_line, error_details)";
				$sql .= " values ('".$_SERVER['SERVER_NAME']."', now(), ".$errno.", '".$mysqli->real_escape_string($errstr)."', '".$mysqli->real_escape_string($errfile)."', ".$errline.", '".$mysqli->real_escape_string($errdetails)."');";
				//echo $sql;
				$mysqli->query($sql); // or die('Error inserting record: '.$mysqli->error.': '.$sql);
			}
			$mysqli->close();
		} else if ($dbDriver == 'pdo') {
			//check if error has already been logged in past floodInterval
			$sql = "select * from ".$dbTable." where domain = :domain and error_level = :errno and error_msg = :errstr and error_file = :errfile and error_line = :errline and timestamp > date_add(now(), INTERVAL -".$this->config['floodInterval'].");";
			try {
				$stmt = $pdo->prepare($sql) or die(print_r($pdo->errorInfo(), true).' Query: '.$sql);
				$stmt->bindParam(':domain', $_SERVER['SERVER_NAME']);
				$stmt->bindParam(':errno', $errno);
				$stmt->bindParam(':errstr', $errstr);
				$stmt->bindParam(':errfile', $errfile);
				$stmt->bindParam(':errline', $errline);
				$stmt->execute();
				$result = $stmt->fetchAll();
			} catch (PDOException $e) {
				die($e->getMessage().'. Query: '.$sql);
			}
			if (sizeof($result) > 0) {
				//there is already an error logged within the last floodInterval
				$alreadyLogged = true;
			} else {
				//error has not been logged in last floodInterval, log it
				$sql = "insert into ".$dbTable." (domain, timestamp, error_level, error_msg, error_file, error_line, error_details)";
				$sql .= " values (:domain, now(), :errno, :errstr, :errfile, :errline, :errdetails);";
				try {
					$stmt = $pdo->prepare($sql) or die(print_r($pdo->errorInfo(), true).' Query: '.$sql);
					$stmt->bindParam(':domain', $_SERVER['SERVER_NAME']);
					$stmt->bindParam(':errno', $errno);
					$stmt->bindParam(':errstr', $errstr);
					$stmt->bindParam(':errfile', $errfile);
					$stmt->bindParam(':errline', $errline);
					$stmt->bindParam(':errdetails', $errdetails);
					$stmt->execute();
				} catch (PDOException $e) {
					die($e->getMessage().'. Query: '.$sql);
				}
			}
		}

		set_error_handler([$this, 'genericErrorHandler']);

		return $alreadyLogged;
	}

	private function emailErrorReport($htmlBody) {
		// do not email errors if visitor is a bot/spider
		if (strpos($_SERVER['HTTP_USER_AGENT'], 'bot') > 0 || strpos($_SERVER['HTTP_USER_AGENT'], 'spider') > 0) {
			return false;
		}

		restore_error_handler();

		$emailConfig = $this->config['email'];
		$phpMailerConfig = $emailConfig['PHPMailer'];

		$mail = new \PHPMailer\PHPMailer\PHPMailer();
		$mail->isHTML(true);
		$mail->CharSet = $phpMailerConfig['CharSet'];
		if ($phpMailerConfig['isSMTP']) {
			$mail->isSMTP();
			$mail->Host = $phpMailerConfig['Host']; // sets the SMTP server
			$mail->Port = $phpMailerConfig['Port']; // set the SMTP port for the SMTP server
			$mail->SMTPDebug = $phpMailerConfig['SMTPDebug'];
			$mail->SMTPAutoTLS = $phpMailerConfig['SMTPAutoTLS']; // enable SMTP authentication
			$mail->SMTPAuth = $phpMailerConfig['SMTPAuth']; // enable SMTP authentication
			$mail->Username = $phpMailerConfig['Username']; // SMTP account username / email address
			$mail->Password = $phpMailerConfig['Password']; // SMTP password
			$mail->SMTPSecure = $phpMailerConfig['Password'];
			if (is_array($phpMailerConfig['SMTPOptions'])) {
				$mail->SMTPOptions = $phpMailerConfig['SMTPOptions'];
			}
		}

		// Set subject
		$mail->Subject = $emailConfig['subject'];

		// set from
		$mail->From = $emailConfig['from']['address'];
		$mail->FromName = ((!empty($emailConfig['from']['name'])) ? $emailConfig['from']['name'] : $emailConfig['from']['address']);

		// When user hits reply in their mail client, the email will go to this address.
		if (!empty($emailConfig['replyTo']['address'])) {
			$mail->addReplyTo($emailConfig['replyTo']['address'], ((!empty($emailConfig['replyTo']['name'])) ? $emailConfig['replyTo']['name'] : ''));
		} else {
			$mail->addReplyTo($mail->From, $mail->FromName);
		}

		// Set recipient(s)
		foreach ($emailConfig['recipients']['to'] as $recipient) {
			$mail->addAddress($recipient['address'], ((!empty($recipient['name'])) ? $recipient['name'] : $recipient['address']));
		}
		foreach ($emailConfig['recipients']['cc'] as $recipient) {
			$mail->addCC($recipient['address'], ((!empty($recipient['name'])) ? $recipient['name'] : $recipient['address']));
		}
		foreach ($emailConfig['recipients']['bcc'] as $recipient) {
			$mail->addBCC($recipient['address'], ((!empty($recipient['name'])) ? $recipient['name'] : $recipient['address']));
		}

		$mail->Body = $htmlBody;
		$altBody = str_replace('<hr />', '---', $mail->Body);
		$altBody = strip_tags($altBody);
		$mail->AltBody = $altBody;
		//print_r($mail);exit;

		$mail->Send();

		set_error_handler([$this, 'genericErrorHandler']);

		return true;
	}

	public function genericErrorHandler($errno, $errstr, $errfile, $errline) {
		$isError = ((in_array($errno, $this->config['errorTypes']) & $errno) === $errno);
		if ($isError && function_exists('set_status_header')) {
			//for CodeIgniter
			set_status_header(500);
		}

		if (error_reporting() && $errorString = $this->errorString($errno, $errstr, $errfile, $errline)) {
			$output = $this->errorMsgOutput($errorString);

			if ($this->config['logErrors']) {
				$alreadyLogged = $this->logErrorReport($errno, $errstr, $errfile, $errline, $output);
			} else {
				$alreadyLogged = false;
			}

			if (!$alreadyLogged && $this->config['emailErrors']) {
				$this->emailErrorReport($output);
			}

			if ($this->config['displayErrors']) {
				echo $output;
			}
		}

		//halt on error
		if ($isError) {
			exit(1); // EXIT_ERROR
		}
	}

	public function fatalErrorShutdownHandler() {
		// run after entire script has executed
		$error = error_get_last();
		if (in_array($error['type'], $this->config['errorTypes'])) {
			//report and handle fatal errors
			error_reporting(implode('|', $this->config['errorTypes']));
			$this->genericErrorHandler($error['type'], $error['message'], $error['file'], $error['line']);
			error_reporting(0);
		}
	}

	public function mysqlError($errorMsg, $sql, $errfile = null, $errline = 0, $die = false) {
		$this->sendError('<b>MySql Error:</b> '.$errorMsg, '<b>Query:</b> '.$sql, $errfile, $errline, $die);
	}

	public function sendError($errorMsg, $msgDetails = '', $errfile = null, $errline = 0, $die = false) {
		if (!empty($errfile) && !empty($errline)) {
			$errorMsg .= ' in <b>'.$errfile.'</b> on line <b>'.$errline.'</b>';
		}

		$output = $this->errorMsgOutput('<b>Warning:</b> ' . $errorMsg, $msgDetails);

		$errfile = (($errfile == null) ? $_SERVER['SCRIPT_FILENAME'] : $errfile);

		if ($this->config['logErrors']) {
			$alreadyLogged = $this->logErrorReport(E_USER_WARNING, $errorMsg, $errfile, $errline, $output);
		} else {
			$alreadyLogged = false;
		}

		if (!$alreadyLogged && $this->config['emailErrors']) {
			$this->emailErrorReport($output);
		}

		if ($this->config['displayErrors']) {
			echo $output;
		}

		//stop executing script
		if ($die) exit;
	}

	//found at: http://php.net/manual/en/function.array-merge-recursive.php#92195
	private function array_merge_recursive_distinct ( array &$array1, array &$array2 ) {
		$merged = $array1;

		foreach ( $array2 as $key => &$value ) {
			if ( is_array ( $value ) && isset ( $merged [$key] ) && is_array ( $merged [$key] ) ) {
				$merged [$key] = $this->array_merge_recursive_distinct ( $merged [$key], $value );
			} else {
				$merged [$key] = $value;
			}
		}

		return $merged;
	}

	/**
	 * Get file or url contents from given path
	 * @param string $uri The uri of the data we are fetching
	 * @returns the data or false on failure
	 */
	private function file_get_contents($uri) {
		$data = ((is_file($uri)) ? file_get_contents($uri) : $this->file_get_contents_remote($uri));
		if ($data) {
			return $data;
		}

		return false;
	}

	/**
	 * Get remote url contents from given path
	 * @param string $uri The uri of the data we are fetching
	 * @returns the data or false on failure
	 */
	private function file_get_contents_remote($uri) {
		//use guzzle to handle request
		$client = new \GuzzleHttp\Client();
		$requestOptions = [ 'verify' => false ]; // accommodates self-signed certs

		try {
			$response = $client->request('GET', $uri, $requestOptions);
			if (in_array($response->getStatusCode(), array(200, 206))) {
				$body = $response->getBody();
				return (string)$body;
			}
		} catch (\Exception $e) {
			$msgDetails = '<br /><b>Guzzle GET request from:</b> '.$uri.'<br /><br />';
			$request = $e->getRequest();
			$requestMsg = \GuzzleHttp\Psr7\str($request);
			$msgDetails .= '<b>Request:</b> '.$requestMsg;
			if ($e->hasResponse()) {
				$response = $e->getResponse();
				$msgDetails .= '<br /><br /><b>Response:</b> HTTP/'.$response->getProtocolVersion().' '.$response->getStatusCode().' '.$response->getReasonPhrase().'<br /><br />';
				$responseHeaders = $response->getHeaders();
				$headersList = '';
				foreach($responseHeaders as $key => $val) {
					$val = (is_array($val) ? $val[0] : $val);
					$headersList .= $key.': '.$val.'<br />';
				}
				$msgDetails .= '<b>Response Headers:</b><br />'.$headersList;
			}
			$msgDetails = trim($msgDetails);
			return 'Error fetching content from: '.$uri.'. Request: '.$requestMsg.'. Details: '.$msgDetails;
		}
		return false;
	}
}
