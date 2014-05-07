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
	const MC_DEBUG_NOTICE              = 'ManiaControl.DebugNotice';
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
		set_error_handler(array(&$this, 'handleError'), -1);
		set_exception_handler(array(&$this, 'handleException'));
	}

	/**
	 * Initialize other Error Handler Features
	 */
	public function init() {
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_RESTART_ON_EXCEPTION, true);
	}

	/**
	 * ManiaControl Exception Handler
	 *
	 * @param \Exception $ex
	 * @param bool       $shutdown
	 */
	public function handleException(\Exception $ex, $shutdown = true) {
		$message = "[ManiaControl EXCEPTION]: {$ex->getMessage()}";

		$exceptionClass = get_class($ex);
		$traceString    = $ex->getTraceAsString();

		$logMessage = $message . PHP_EOL . 'Class: ' . $exceptionClass . PHP_EOL . 'Trace:' . PHP_EOL . $traceString;
		logMessage($logMessage);

		if ($this->reportErrors) {
			$error                    = array();
			$error['Type']            = 'Exception';
			$error['Message']         = $message;
			$error['Class']           = $exceptionClass;
			$error['FileLine']        = $ex->getFile() . ': ' . $ex->getLine();
			$error['Backtrace']       = $traceString;
			$error['OperatingSystem'] = php_uname();
			$error['PHPVersion']      = phpversion();

			if ($this->maniaControl->server) {
				$error['ServerLogin'] = $this->maniaControl->server->login;
			}

			if ($this->maniaControl->settingManager && $this->maniaControl->updateManager) {
				$error['UpdateChannel']       = $this->maniaControl->settingManager->getSetting($this->maniaControl->updateManager, UpdateManager::SETTING_UPDATECHECK_CHANNEL);
				$error['ManiaControlVersion'] = ManiaControl::VERSION . ' #' . $this->maniaControl->updateManager->getNightlyBuildDate();
			} else {
				$error['ManiaControlVersion'] = ManiaControl::VERSION;
			}

			$json = json_encode($error);
			$info = base64_encode($json);

			$url     = ManiaControl::URL_WEBSERVICE . 'errorreport?error=' . urlencode($info);
			$success = FileUtil::loadFile($url);

			if (!json_decode($success)) {
				logMessage('Exception-Report failed!');
			} else {
				logMessage('Exception successfully reported!');
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
	 * Trigger a Debug Notice to the ManiaControl Website
	 *
	 * @param string $message
	 */
	public function triggerDebugNotice($message) {
		$this->handleError(self::MC_DEBUG_NOTICE, $message);
	}

	/**
	 * ManiaControl Error Handler
	 *
	 * @param int    $errorNumber
	 * @param string $errorString
	 * @param string $errorFile
	 * @param int    $errorLine
	 * @param array  $errorContext
	 * @return bool
	 */
	public function handleError($errorNumber, $errorString, $errorFile = null, $errorLine = -1, array $errorContext = array()) {
		if (error_reporting() === 0) {
			return false;
		}

		$errorTag = $this->getErrorTag($errorNumber);

		$message     = $errorTag . ': ' . $errorString;
		$fileLine    = $errorFile . ': ' . $errorLine;
		$traceString = $this->parseBackTrace(debug_backtrace());

		$logMessage = $message . PHP_EOL . 'File&Line: ' . $fileLine . PHP_EOL . 'Trace: ' . $traceString;
		logMessage($logMessage);

		if ($this->reportErrors && !$this->isUserErrorNumber($errorNumber)) {
			$error                    = array();
			$error['Type']            = 'Error';
			$error['Message']         = $message;
			$error['FileLine']        = $fileLine;
			$error['Backtrace']       = $traceString;
			$error['OperatingSystem'] = php_uname();
			$error['PHPVersion']      = phpversion();

			if ($this->maniaControl->server) {
				$error['ServerLogin'] = $this->maniaControl->server->login;
			}

			if ($this->maniaControl->settingManager && $this->maniaControl->updateManager) {
				$error['UpdateChannel']       = $this->maniaControl->settingManager->getSetting($this->maniaControl->updateManager, UpdateManager::SETTING_UPDATECHECK_CHANNEL);
				$error['ManiaControlVersion'] = ManiaControl::VERSION . ' ' . $this->maniaControl->updateManager->getNightlyBuildDate();
			} else {
				$error['ManiaControlVersion'] = ManiaControl::VERSION;
			}

			$json = json_encode($error);
			$info = base64_encode($json);

			$url     = ManiaControl::URL_WEBSERVICE . 'errorreport?error=' . urlencode($info);
			$success = FileUtil::loadFile($url);
			$success = json_decode($success);
			if ($success) {
				logMessage('Error successfully reported!');
			} else {
				logMessage('Error-Report failed!');
			}
		}
		if ($this->shouldStopExecution($errorNumber)) {
			logMessage('Stopping Execution...');
			exit();
		}
		return false;
	}

	/**
	 * Get the Prefix for the given Error Level
	 *
	 * @param int $errorLevel
	 * @return string
	 */
	public function getErrorTag($errorLevel) {
		switch ($errorLevel) {
			case E_NOTICE:
				return '[PHP NOTICE]';
			case E_WARNING:
				return '[PHP WARNING]';
			case E_ERROR:
				return '[PHP ERROR]';
			case E_CORE_ERROR:
				return '[PHP CORE ERROR]';
			case E_COMPILE_ERROR:
				return '[PHP COMPILE ERROR]';
			case E_RECOVERABLE_ERROR:
				return '[PHP RECOVERABLE ERROR]';
			case E_USER_NOTICE:
				return '[ManiaControl NOTICE]';
			case E_USER_WARNING:
				return '[ManiaControl WARNING]';
			case E_USER_ERROR:
				return '[ManiaControl ERROR]';
			case self::MC_DEBUG_NOTICE:
				return '[ManiaControl DEBUG]';
		}
		return "[PHP ERROR '{$errorLevel}']";
	}

	/**
	 * Parse the Debug Backtrace into a String for the Error Report
	 *
	 * @return string
	 */
	private function parseBackTrace(array $backtrace) {
		$traceString = '';
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
	 * Check if the given Error Number is a User Error
	 *
	 * @param int $errorNumber
	 * @return bool
	 */
	private function isUserErrorNumber($errorNumber) {
		return ($errorNumber === E_USER_ERROR || $errorNumber === E_USER_WARNING || $errorNumber === E_USER_NOTICE || $errorNumber === E_USER_DEPRECATED);
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

	/**
	 * Check if the Shutdown was caused by a Fatal Error and report it
	 */
	public function handleShutdown() {
		$error = error_get_last();
		if ($error && ($error['type'] & E_FATAL)) {
			$this->handleError($error['type'], $error['message'], $error['file'], $error['line']);
		}
	}
} 