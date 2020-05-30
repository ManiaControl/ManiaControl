<?php

namespace ManiaControl;

use ManiaControl\Files\AsyncHttpRequest;
use ManiaControl\Plugins\PluginManager;
use ManiaControl\Update\UpdateManager;
use ManiaControl\Utils\Formatter;
use ManiaControl\Utils\WebReader;
use Maniaplanet\DedicatedServer\Xmlrpc\TransportException;

/**
 * Error and Exception Manager Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ErrorHandler {
	/*
	 * Constants
	 */
	const MC_DEBUG_NOTICE              = 'ManiaControl.DebugNotice';
	const SETTING_RESTART_ON_EXCEPTION = 'Automatically restart on Exceptions';
	const LOG_SUPPRESSED_ERRORS        = false;
	const LONG_LOOP_REPORT_TIME        = 5;

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl  = null;
	private $handlingError = null;

	/**
	 * Construct a new error handler instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		set_error_handler(array(&$this, 'handleError')); //TODO before was -1 verify why
		set_exception_handler(array(&$this, 'handleException'));
		register_shutdown_function(array(&$this, 'handleShutdown'));
	}

	/**
	 * Initialize error handler features
	 */
	public function init() {
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_RESTART_ON_EXCEPTION, true);
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
			set_error_handler(array(&$this, 'handleError'));
		}

		// Build log message
		$errorTag     = $this->getErrorTag($errorNumber);
		$isUserError  = self::isUserErrorNumber($errorNumber);
		$isFatalError = self::isFatalError($errorNumber);

		$isPluginError = false;

		$traceString      = null;
		$sourceClass      = null;
		$traceSourceClass = null;
		$fileLine         = null;

		$message = $errorTag . ': ' . $errorString;
		if (!$onShutdown) {
			$traceString = $this->parseBackTrace(array_slice(debug_backtrace(), 1), $traceSourceClass);
		}
		if ($errorFile) {
			$fileLine    = $errorFile . ': ' . $errorLine;
			$sourceClass = $this->getSourceClass($errorFile);
		}
		if (!$sourceClass && $traceSourceClass) {
			$sourceClass = $traceSourceClass;
		}

		$logMessage = $message . PHP_EOL . 'File&Line: ' . $fileLine;
		if (!$isUserError && $traceString) {
			$logMessage .= PHP_EOL . 'Trace: ' . PHP_EOL . $traceString;
		}
		Logger::log($logMessage);

		if (!DEV_MODE && !$isUserError && !$suppressed) {
			// Report error
			$report            = array();
			$report['Type']    = 'Error';
			$report['Message'] = $message;
			if ($fileLine) {
				$report['FileLine'] = self::stripBaseDir($fileLine);
			}

			if ($sourceClass) {
				$report['SourceClass'] = $sourceClass;
				$pluginId              = PluginManager::getPluginId($sourceClass);
				if ($pluginId > 0) {
					$report['PluginId'] = $pluginId;
					$report['PluginVersion'] = PluginManager::getPluginVersion($sourceClass);

					if ($isFatalError) {
						$this->maniaControl->getPluginManager()->deactivatePlugin($sourceClass);
						$message = $this->maniaControl->getChat()->formatMessage(
							'Plugin %s has an Error -> The Plugin will be deactivated and ManiaControl restarted!',
							$sourceClass
						);
						$this->maniaControl->getChat()->sendError($message);
						Logger::logError("Plugin {$sourceClass} has an Error -> The Plugin will be deactivated and ManiaControl restarted!");
						$isPluginError = true;
					}
				}
			}

			if ($traceString) {
				$report['Backtrace'] = $traceString;
			}

			$report['OperatingSystem'] = php_uname();
			$report['PHPVersion']      = phpversion();

			if ($this->maniaControl->getServer()) {
				$report['ServerLogin'] = $this->maniaControl->getServer()->login;
			}

			if ($this->maniaControl->getSettingManager() && $this->maniaControl->getUpdateManager()) {
				$report['UpdateChannel']       = $this->maniaControl->getSettingManager()->getSettingValue($this->maniaControl->getUpdateManager(), UpdateManager::SETTING_UPDATECHECK_CHANNEL);
				$report['ManiaControlVersion'] = ManiaControl::VERSION . ' ' . $this->maniaControl->getUpdateManager()->getBuildDate();
			} else {
				$report['ManiaControlVersion'] = ManiaControl::VERSION;
			}

			$report['DedicatedBuildVersion'] = $this->maniaControl->getDedicatedServerBuildVersion();

			$json = json_encode(Formatter::utf8($report));
			$info = base64_encode($json);

			$url = ManiaControl::URL_WEBSERVICE . 'errorreport?error=' . urlencode($info);

			if ($isFatalError) {
				$response = WebReader::getUrl($url);
				$content  = $response->getContent();
				$success  = json_decode($content);
				if ($success) {
					Logger::log('Error-Report successful!');
				} else {
					Logger::log('Error-Report failed! ' . print_r($content, true));
				}
			} else {
				//Async Report
				$asyncHttpRequest = new AsyncHttpRequest($this->maniaControl, $url);
				$asyncHttpRequest->setContentType(AsyncHttpRequest::CONTENT_TYPE_JSON);
				$asyncHttpRequest->setCallable(function ($json, $error) {
					if ($error) {
						Logger::logError("Error while Sending Error Report");
						return;
					}
					$success = json_decode($json);
					if ($success) {
						Logger::log('Error-Report successful!');
					} else {
						Logger::log('Error-Report failed! ' . print_r($json, true));
					}
				});

				$asyncHttpRequest->getData();
			}
		}

		if ($isFatalError) {
			if ($isPluginError) {
				$this->maniaControl->reboot();
			} else {
				$this->maniaControl->quit('Quitting ManiaControl after Fatal Error.');
			}
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
			case E_ERROR:
				return '[PHP ERROR]';
			case E_WARNING:
				return '[PHP WARNING]';
			case E_PARSE:
				return '[PHP PARSE ERROR]';
			case E_NOTICE:
				return '[PHP NOTICE]';
			case E_CORE_ERROR:
				return '[PHP CORE ERROR]';
			case E_COMPILE_ERROR:
				return '[PHP COMPILE ERROR]';
			case E_USER_ERROR:
				return '[ManiaControl ERROR]';
			case E_USER_WARNING:
				return '[ManiaControl WARNING]';
			case E_USER_NOTICE:
				return '[ManiaControl NOTICE]';
			case E_RECOVERABLE_ERROR:
				return '[PHP RECOVERABLE ERROR]';
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
	private static function isUserErrorNumber($errorNumber) {
		$userError = (E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE | E_USER_DEPRECATED);
		return is_int($errorNumber) && ($errorNumber & $userError);
	}

	/**
	 * Test whether the given Error Number represents a Fatal Error
	 *
	 * @param int $errorNumber
	 * @return bool
	 */
	public static function isFatalError($errorNumber) {
		$fatalError = (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR);
		return is_int($errorNumber) && ($errorNumber & $fatalError);
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
			$skipStep    = $this->shouldSkipTraceStep($traceStep);
			$traceString .= '#' . $stepCount . ': ';
			if (isset($traceStep['class'])) {
				if (!$sourceClass && !$skipStep && !$this->isIgnoredSourceClass($traceStep['class'])) {
					$sourceClass = $traceStep['class'];
				}
				$traceString .= $traceStep['class'];
			}
			if (isset($traceStep['type'])) {
				$traceString .= $traceStep['type'];
			}
			if (isset($traceStep['function'])) {
				$traceString .= $traceStep['function'] . '(';
				if (isset($traceStep['args']) && !$skipStep) {
					$traceString .= $this->parseArgumentsArray($traceStep['args']);
				}
				$traceString .= ')';
			}
			if (isset($traceStep['file']) && !$skipStep) {
				$traceString .= ' in File ';
				$traceString .= self::stripBaseDir($traceStep['file']);
			}
			if (isset($traceStep['line']) && !$skipStep) {
				$traceString .= ' on Line ';
				$traceString .= $traceStep['line'];
			}
			$traceString .= PHP_EOL;
			if (strlen($traceString) > 2500) {
				// Too long...
				$traceString .= '...';
				break;
			}
			$stepCount++;
		}
		return $traceString;
	}

	/**
	 * Check if the given Trace Step should be skipped
	 *
	 * @param array $traceStep
	 * @return bool
	 */
	private function shouldSkipTraceStep(array $traceStep) {
		if (isset($traceStep['class'])) {
			$skippedClasses = array('Symfony\\Component\\EventDispatcher\\EventDispatcher', 'cURL\\Request');
			foreach ($skippedClasses as $skippedClass) {
				if ($traceStep['class'] === $skippedClass) {
					return true;
				}
			}
		}
		if (isset($traceStep['file'])) {
			$skippedFiles = array('Symfony', 'curl-easy');
			foreach ($skippedFiles as $skippedFile) {
				if (strpos($traceStep['file'], $skippedFile)) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Check if the given Class Name should be ignored as possible Error Source Class
	 *
	 * @param string $class
	 * @return bool
	 */
	private function isIgnoredSourceClass($class) {
		$ignoredClasses = array('Maniaplanet\\', '\\ErrorHandler');
		foreach ($ignoredClasses as $ignoredClass) {
			if (strpos($class, $ignoredClass) !== false) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Build a string from an arguments array
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
				$type   = gettype($arg);
				$string .= $type . '(';
				if (is_string($arg)) {
					$param = $arg;
					if (strlen($param) > 40) {
						$param = substr($param, 0, 40) . '..';
					}
					$string .= print_r($param, true);
				} else {
					$string .= print_r($arg, true);
				}
				$string .= ')';
			}
			if ($index < $argsCount - 1) {
				$string .= ', ';
			}
			if (strlen($string) > 150) {
				// Too long...
				$string .= '...';
				break;
			}
		}
		return $string;
	}

	/**
	 * Strip the ManiaControl path from the given path to ensure privacy
	 *
	 * @param string $path
	 * @return string
	 */
	private static function stripBaseDir($path) {
		return str_replace(MANIACONTROL_PATH, '', $path);
	}

	/**
	 * Get the source class via the error file
	 *
	 * @param string $errorFile
	 * @return string
	 */
	private function getSourceClass($errorFile) {
		if (!$errorFile) {
			return null;
		}
		$filePath  = substr($errorFile, strlen(MANIACONTROL_PATH));
		$filePath  = str_replace('plugins' . DIRECTORY_SEPARATOR, '', $filePath);
		$filePath  = str_replace('core' . DIRECTORY_SEPARATOR, 'ManiaControl\\', $filePath);
		$className = str_replace('.php', '', $filePath);
		$className = str_replace(DIRECTORY_SEPARATOR, '\\', $className);


		if (!class_exists($className, false)) {
			//For Classes With different Folder Namespaces
			$splitNameSpace = explode('\\', $className);
			if (is_array($splitNameSpace)) {
				$className = end($splitNameSpace);
			}

			foreach (get_declared_classes() as $declared_class) {
				if (strpos($declared_class, $className) !== false) {
					return $declared_class;
				}
			}
			return null;
		}
		return $className;
	}

	/**
	 * Handle PHP Process Shutdown
	 */
	public function handleShutdown() {
		// Check if the Shutdown was caused by a Fatal Error and report it
		$error = error_get_last();
		if ($error && self::isFatalError($error['type'])) {
			$this->handleError($error['type'], $error['message'], $error['file'], $error['line'], array(), true);
		}

		$this->maniaControl->quit('Quitting ManiaControl!');
	}

	/**
	 * ManiaControl Exception Handler
	 *
	 * @param \Throwable $exception
	 * @param bool       $shutdown
	 */
	public function handleException($exception, $shutdown = true) {
		//Removed error type, as php throwed the exception in a case and it was not from class Exception weiredly
		$message = "[ManiaControl EXCEPTION]: {$exception->getMessage()}";

		$exceptionClass = get_class($exception);
		$sourceClass    = null;
		$traceString    = $this->parseBackTrace($exception->getTrace(), $sourceClass);

		$logMessage = $message . PHP_EOL . 'Class: ' . $exceptionClass . PHP_EOL . 'Trace:' . PHP_EOL . $traceString;
		Logger::log($logMessage);

		if (!DEV_MODE) {
			$report                    = array();
			$report['Type']            = 'Exception';
			$report['Message']         = $message;
			$report['Class']           = $exceptionClass;
			$report['FileLine']        = self::stripBaseDir($exception->getFile()) . ': ' . $exception->getLine();
			if ($sourceClass) {
				$report['SourceClass'] = $sourceClass;
				$pluginId              = PluginManager::getPluginId($sourceClass);
				if ($pluginId > 0) {
					$report['PluginId'] = $pluginId;
					$report['PluginVersion'] = PluginManager::getPluginVersion($sourceClass);

					$this->maniaControl->getPluginManager()->deactivatePlugin($sourceClass);
					$message = $this->maniaControl->getChat()->formatMessage(
						'Plugin %s has an Error -> The Plugin will be deactivated and ManiaControl restarted!',
						$sourceClass
					);
					$this->maniaControl->getChat()->sendError($message);
					Logger::logError("Plugin {$sourceClass} has an Error -> The Plugin will be deactivated and ManiaControl restarted!");
				}
			}

			$report['Backtrace']       = $traceString;
			$report['OperatingSystem'] = php_uname();
			$report['PHPVersion']      = phpversion();

			if ($server = $this->maniaControl->getServer()) {
				$report['ServerLogin'] = $server->login;
			}

			if (($settingManager = $this->maniaControl->getSettingManager()) && ($updateManager = $this->maniaControl->getUpdateManager())) {
				$report['UpdateChannel']       = $settingManager->getSettingValue($updateManager, $updateManager::SETTING_UPDATECHECK_CHANNEL);
				$report['ManiaControlVersion'] = ManiaControl::VERSION . ' #' . $updateManager->getBuildDate();
			} else {
				$report['ManiaControlVersion'] = ManiaControl::VERSION;
			}

			$report['DedicatedBuildVersion'] = $this->maniaControl->getDedicatedServerBuildVersion();

			$errorReport = json_encode(Formatter::utf8($report));

			$url      = ManiaControl::URL_WEBSERVICE . 'errorreport';
			$response = WebReader::postUrl($url, $errorReport);
			$content  = $response->getContent();
			$success  = json_decode($content);
			if ($success) {
				Logger::log('Exception successfully reported!');
			} else {
				Logger::log('Exception-Report failed! ' . print_r($content, true));
			}
		}

		if ($shutdown) {
			if ($this->shouldRestart()) {
				$this->maniaControl->reboot();
			}
			try {
				$this->maniaControl->quit('Quitting ManiaControl after Exception.');
			} catch (TransportException $e) {
			}
		}
	}

	/**
	 * Test if ManiaControl should restart automatically
	 *
	 * @return bool
	 */
	private function shouldRestart() {
		if (!$this->maniaControl || !$this->maniaControl->getSettingManager() || DEV_MODE) {
			return false;
		}
		$setting = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_RESTART_ON_EXCEPTION, true);
		return $setting;
	}
}
