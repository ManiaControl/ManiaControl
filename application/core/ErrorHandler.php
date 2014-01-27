<?php

/**
 * Error and Exception Manager Class
 *
 * @author steeffeen & kremsy
 */
class ErrorHandler {
	/**
	 * Construct Error Handler
	 */
	public function __construct() {
		set_error_handler(array(&$this, 'errorHandler'), -1);
		set_exception_handler(array(&$this, 'exceptionHandler'));
	}

	/**
	 * ManiaControl ExceptionHandler
	 *
	 * @param Exception $ex
	 */
	public function exceptionHandler(Exception $ex) {
		$message = "[ManiaControl EXCEPTION]: {$ex->getMessage()} Trace: {$ex->getTraceAsString()}!";
		logMessage($message);
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