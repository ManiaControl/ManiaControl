<?php

namespace ManiaControl;

use ManiaControl\Admin\ActionsMenu;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Bills\BillManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Callbacks\Callbacks;
use ManiaControl\Callbacks\EchoManager;
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\Callbacks\TimerManager;
use ManiaControl\Commands\CommandListener;
use ManiaControl\Commands\CommandManager;
use ManiaControl\Communication\CommunicationAnswer;
use ManiaControl\Communication\CommunicationListener;
use ManiaControl\Communication\CommunicationManager;
use ManiaControl\Communication\CommunicationMethods;
use ManiaControl\Configurator\Configurator;
use ManiaControl\Database\Database;
use ManiaControl\Files\AsynchronousFileReader;
use ManiaControl\Files\FileUtil;
use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Maps\MapManager;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;
use ManiaControl\Plugins\PluginManager;
use ManiaControl\Script\ModeScriptEventManager;
use ManiaControl\Server\Server;
use ManiaControl\Settings\SettingManager;
use ManiaControl\Statistics\StatisticManager;
use ManiaControl\Update\UpdateManager;
use ManiaControl\Utils\CommandLineHelper;
use ManiaControl\Utils\SystemUtil;
use Maniaplanet\DedicatedServer\Connection;
use Maniaplanet\DedicatedServer\Xmlrpc\AuthenticationException;
use Maniaplanet\DedicatedServer\Xmlrpc\TransportException;

