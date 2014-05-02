<?php

namespace ManiaControl;

use ManiaControl\Files\FileUtil;
use ManiaControl\Update\UpdateManager;

/**
 * Error and Exception Manager Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ErrorHandler {
	/*
	 * Constants
	 */
	const MC_DEBUG_NOTICE              = "ManiaControl.DebugNotice";
	const SETTING_RESTART_ON_EXCEPTION = 'Automatically restart on Exceptions';

	/*
	 * Private Properties
	 */
	private $maniaControl = null;
	private $reportErrors = true;

	/**
	 * Construct Error Handler
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		set_error_handler(array(&$this, 'errorHandler'), -1);
		set_exception_handler(array(&$this, 'exceptionHandler'));
	}

	/**
	 * Initialize other Error Handler Features
	 */
	public function init() {
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_RESTART_ON_EXCEPTION, true);
	}

	/**
	 * ManiaControl ExceptionHandler
	 *
	 * @param \Exception $ex
	 * @param bool       $shutdown
	 */
	public function exceptionHandler(\Exception $ex, $shutdown = true) {
		// Log exception
		$message      = "[ManiaControl EXCEPTION]: {$ex->getMessage()}";
		$traceMessage = 'Class: ' . get_class($ex) . PHP_EOL;
		$traceMessage .= 'Trace:' . PHP_EOL . $ex->getTraceAsString();
		logMessage($message . PHP_EOL . $traceMessage);

		if ($this->reportErrors) {
			$error                    = array();
			$error["Type"]            = "Exception";
			$error["Message"]         = $message;
			$error["Backtrace"]       = $traceMessage;
			$error['OperatingSystem'] = php_uname();
			$error['PHPVersion']      = phpversion();

			if ($this->maniaControl->server) {
				$error['ServerLogin'] = $this->maniaControl->server->login;
			} else {
				$error['ServerLogin'] = '';
			}

			if ($this->maniaControl->settingManager && $this->maniaControl->updateManager) {
				$error['UpdateChannel']       = $this->maniaControl->settingManager->getSetting($this->maniaControl->updateManager, UpdateManager::SETTING_UPDATECHECK_CHANNEL);
				$error['ManiaControlVersion'] = $this->maniaControl->updateManager->getNightlyBuildDate();
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
		}

		if ($shutdown) {
			if ($this->shouldRestart()) {
				$this->maniaControl->restart();
			}
			exit();
		}
	}

	/**
	 * Test if ManiaControl should restart automatically
	 *
	 * @return bool
	 */
	private function shouldRestart() {
		if (!$this->maniaControl || !$this->maniaControl->settingManager) {
			return false;
		}
		$setting = $this->maniaControl->settingManager->getSetting($this, self::SETTING_RESTART_ON_EXCEPTION, true);
		return $setting;
	}

	/**
	 * Triggers a Debug Notice to the ManiaControl Website
	 *
	 * @param $message
	 */
	public function triggerDebugNotice($message) {
		$this->errorHandler(self::MC_DEBUG_NOTICE, $message);
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
	public function errorHandler($errorNumber, $errorString, $errorFile = null, $errorLine = -1) {
		if (error_reporting() === 0) {
			// Error suppressed
			return false;
		}

		$userError = $this->isUserErrorNumber($errorNumber);

		// Log error
		$errorTag     = $this->getErrorTag($errorNumber);
		$message      = $errorTag . ': ' . $errorString;
		$fileLine     = $errorFile . ': ' . $errorLine;
		$traceMessage = $this->parseBackTrace(debug_backtrace());
		$logMessage   = $message . PHP_EOL . ($userError ? $fileLine : $traceMessage);
		logMessage($logMessage);

		if ($this->reportErrors && !$userError) {
			$error                    = array();
			$error["Type"]            = "Error";
			$error["Message"]         = $message;
			$error["FileLine"]        = $fileLine;
			$error["Backtrace"]       = $traceMessage;
			$error['OperatingSystem'] = php_uname();
			$error['PHPVersion']      = phpversion();

			if ($this->maniaControl->server) {
				$error['ServerLogin'] = $this->maniaControl->server->login;
			} else {
				$error['ServerLogin'] = '';
			}

			if ($this->maniaControl->settingManager && $this->maniaControl->updateManager) {
				$error['UpdateChannel']       = $this->maniaControl->settingManager->getSetting($this->maniaControl->updateManager, UpdateManager::SETTING_UPDATECHECK_CHANNEL);
				$error['ManiaControlVersion'] = ManiaControl::VERSION . '#' . $this->maniaControl->updateManager->getNightlyBuildDate();
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
		if ($this->shouldStopExecution($errorNumber)) {
			logMessage('Stopping execution...');
			exit();
		}
		return false;
	}

	/**
	 * Check if the given Error Number is a User Error
	 *
	 * @param int $errorNumber
	 * @return bool
	 */
	private function isUserErrorNumber($errorNumber) {
		return ($errorNumber === E_USER_ERROR || $errorNumber === E_USER_WARNING || $errorNumber === E_USER_NOTICE || $errorNumber === E_USER_DEPRECATED);
	}

	/**
	 * Get the Prefix for the given Error Level
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
		if ($errorLevel == self::MC_DEBUG_NOTICE) {
			return '[ManiaControl DEBUG]';
		}
		return "[PHP {$errorLevel}]";
	}

	/**
	 * Parse the Debug Backtrace into a String for the Error Report
	 * return string
	 */
	private function parseBackTrace(array $backtrace) {
		$traceString = 'Trace:';
		$stepCount   = 0;
		foreach ($backtrace as $traceStep) {
			$traceString .= PHP_EOL . '#' . $stepCount . ': ';
			if (isset($traceStep['class'])) {
				$traceString .= $traceStep['class'];
			}
			if (isset($traceStep['type'])) {
				$traceString .= $traceStep['type'];
			}
			if (isset($traceStep['function'])) {
				$traceString .= $traceStep['function'];
			}
			if (isset($traceStep['file'])) {
				$traceString .= ' in File ';
				$traceString .= $traceStep['file'];
			}
			if (isset($traceStep['line'])) {
				$traceString .= ' on Line ';
				$traceString .= $traceStep['line'];
			}
			$stepCount++;
		}
		return $traceString;
	}

	/**
	 * Test if ManiaControl should stop its Execution
	 *
	 * @param int $errorNumber
	 * @return bool
	 */
	private function shouldStopExecution($errorNumber) {
		return ($errorNumber === E_ERROR || $errorNumber === E_USER_ERROR || $errorNumber === E_FATAL);
	}
} 