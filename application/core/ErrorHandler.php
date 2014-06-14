<?php

namespace ManiaControl;

use ManiaControl\Callbacks\Callbacks;
use ManiaControl\Files\FileUtil;
use ManiaControl\Plugins\PluginManager;
use ManiaControl\Update\UpdateManager;
use Maniaplanet\DedicatedServer\Xmlrpc\TransportException;

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
	private $handlingError = null;

	/**
	 * Construct Error Handler
	 *
	 * @param ManiaControl @maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		set_error_handler(array(&$this, 'handleError'), -1);
		set_exception_handler(array(&$this, 'handleException'));
		register_shutdown_function(array(&$this, 'handleShutdown'));
	}

	/**
	 * Initialize other Error Handler Features
	 */
	public function init() {
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_RESTART_ON_EXCEPTION, true);
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
	 * @param bool   $onShutdown
	 * @return bool
	 */
	public function handleError($errorNumber, $errorString, $errorFile = null, $errorLine = -1, array $errorContext = array(), $onShutdown = false) {
		$suppressed = (error_reporting() === 0);
		if ($suppressed && !self::LOG_SUPPRESSED_ERRORS) {
			return false;
		}

		if (!$this->handlingError) {
			// Reset error handler for safety
			$this->handlingError = true;
			set_error_handler(array(&$this, 'handleError'), -1);
		}

		// Build log message
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
		if (!$userError && !$onShutdown) {
			$logMessage .= PHP_EOL . 'Trace: ' . PHP_EOL . $traceString;
		}
		$this->maniaControl->log($logMessage);

		if (!DEV_MODE && !$userError && !$suppressed) {
			// Report error
			$report                = array();
			$report['Type']        = 'Error';
			$report['Message']     = $message;
			$report['FileLine']    = $fileLine;
			$report['SourceClass'] = $sourceClass;
			$report['PluginId']    = PluginManager::getPluginId($sourceClass);
			if (!$onShutdown) {
				$report['Backtrace'] = $traceString;
			}
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
				logMessage('Error-Report successful!');
			} else {
				logMessage('Error-Report failed! ' . print_r($response, true));
			}
		}

		if ($this->isFatalError($errorNumber)) {
			$this->maniaControl->quit('Quitting ManiaControl after Fatal Error.');
		}

		// Disable safety state
		$this->handlingError = false;

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
			$traceString .= '#' . $stepCount . ': ';
			if (isset($traceStep['class'])) {
				if (!$sourceClass && strpos($traceStep['class'], '\\FaultException') === false) {
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
			$traceString .= PHP_EOL;
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
				$string .= $type . '(';
				if (is_string($arg)) {
					$param = iconv('UTF-8', 'UTF-8//IGNORE', substr($arg, 0, 20));
					$string .= print_r($param, true);
				} else {
					$string .= print_r($arg, true);
				}
				$string .= ')';
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
	 * Test whether the given Error Number represents a Fatal Error
	 *
	 * @param int $errorNumber
	 * @return bool
	 */
	private function isFatalError($errorNumber) {
		return ($errorNumber & E_FATAL);
	}

	/**
	 * Handle PHP Process Shutdown
	 */
	public function handleShutdown() {
		// TODO: skip client-related actions on transport exception (e.g. server down)

		if ($this->maniaControl->callbackManager) {
			// OnShutdown callback
			$this->maniaControl->callbackManager->triggerCallback(Callbacks::ONSHUTDOWN);
		}

		if ($this->maniaControl->chat) {
			// Announce quit
			$this->maniaControl->chat->sendInformation('ManiaControl shutting down.');
		}

		if ($this->maniaControl->client) {
			try {
				$this->maniaControl->client->sendHideManialinkPage();
			} catch (TransportException $e) {
				$this->handleException($e, false);
			}
		}

		// Check if the Shutdown was caused by a Fatal Error and report it
		$error = error_get_last();
		if ($error && ($error['type'] & E_FATAL)) {
			$this->handleError($error['type'], $error['message'], $error['file'], $error['line'], array(), true);
		}

		$this->maniaControl->quit('Quitting ManiaControl!');
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
			$report['Message']         = $message;
			$report['Class']           = $exceptionClass;
			$report['FileLine']        = $exception->getFile() . ': ' . $exception->getLine();
			$report['SourceClass']     = $sourceClass;
			$report['PluginId']        = PluginManager::getPluginId($sourceClass);
			$report['Backtrace']       = $traceString;
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
			$this->maniaControl->quit('Quitting ManiaControl after Exception.');
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
		$setting = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_RESTART_ON_EXCEPTION, true);
		return $setting;
	}
} 