/**
 * ManiaControl Server Controller for ManiaPlanet Server
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ManiaControl implements CallbackListener, CommandListener, TimerListener, CommunicationListener, UsageInformationAble {
	use UsageInformationTrait;

	/*
	 * Constants
	 */
	const VERSION                     = '0.201';
	const API_VERSION                 = '2013-04-16';
	const MIN_DEDIVERSION             = '2017-03-23_18_00';
	const SCRIPT_TIMEOUT              = 10;
	const URL_WEBSERVICE              = 'https://ws.maniacontrol.com/';
	const SETTING_PERMISSION_SHUTDOWN = 'Shutdown ManiaControl';
	const SETTING_PERMISSION_RESTART  = 'Restart ManiaControl';

	/*
	 * Public properties
	 */
	/** @var ActionsMenu $actionsMenu
	 * @deprecated
	 * @see getActionsMenu()
	 */
	public $actionsMenu = null;
	/** @var AuthenticationManager $authenticationManager
	 * @deprecated
	 * @see getAuthenticationManager()
	 */
	public $authenticationManager = null;
	/** @var CallbackManager $callbackManager
	 * @deprecated
	 * @see getCallbackManager()
	 */
	public $callbackManager = null;
	/** @var Chat $chat
	 * @deprecated
	 * @see getChat()
	 */
	public $chat = null;
	/** @var \SimpleXMLElement $config
	 * @deprecated
	 * @see getConfig()
	 */
	public $config = null;
	/** @var Configurator $configurator
	 * @deprecated
	 * @see getConfigurator()
	 */
	public $configurator = null;
	/** @var Connection $client
	 * @deprecated
	 * @see getClient()
	 */
	public $client = null;
	/** @var CommandManager $commandManager
	 * @deprecated
	 * @see getCommandManager()
	 */
	public $commandManager = null;
	/** @var Database $database
	 * @deprecated
	 * @see getDatabase()
	 */
	public $database = null;
	/** @var ManialinkManager $manialinkManager
	 * @deprecated
	 * @see getManialinkManager
	 */
	public $manialinkManager = null;
	/** @var MapManager $mapManager
	 * @deprecated
	 * @see getMapManager()
	 */
	public $mapManager = null;
	/** @var PlayerManager $playerManager
	 * @deprecated
	 * @see getPlayerManager()
	 */
	public $playerManager = null;
	/** @var PluginManager $pluginManager
	 * @deprecated
	 * @see getPluginManager()
	 */
	public $pluginManager = null;
	/** @var Server $server
	 * @deprecated
	 * @see getServer()
	 */
	public $server = null;
	/** @var SettingManager $settingManager
	 * @deprecated
	 * @see getSettingManager()
	 */
	public $settingManager = null;
	/** @var StatisticManager $statisticManager
	 * @deprecated
	 * @see getStatisticManager()
	 */
	public $statisticManager = null;
	/** @var UpdateManager $updateManager
	 * @deprecated
	 * @see getUpdateManager()
	 */
	public $updateManager = null;
	/** @var ErrorHandler $errorHandler
	 * @deprecated
	 * @see getErrorHandler()
	 */
	public $errorHandler = null;
	/** @var TimerManager $timerManager
	 * @deprecated
	 * @see getTimerManager()
	 */
	public $timerManager = null;
	/** @var AsynchronousFileReader $fileReader
	 * @deprecated
	 * @see getFileReader()
	 */
	public $fileReader = null;
	/** @var BillManager $billManager
	 * @deprecated
	 * @see getBillManager()
	 */
	public $billManager = null;

	/*
	 * Private properties
	 */
	private $requestQuitMessage = null;

	/** @var EchoManager $echoManager */
	private $echoManager = null;

	/** @var CommunicationManager $communicationManager */
	private $communicationManager = null;

	/** @var ModeScriptEventManager $modeScriptEventManager */
	private $modeScriptEventManager = null;

	private $dedicatedServerBuildVersion = "";

	/**
	 * Construct a new ManiaControl instance
	 */
	public function __construct() {
		Logger::log('Loading ManiaControl v' . self::VERSION . ' ...');

		$this->errorHandler = new ErrorHandler($this);

		$this->loadConfig();

		// Load ManiaControl Modules
		$this->callbackManager        = new CallbackManager($this);
		$this->modeScriptEventManager = new ModeScriptEventManager($this);
		$this->echoManager            = new EchoManager($this);
		$this->communicationManager   = new CommunicationManager($this);
		$this->timerManager           = new TimerManager($this);
		$this->database               = new Database($this);
		$this->fileReader             = new AsynchronousFileReader($this);
		$this->billManager            = new BillManager($this);
		$this->settingManager         = new SettingManager($this);
		$this->statisticManager       = new StatisticManager($this);
		$this->manialinkManager       = new ManialinkManager($this);
		$this->actionsMenu            = new ActionsMenu($this);
		$this->chat                   = new Chat($this);
		$this->commandManager         = new CommandManager($this);
		$this->server                 = new Server($this);
		$this->authenticationManager  = new AuthenticationManager($this);
		$this->playerManager          = new PlayerManager($this);
		$this->mapManager             = new MapManager($this);
		$this->configurator           = new Configurator($this);
		$this->pluginManager          = new PluginManager($this);
		$this->updateManager          = new UpdateManager($this);

		$this->getErrorHandler()->init();

		// Permissions
		$this->getAuthenticationManager()->definePermissionLevel(self::SETTING_PERMISSION_SHUTDOWN, AuthenticationManager::AUTH_LEVEL_SUPERADMIN);
		$this->getAuthenticationManager()->definePermissionLevel(self::SETTING_PERMISSION_RESTART, AuthenticationManager::AUTH_LEVEL_SUPERADMIN);

		// Commands
		$this->getCommandManager()->registerCommandListener('version', $this, 'commandVersion', false, 'Shows ManiaControl version.');
		$this->getCommandManager()->registerCommandListener('restart', $this, 'commandRestart', true, 'Restarts ManiaControl.');
		$this->getCommandManager()->registerCommandListener('shutdown', $this, 'commandShutdown', true, 'Shuts ManiaControl down.');

		// Check connection every 30 seconds
		$this->getTimerManager()->registerTimerListening($this, 'checkConnection', 1000 * 30);

		// Communication Methods
		$this->getCommunicationManager()->registerCommunicationListener(CommunicationMethods::RESTART_MANIA_CONTROL, $this, function ($data) {
			//Delay Shutdown to send answer first
			$this->getTimerManager()->registerOneTimeListening($this, function () use ($data) {
				if (is_object($data) && property_exists($data, "message")) {
					$this->restart($data->message);
				}
				$this->restart();
			}, 3000);
			return new CommunicationAnswer();
		});
	}

	/**
	 * Load the Config XML-File
	 */
	private function loadConfig() {
		$configId       = CommandLineHelper::getParameter('-config');
		$configFileName = ($configId ? $configId : 'server.xml');
		$config         = FileUtil::loadConfig($configFileName);
		if (!$config) {
			$this->quit("Error loading Configuration XML-File! ('{$configFileName}')", true);
		}
		if ($config->count() < 3) {
			$this->quit("Your Configuration File ('{$configFileName}') doesn't seem to be maintained properly. Please check it again!", true);
		}
		$this->config = $config;
	}

	/**
	 * Quit ManiaControl and log the given message
	 *
	 * @param string $message
	 * @param bool   $errorPrefix
	 */
	public function quit($message = null, $errorPrefix = false) {
		if ($this->getClient()) {
			if ($this->getCallbackManager()) {
				// OnShutdown callback
				$this->getCallbackManager()->triggerCallback(Callbacks::ONSHUTDOWN);
			}

			if ($chat = $this->getChat()) {
				// Announce quit
				try {
					$chat->sendInformation('ManiaControl shutting down.');
				} catch (TransportException $e) {
				}
			}

			// Hide UI
			try {
				$this->getClient()->sendHideManialinkPage();
			} catch (TransportException $e) {
			}

			// Delete client
			Connection::delete($this->getClient());
			$this->client = null;
		}

		SystemUtil::quit($message, $errorPrefix);
	}

	/**
	 * Return the client
	 *
	 * @return Connection
	 */
	public function getClient() {
		return $this->client;
	}

	/**
	 * Return the callback manager
	 *
	 * @return CallbackManager
	 */
	public function getCallbackManager() {
		return $this->callbackManager;
	}

	/**
	 * Return the echo manager
	 *
	 * @return EchoManager
	 */
	public function getEchoManager() {
		return $this->echoManager;
	}

	/**
	 * Return the socket manager
	 *
	 * @return CommunicationManager
	 */
	public function getCommunicationManager() {
		return $this->communicationManager;
	}

	/**
	 * Return the chat
	 *
	 * @return Chat
	 */
	public function getChat() {
		return $this->chat;
	}

	/**
	 * Return the error handler
	 *
	 * @return ErrorHandler
	 */
	public function getErrorHandler() {
		return $this->errorHandler;
	}

	/**
	 * Return the authentication manager
	 *
	 * @return AuthenticationManager
	 */
	public function getAuthenticationManager() {
		return $this->authenticationManager;
	}

	/**
	 * Return the command manager
	 *
	 * @return CommandManager
	 */
	public function getCommandManager() {
		return $this->commandManager;
	}

	/**
	 * Return the timer manager
	 *
	 * @return TimerManager
	 */
	public function getTimerManager() {
		return $this->timerManager;
	}

	/**
	 * Print a message to console and log
	 *
	 * @param string $message
	 * @param bool   $stripCodes
	 * @deprecated
	 * @see Logger::log()
	 */
	public function log($message, $stripCodes = false) {
		Logger::log($message, $stripCodes);
	}

	/**
	 * Return the actions menu
	 *
	 * @return ActionsMenu
	 */
	public function getActionsMenu() {
		return $this->actionsMenu;
	}

	/**
	 * Return the config
	 *
	 * @return \SimpleXMLElement
	 */
	public function getConfig() {
		return $this->config;
	}

	/**
	 * Return the configurator
	 *
	 * @return Configurator
	 */
	public function getConfigurator() {
		return $this->configurator;
	}

	/**
	 * Return the database
	 *
	 * @return Database
	 */
	public function getDatabase() {
		return $this->database;
	}

	/**
	 * Return the manialink manager
	 *
	 * @return ManialinkManager
	 */
	public function getManialinkManager() {
		return $this->manialinkManager;
	}

	/**
	 * Return the map manager
	 *
	 * @return MapManager
	 */
	public function getMapManager() {
		return $this->mapManager;
	}

	/**
	 * Return the player manager
	 *
	 * @return PlayerManager
	 */
	public function getPlayerManager() {
		return $this->playerManager;
	}

	/**
	 * Return the setting manager
	 *
	 * @return SettingManager
	 */
	public function getSettingManager() {
		return $this->settingManager;
	}

	/**
	 * Return the statistic manager
	 *
	 * @return StatisticManager
	 */
	public function getStatisticManager() {
		return $this->statisticManager;
	}

	/**
	 * Return the bill manager
	 *
	 * @return BillManager
	 */
	public function getBillManager() {
		return $this->billManager;
	}

	/**
	 * @return ModeScriptEventManager
	 */
	public function getModeScriptEventManager() {
		return $this->modeScriptEventManager;
	}

	/**
	 * Check connection
	 */
	public function checkConnection() {
		if ($this->getClient()->getIdleTime() > 180) {
			$this->getClient()->getServerName();
		}
	}

	/**
	 * Handle Version Command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function commandVersion(array $chatCallback, Player $player) {
		$message = 'This server is using ManiaControl v' . ManiaControl::VERSION . '!';
		$this->getChat()->sendInformation($message, $player);
	}

	/**
	 * Handle Restart AdminCommand
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function commandRestart(array $chatCallback, Player $player) {
		if (!$this->getAuthenticationManager()->checkPermission($player, self::SETTING_PERMISSION_RESTART)) {
			$this->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}
		$this->restart("ManiaControl Restart requested by '{$player->login}'!");
	}

	/**
	 * Restart ManiaControl
	 *
	 * @param string $message
	 */
	public function restart($message = null) {
		// Trigger callback on Restart
		$this->getCallbackManager()->triggerCallback(Callbacks::ONRESTART);

		// Announce restart
		try {
			$this->getChat()->sendInformation('Restarting ManiaControl...');
		} catch (TransportException $e) {
		}
		Logger::log('Restarting ManiaControl... ' . $message);

		// Start new instance
		SystemUtil::restart();

		// Quit old instance
		$this->quit('Quitting ManiaControl to restart.');
	}

	/**
	 * Handle Shutdown Command
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function commandShutdown(array $chat, Player $player) {
		if (!$this->getAuthenticationManager()->checkPermission($player, self::SETTING_PERMISSION_SHUTDOWN)) {
			$this->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}
		$this->requestQuit("ManiaControl Shutdown requested by '{$player->login}'!");
	}

	/**
	 * Request ManiaControl to quit
	 *
	 * @param mixed $message
	 */
	public function requestQuit($message = true) {
		$this->requestQuitMessage = $message;
	}


	/**
	 * Run ManiaControl
	 */
	public function run() {
		Logger::log('Starting ManiaControl v' . self::VERSION . '!');

		try {
			// Connect to server
			$this->connect();

			// Check if the version of the server is high enough
			$version                           = $this->getClient()->getVersion();
			$this->dedicatedServerBuildVersion = $version->build;
			if ($this->dedicatedServerBuildVersion < self::MIN_DEDIVERSION) {
				$this->quit("The Server has Version '{$this->dedicatedServerBuildVersion}', while at least '" . self::MIN_DEDIVERSION . "' is required!", true);
			}

			// Listen for shutdown
			$this->getCallbackManager()->registerCallbackListener(CallbackManager::CB_MP_SERVERSTOP, $this, 'handleServerStopCallback');

			// OnInit callback
			$this->getCallbackManager()->triggerCallback(Callbacks::ONINIT);

			// Load plugins
			$this->getPluginManager()->loadPlugins();
			$this->getUpdateManager()->getPluginUpdateManager()->checkPluginsUpdate();

			// AfterInit callback
			$this->getCallbackManager()->triggerCallback(Callbacks::AFTERINIT);

			// Loading finished
			Logger::log('Loading completed!');
			Logger::log('Link: ' . $this->getServer()->getJoinLink());
			$this->getChat()->sendInformation('ManiaControl v' . self::VERSION . ' successfully started!');

			// Main loop
			while (!$this->requestQuitMessage) {
				$this->loop();
			}

			// Shutdown
			$this->quit($this->requestQuitMessage);

		} catch (TransportException $exception) {
			Logger::logError('Connection interrupted!');
			$this->getErrorHandler()->handleException($exception);
			SystemUtil::quit($exception->getMessage(), true);
		}
	}

	/**
	 * Connect to ManiaPlanet server
	 */
	private function connect() {
		// Load remote client
		$serverConfig = $this->getServer()->loadConfig();

		Logger::log("Connecting to Server at {$serverConfig->host}:{$serverConfig->port}...");

		try {
			$this->client = Connection::factory($serverConfig->host, $serverConfig->port, self::SCRIPT_TIMEOUT, $serverConfig->user, $serverConfig->pass, self::API_VERSION);
		} catch (TransportException $exception) {
			$message = "Couldn't connect to the server: '{$exception->getMessage()}'";
			$this->quit($message, true);
		} catch (AuthenticationException $exception) {
			$message = "Couldn't authenticate on Server with User '{$serverConfig->user}' & Pass '{$serverConfig->pass}'! " . $exception->getMessage();
			$this->quit($message, true);
		}

		// Enable callback system
		$this->getClient()->enableCallbacks(true);

		// Wait for server to be ready
		if (!$this->getServer()->waitForStatus(4)) {
			$this->quit("Server couldn't get ready!");
		}

		// Connect finished
		Logger::log('Server Connection successfully established!');

		// Hide old widgets
		$this->getClient()->sendHideManialinkPage();

		// Enable script callbacks
		$this->getServer()->getScriptManager()->enableScriptCallbacks();
	}

	/**
	 * Get The Build Version of the Dedicated Server
	 *
	 * @return string
	 */
	public function getDedicatedServerBuildVersion() {
		return $this->dedicatedServerBuildVersion;
	}


	/**
	 * Return the server
	 *
	 * @return Server
	 */
	public function getServer() {
		return $this->server;
	}

	/**
	 * Return the plugin manager
	 *
	 * @return PluginManager
	 */
	public function getPluginManager() {
		return $this->pluginManager;
	}

	/**
	 * Return the update manager
	 *
	 * @return UpdateManager
	 */
	public function getUpdateManager() {
		return $this->updateManager;
	}

	/**
	 * Perform the Main Loop
	 */
	private function loop() {
		$loopStart = microtime(true);

		// Extend script timeout
		set_time_limit(self::SCRIPT_TIMEOUT);

		// Manage callbacks
		$this->getCallbackManager()->manageCallbacks();

		// Manage async file reader
		$this->getFileReader()->appendData();

		// Yield for next tick
		$loopEnd      = microtime(true);
		$loopDuration = $loopEnd - $loopStart;
		$sleepTime    = (int) (2500 - $loopDuration * 1000000);
		if ($sleepTime > 0) {
			usleep($sleepTime);
		}
	}

	/**
	 * Return the file reader
	 *
	 * @return AsynchronousFileReader
	 */
	public function getFileReader() {
		return $this->fileReader;
	}

	/**
	 * Handle Server Stop Callback
	 */
	public function handleServerStopCallback() {
		$this->requestQuit('The Server has been shut down!');
	}


}
