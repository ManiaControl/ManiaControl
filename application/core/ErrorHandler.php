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
	 * Constants
	 */
	const MC_DEBUG_NOTICE = "ManiaControl.DebugNotice";
	
	/**
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
	 * ManiaControl ExceptionHandler
	 * ManiaControl Shuts down after exception
	 *
	 * @param \Exception $ex
	 */
	public function exceptionHandler(\Exception $ex) {
		// Log exception
		$message = "[ManiaControl EXCEPTION]: {$ex->getMessage()}";
		$traceMessage = 'Class: ' . get_class($ex) . PHP_EOL;
		$traceMessage .= 'Trace:' . PHP_EOL . $ex->getTraceAsString();
		logMessage($message . PHP_EOL . $traceMessage);
		
		if ($this->reportErrors) {
			$error = array();
			$error["Type"] = "Exception";
			$error["Message"] = $message;
			$error["Backtrace"] = $traceMessage;
			$error['OperatingSystem'] = php_uname();
			$error['PHPVersion'] = phpversion();
			
			if ($this->maniaControl->server) {
				$error['ServerLogin'] = $this->maniaControl->server->login;
			}
			else {
				$error['ServerLogin'] = '';
			}
			
			if ($this->maniaControl->settingManager && $this->maniaControl->updateManager) {
				$error['UpdateChannel'] = $this->maniaControl->settingManager->getSetting($this->maniaControl->updateManager, 
						UpdateManager::SETTING_UPDATECHECK_CHANNEL);
				$error['ManiaControlVersion'] = $this->maniaControl->updateManager->getCurrentBuildDate();
			}
			else {
				$error['UpdateChannel'] = '';
				$error['ManiaControlVersion'] = ManiaControl::VERSION;
			}
			
			$json = json_encode($error);
			$info = base64_encode($json);
			
			$url = ManiaControl::URL_WEBSERVICE . "errorreport?error=" . urlencode($info);
			$success = FileUtil::loadFile($url);
			
			if (!json_decode($success)) {
				logMessage("Exception-Report failed!");
			}
			else {
				logMessage("Exception successfully reported!");
			}
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
	public function errorHandler($errorNumber, $errorString, $errorFile = null, $errorLine = -1) {
		if (error_reporting() == 0) {
			// Error suppressed
			return false;
		}
		
		// Log error
		$errorTag = $this->getErrorTag($errorNumber);
		$message = $errorTag . ': ' . $errorString;
		$traceMessage = $this->parseBackTrace(debug_backtrace());
		logMessage($message . PHP_EOL . $traceMessage);
		
		if ($this->reportErrors && $errorNumber != E_USER_ERROR && $errorNumber != E_USER_WARNING && $errorNumber != E_USER_NOTICE) {
			$error = array();
			$error["Type"] = "Error";
			$error["Message"] = $message;
			$error["Backtrace"] = $traceMessage;
			$error['OperatingSystem'] = php_uname();
			$error['PHPVersion'] = phpversion();
			
			if ($this->maniaControl->server) {
				$error['ServerLogin'] = $this->maniaControl->server->login;
			}
			else {
				$error['ServerLogin'] = '';
			}
			
			if ($this->maniaControl->settingManager && $this->maniaControl->updateManager) {
				$error['UpdateChannel'] = $this->maniaControl->settingManager->getSetting($this->maniaControl->updateManager, 
						UpdateManager::SETTING_UPDATECHECK_CHANNEL);
				$error['ManiaControlVersion'] = $this->maniaControl->updateManager->getCurrentBuildDate();
			}
			else {
				$error['UpdateChannel'] = '';
				$error['ManiaControlVersion'] = ManiaControl::VERSION;
			}
			
			$json = json_encode($error);
			$info = base64_encode($json);
			
			$url = ManiaControl::URL_WEBSERVICE . "errorreport?error=" . urlencode($info);
			$success = FileUtil::loadFile($url);
			
			if (!json_decode($success)) {
				logMessage("Error-Report failed!");
			}
			else {
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
	 * Triggers a Debug Notice to the ManiaControl Website
	 *
	 * @param $message
	 */
	public function triggerDebugNotice($message) {
		$this->errorHandler(self::MC_DEBUG_NOTICE, $message);
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
		if ($errorLevel == self::MC_DEBUG_NOTICE) {
			return '[ManiaControl DEBUG]';
		}
		return "[PHP {$errorLevel}]";
	}

	/**
	 * Parse the Debug Backtrace into a String for the Error Report
	 *
	 * return string
	 */
	private function parseBackTrace(array $backtrace) {
		$traceString = 'Trace:';
		$stepCount = 0;
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
} 