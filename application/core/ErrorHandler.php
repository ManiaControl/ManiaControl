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
		$message .= "Class: " . get_class($ex) . PHP_EOL;
		$message .= "Trace: {$ex->getTraceAsString()}" . PHP_EOL;
		logMessage($message);

		$error                    = array();
		$error["Type"]            = "Exception";
		$error["Message"]         = $message;
		$error['OperatingSystem'] = php_uname();
		$error['PHPVersion']      = phpversion();

		if ($this->maniaControl->server) {
			$error['ServerLogin'] = $this->maniaControl->server->login;
		} else {
			$error['ServerLogin'] = '';
		}

		if ($this->maniaControl->settingManager && $this->maniaControl->updateManager) {
			$error['UpdateChannel']       = $this->maniaControl->settingManager->getSetting($this->maniaControl->updateManager, UpdateManager::SETTING_UPDATECHECK_CHANNEL);
			$error['ManiaControlVersion'] = $this->maniaControl->updateManager->getCurrentBuildDate();
		} else {
			$error['UpdateChannel']       = '';
			$error['ManiaControlVersion'] = ManiaControl::VERSION;
		}

		$json = json_encode($error);
		$info = base64_encode($json);

		$url     = ManiaControl::URL_WEBSERVICE . "errorreport?error=" . urlencode($info);
		$success = FileUtil::loadFile($url);

		if (!json_decode($success)) {
			logMessage("Exception-Report failed!");
		} else {
			logMessage("Exception successfully reported!");
		}

		$this->maniaControl->restart();
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

			$error                    = array();
			$error["Type"]            = "Error";
			$error["Message"]         = $message;
			$error['OperatingSystem'] = php_uname();
			$error['PHPVersion']      = phpversion();

			if ($this->maniaControl->server) {
				$error['ServerLogin'] = $this->maniaControl->server->login;
			} else {
				$error['ServerLogin'] = '';
			}

			if ($this->maniaControl->settingManager && $this->maniaControl->updateManager) {
				$error['UpdateChannel']       = $this->maniaControl->settingManager->getSetting($this->maniaControl->updateManager, UpdateManager::SETTING_UPDATECHECK_CHANNEL);
				$error['ManiaControlVersion'] = $this->maniaControl->updateManager->getCurrentBuildDate();
			} else {
				$error['UpdateChannel']       = '';
				$error['ManiaControlVersion'] = ManiaControl::VERSION;
			}

			$json = json_encode($error);
			$info = base64_encode($json);

			$url     = ManiaControl::URL_WEBSERVICE . "errorreport?error=" . urlencode($info);
			$success = FileUtil::loadFile($url);

			if (!json_decode($success)) {
				logMessage("Error-Report failed!");
			} else {
				logMessage("Error successfully reported!");
			}
		}

		if ($errorNumber == E_ERROR || $errorNumber == E_USER_ERROR || $errorNumber == E_FATAL) {
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
		if ($errorLevel == E_CORE_ERROR) {
			return '[PHP CORE ERROR]';
		}
		if ($errorLevel == E_COMPILE_ERROR) {
			return '[PHP COMPILE ERROR]';
		}
		if ($errorLevel == E_RECOVERABLE_ERROR) {
			return '[PHP RECOVERABLE ERROR]';
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