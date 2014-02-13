<?php

namespace ManiaControl;

use ManiaControl\Files\FileUtil;
use ManiaControl\Update\UpdateManager;

/**
 * Error and Exception Manager Class
 *
 * @author steeffeen & kremsy
 */
class ErrorHandler {
	/**
	 * Private Properties
	 */
	private $maniaControl = null;

	/**
	 * Construct Error Handler
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		set_error_handler(array(&$this, 'errorHandler'), -1);
		set_exception_handler(array(&$this, 'exceptionHandler'));
	}

	/**
	 * ManiaControl ExceptionHandler
	 * ManiaControl Shuts down after exception
	 *
	 * @param \Exception $ex
	 */
	public function exceptionHandler(\Exception $ex) {
		$message = "[ManiaControl EXCEPTION]: {$ex->getMessage()}" . PHP_EOL;
		$message .= "Class: ". get_class($ex) . PHP_EOL;
		$message .= "Trace: {$ex->getTraceAsString()}" . PHP_EOL;
		logMessage($message);

		$error                        = array();
		$error["Type"]                = "Exception";
		$error["Message"]             = $message;
		$error['ManiaControlVersion'] = ManiaControl::VERSION;
		$error['OperatingSystem']     = php_uname();
		$error['PHPVersion']          = phpversion();
		if ($this->maniaControl->server != null) {
			$error['ServerLogin'] = $this->maniaControl->server->login;
		} else {
			$error['ServerLogin'] = null;
		}

		$json = json_encode($error);
		$info = base64_encode($json);

		$url     = UpdateManager::URL_WEBSERVICE . "errorreport?error=" . urlencode($info);
		$success = FileUtil::loadFile($url);

		if (!json_decode($success)) {
			logMessage("Exception-Report failed");
		}

		exit();
	}

	/**
	 * Error Handler
	 *
	 * @param $errorNumber
	 * @param $errorString
	 * @param $errorFile
	 * @param $errorLine
	 * @return bool
	 */
	public function errorHandler($errorNumber, $errorString, $errorFile, $errorLine) {
		if (error_reporting() == 0) {
			// Error suppressed
			return false;
		}
		// Log error
		$errorTag = $this->getErrorTag($errorNumber);
		$message  = "{$errorTag}: {$errorString} in File '{$errorFile}' on Line {$errorLine}!";
		logMessage($message);

		if ($errorNumber != E_USER_ERROR && $errorNumber != E_USER_WARNING && $errorNumber != E_USER_NOTICE) {
			$error                        = array();
			$error["Type"]                = "Error";
			$error["Message"]             = $message;
			$error['ManiaControlVersion'] = ManiaControl::VERSION;
			$error['OperatingSystem']     = php_uname();
			$error['PHPVersion']          = phpversion();
			if ($this->maniaControl->server != null) {
				$error['ServerLogin'] = $this->maniaControl->server->login;
			} else {
				$error['ServerLogin'] = null;
			}

			$json = json_encode($error);
			$info = base64_encode($json);

			$url     = UpdateManager::URL_WEBSERVICE . "errorreport?error=" . urlencode($info);
			$success = FileUtil::loadFile($url);

			if (!json_decode($success)) {
				logMessage("Error-Report failed");
			}
		}

		if ($errorNumber == E_ERROR || $errorNumber == E_USER_ERROR) {
			logMessage('Stopping execution...');
			exit();
		}
		return false;
	}

	/**
	 * Get the prefix for the given error level
	 *
	 * @param int $errorLevel
	 * @return string
	 */
	public function getErrorTag($errorLevel) {
		if ($errorLevel == E_NOTICE) {
			return '[PHP NOTICE]';
		}
		if ($errorLevel == E_WARNING) {
			return '[PHP WARNING]';
		}
		if ($errorLevel == E_ERROR) {
			return '[PHP ERROR]';
		}
		if ($errorLevel == E_USER_NOTICE) {
			return '[ManiaControl NOTICE]';
		}
		if ($errorLevel == E_USER_WARNING) {
			return '[ManiaControl WARNING]';
		}
		if ($errorLevel == E_USER_ERROR) {
			return '[ManiaControl ERROR]';
		}
		return "[PHP {$errorLevel}]";
	}
} 