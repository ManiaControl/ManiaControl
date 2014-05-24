<?php

namespace ManiaControl;

use ManiaControl\Files\FileUtil;
use ManiaControl\Plugins\PluginManager;
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
	const LOG_SUPPRESSED_ERRORS        = false;

	/*
	 * Private Properties
	 */
	private $maniaControl = null;

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
	 * @param \Exception $exception
	 * @param bool       $shutdown
	 */
	public function handleException(\Exception $exception, $shutdown = true) {
		$message = "[ManiaControl EXCEPTION]: {$exception->getMessage()}";

		$exceptionClass = get_class($exception);
		$sourceClass    = null;
		$traceString    = $this->parseBackTrace($exception->getTrace(), $sourceClass);

		$logMessage = $message . PHP_EOL . 'Class: ' . $exceptionClass . PHP_EOL . 'Trace:' . PHP_EOL . $traceString;
		$this->maniaControl->log($logMessage);

		if (!DEV_MODE) {
			$report                    = array();
			$report['Type']            = 'Exception';
			$report['Message']         = iconv('UTF-8', 'UTF-8//TRANSLIT', $message);
			$report['Class']           = $exceptionClass;
			$report['FileLine']        = $exception->getFile() . ': ' . $exception->getLine();
			$report['SourceClass']     = $sourceClass;
			$report['PluginId']        = PluginManager::getPluginId($sourceClass);
			$report['Backtrace']       = iconv('UTF-8', 'UTF-8//TRANSLIT', $traceString);
			$report['OperatingSystem'] = php_uname();
			$report['PHPVersion']      = phpversion();

			if ($this->maniaControl->server) {
				$report['ServerLogin'] = $this->maniaControl->server->login;
			}

			if ($this->maniaControl->settingManager && $this->maniaControl->updateManager) {
				$report['UpdateChannel']       = $this->maniaControl->settingManager->getSettingValue($this->maniaControl->updateManager, UpdateManager::SETTING_UPDATECHECK_CHANNEL);
				$report['ManiaControlVersion'] = ManiaControl::VERSION . ' #' . $this->maniaControl->updateManager->getNightlyBuildDate();
			} else {
				$report['ManiaControlVersion'] = ManiaControl::VERSION;
			}

			$json = json_encode($report);
			$info = base64_encode($json);

			$url      = ManiaControl::URL_WEBSERVICE . 'errorreport?error=' . urlencode($info);
			$response = FileUtil::loadFile($url);
			$success  = json_decode($response);
			if ($success) {
				logMessage('Exception successfully reported!');
			} else {
				logMessage('Exception-Report failed! ' . print_r($response, true));
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
	 * Parse the Debug Backtrace into a String for the Error Report
	 *
	 * @param array  $backtrace
	 * @param string $sourceClass
	 * @return string
	 */
	private function parseBackTrace(array $backtrace, &$sourceClass = null) {
		$traceString = '';
		$stepCount   = 0;
		foreach ($backtrace as $traceStep) {
			$traceString .= PHP_EOL . '#' . $stepCount . ': ';
			if (isset($traceStep['class'])) {
				if (!$sourceClass) {
					$sourceClass = $traceStep['class'];
				}
				$traceString .= $traceStep['class'];
			}
			if (isset($traceStep['type'])) {
				$traceString .= $traceStep['type'];
			}
			if (isset($traceStep['function'])) {
				$traceString .= $traceStep['function'] . '(';
				if (isset($traceStep['args'])) {
					$traceString .= $this->parseArgumentsArray($traceStep['args']);
				}
				$traceString .= ')';
			}
			if (isset($traceStep['file'])) {
				$traceString .= ' in File ';
				$traceString .= $traceStep['file'];
			}
			if (isset($traceStep['line'])) {
				$traceString .= ' on Line ';
				$traceString .= $traceStep['line'];
			}
			if (strlen($traceString) > 1300) {
				// Too long...
				$traceString .= '...';
				break;
			}
			$stepCount++;
		}
		return $traceString;
	}

	/**
	 * Build a String from an Arguments Array
	 *
	 * @param array $args
	 * @return string
	 */
	private function parseArgumentsArray(array $args) {
		$string    = '';
		$argsCount = count($args);
		foreach ($args as $index => $arg) {
			if (is_object($arg)) {
				$string .= 'object(' . get_class($arg) . ')';
			} else if (is_array($arg)) {
				$string .= 'array(' . $this->parseArgumentsArray($arg) . ')';
			} else {
				$type = gettype($arg);
				$string .= $type . '(' . print_r($arg, true) . ')';
			}
			if ($index < $argsCount - 1) {
				$string .= ', ';
			}
			if (strlen($string) > 100) {
				// Too long...
				$string .= '...';
				break;
			}
		}
		return $string;
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
		$setting = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_RESTART_ON_EXCEPTION, true);
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
		$suppressed = (error_reporting() === 0);
		if ($suppressed && !self::LOG_SUPPRESSED_ERRORS) {
			return false;
		}

		$errorTag         = $this->getErrorTag($errorNumber);
		$userError        = $this->isUserErrorNumber($errorNumber);
		$traceSourceClass = null;

		$message     = $errorTag . ': ' . $errorString;
		$fileLine    = $errorFile . ': ' . $errorLine;
		$traceString = $this->parseBackTrace(array_slice(debug_backtrace(), 1), $traceSourceClass);
		$sourceClass = $this->getSourceClass($errorFile);
		if (!$sourceClass) {
			$sourceClass = $traceSourceClass;
		}

		$logMessage = $message . PHP_EOL . 'File&Line: ' . $fileLine;
		if (!$userError) {
			$logMessage .= PHP_EOL . 'Trace: ' . $traceString;
		}
		$this->maniaControl->log($logMessage);

		if (!DEV_MODE && !$userError && !$suppressed) {
			$report                    = array();
			$report['Type']            = 'Error';
			$report['Message']         = iconv('UTF-8', 'UTF-8//TRANSLIT', $message);
			$report['FileLine']        = $fileLine;
			$report['SourceClass']     = $sourceClass;
			$report['PluginId']        = PluginManager::getPluginId($sourceClass);
			$report['Backtrace']       = iconv('UTF-8', 'UTF-8//TRANSLIT', $traceString);
			$report['OperatingSystem'] = php_uname();
			$report['PHPVersion']      = phpversion();

			if ($this->maniaControl->server) {
				$report['ServerLogin'] = $this->maniaControl->server->login;
			}

			if ($this->maniaControl->settingManager && $this->maniaControl->updateManager) {
				$report['UpdateChannel']       = $this->maniaControl->settingManager->getSettingValue($this->maniaControl->updateManager, UpdateManager::SETTING_UPDATECHECK_CHANNEL);
				$report['ManiaControlVersion'] = ManiaControl::VERSION . ' ' . $this->maniaControl->updateManager->getNightlyBuildDate();
			} else {
				$report['ManiaControlVersion'] = ManiaControl::VERSION;
			}

			$json = json_encode($report);
			$info = base64_encode($json);

			$url      = ManiaControl::URL_WEBSERVICE . 'errorreport?error=' . urlencode($info);
			$response = FileUtil::loadFile($url);
			$success  = json_decode($response);
			if ($success) {
				logMessage('Error successfully reported!');
			} else {
				logMessage('Error-Report failed! ' . print_r($response, true));
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
	 * Check if the given Error Number is a User Error
	 *
	 * @param int $errorNumber
	 * @return bool
	 */
	private function isUserErrorNumber($errorNumber) {
		return ($errorNumber & E_USER_ERROR || $errorNumber & E_USER_WARNING || $errorNumber & E_USER_NOTICE || $errorNumber & E_USER_DEPRECATED);
	}

	/**
	 * Get the Source Class via the Error File
	 *
	 * @param string $errorFile
	 * @return string
	 */
	private function getSourceClass($errorFile) {
		if (!$errorFile) {
			return null;
		}
		$filePath  = substr($errorFile, strlen(ManiaControlDir));
		$filePath  = str_replace('plugins' . DIRECTORY_SEPARATOR, '', $filePath);
		$filePath  = str_replace('core' . DIRECTORY_SEPARATOR, 'ManiaControl\\', $filePath);
		$className = str_replace('.php', '', $filePath);
		$className = str_replace(DIRECTORY_SEPARATOR, '\\', $className);
		if (!class_exists($className, false)) {
			return null;
		}
		return $className;
	}

	/**
	 * Test if ManiaControl should stop its Execution
	 *
	 * @param int $errorNumber
	 * @return bool
	 */
	private function shouldStopExecution($errorNumber) {
		return ($errorNumber & E_FATAL);
	}

	/**
	 * Check if the Shutdown was caused by a Fatal Error and report it
	 */
	public function handleShutdown() {
		$error = error_get_last();
		if ($error) {
			$this->handleError($error['type'], $error['message'], $error['file'], $error['line']);
		}
	}
} 