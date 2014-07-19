<?php

namespace ManiaControl;

use ManiaControl\Admin\ActionsMenu;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Bills\BillManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Callbacks\Callbacks;
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\Callbacks\TimerManager;
use ManiaControl\Commands\CommandListener;
use ManiaControl\Commands\CommandManager;
use ManiaControl\Configurators\Configurator;
use ManiaControl\Database\Database;
use ManiaControl\Files\AsynchronousFileReader;
use ManiaControl\Files\FileUtil;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Maps\MapManager;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;
use ManiaControl\Plugins\PluginManager;
use ManiaControl\Server\Server;
use ManiaControl\Settings\SettingManager;
use ManiaControl\Statistics\StatisticManager;
use ManiaControl\Update\UpdateManager;
use ManiaControl\Utils\CommandLineHelper;
use ManiaControl\Utils\Formatter;
use ManiaControl\Utils\SystemUtil;
use Maniaplanet\DedicatedServer\Connection;
use Maniaplanet\DedicatedServer\Xmlrpc\AuthenticationException;
use Maniaplanet\DedicatedServer\Xmlrpc\Exception;
use Maniaplanet\DedicatedServer\Xmlrpc\TransportException;

require_once __DIR__ . '/Libs/Maniaplanet/DedicatedServer/Connection.php';
require_once __DIR__ . '/Libs/FML/autoload.php';
require_once __DIR__ . '/Libs/Symfony/autoload.php';
require_once __DIR__ . '/Libs/curl-easy/autoload.php';

/**
 * ManiaControl Server Controller for ManiaPlanet Server
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ManiaControl implements CallbackListener, CommandListener, TimerListener {
	/*
	 * Constants
	 */
	const VERSION                     = '0.152';
	const API_VERSION                 = '2013-04-16';
	const MIN_DEDIVERSION             = '2014-04-02_18_00';
	const SCRIPT_TIMEOUT              = 10;
	const URL_WEBSERVICE              = 'http://ws.maniacontrol.com/';
	const SETTING_PERMISSION_SHUTDOWN = 'Shutdown ManiaControl';
	const SETTING_PERMISSION_RESTART  = 'Restart ManiaControl';

	/*
	 * Public Properties
	 */
	public $actionsMenu = null;
	public $authenticationManager = null;
	public $callbackManager = null;
	public $chat = null;
	/** @var \SimpleXMLElement $config */
	public $config = null;
	public $configurator = null;
	/** @var Connection $client */
	public $client = null;
	public $commandManager = null;
	public $database = null;
	public $manialinkManager = null;
	public $mapManager = null;
	public $playerManager = null;
	public $pluginManager = null;
	public $server = null;
	public $settingManager = null;
	public $statisticManager = null;
	/** @var UpdateManager $updateManager */
	public $updateManager = null;
	public $errorHandler = null;
	public $timerManager = null;
	public $fileReader = null;
	public $billManager = null;

	/*
	 * Private Properties
	 */
	private $requestQuitMessage = null;

	/**
	 * Construct ManiaControl
	 */
	public function __construct() {
		$this->log('Loading ManiaControl v' . self::VERSION . ' ...');

		$this->errorHandler = new ErrorHandler($this);

		$this->loadConfig();

		// Load ManiaControl Modules
		$this->callbackManager       = new CallbackManager($this);
		$this->timerManager          = new TimerManager($this);
		$this->database              = new Database($this);
		$this->fileReader            = new AsynchronousFileReader($this);
		$this->billManager           = new BillManager($this);
		$this->settingManager        = new SettingManager($this);
		$this->statisticManager      = new StatisticManager($this);
		$this->manialinkManager      = new ManialinkManager($this);
		$this->actionsMenu           = new ActionsMenu($this);
		$this->chat                  = new Chat($this);
		$this->commandManager        = new CommandManager($this);
		$this->server                = new Server($this);
		$this->authenticationManager = new AuthenticationManager($this);
		$this->playerManager         = new PlayerManager($this);
		$this->mapManager            = new MapManager($this);
		$this->configurator          = new Configurator($this);
		$this->pluginManager         = new PluginManager($this);
		$this->updateManager         = new UpdateManager($this);

		$this->errorHandler->init();

		// Define Permission Levels
		$this->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_SHUTDOWN, AuthenticationManager::AUTH_LEVEL_SUPERADMIN);
		$this->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_RESTART, AuthenticationManager::AUTH_LEVEL_SUPERADMIN);

		// Register for commands
		$this->commandManager->registerCommandListener('version', $this, 'commandVersion', false, 'Shows ManiaControl version.');
		$this->commandManager->registerCommandListener('restart', $this, 'commandRestart', true, 'Restarts ManiaControl.');
		$this->commandManager->registerCommandListener('shutdown', $this, 'commandShutdown', true, 'Shuts ManiaControl down.');

		// Check connection every 30 seconds
		$this->timerManager->registerTimerListening($this, 'checkConnection', 1000 * 30);
	}

	/**
	 * Print a message to console and log
	 *
	 * @param string $message
	 * @param bool   $stripCodes
	 */
	public function log($message, $stripCodes = false) {
		if ($stripCodes) {
			$message = Formatter::stripCodes($message);
		}
		Logger::log($message);
	}

	/**
	 * Load the Config XML-File
	 */
	private function loadConfig() {
		$configId       = CommandLineHelper::getParameter('-config');
		$configFileName = ($configId ? $configId : 'server.xml');
		$this->config   = FileUtil::loadConfig($configFileName);
		if (!$this->config) {
			$this->quit("Error loading Configuration XML-File! ('{$configFileName}')", true);
		}
		if ($this->config->count() < 3) {
			$this->quit("Your Configuration File ('{$configFileName}') doesn't seem to be maintained properly. Please check it again!", true);
		}
	}

	/**
	 * Quit ManiaControl and log the given message
	 *
	 * @param string $message
	 * @param bool   $errorPrefix
	 */
	public function quit($message = null, $errorPrefix = false) {
		if ($message) {
			if ($errorPrefix) {
				$message = '[ERROR] ' . $message;
			}
			$this->log($message);
		}
		$this->disconnect();
		exit();
	}

	/**
	 * Close the Client Connection
	 */
	public function disconnect() {
		Connection::delete($this->client);
		$this->client = null;
	}

	/**
	 * Check Connection
	 */
	public function checkConnection() {
		if ($this->client->getIdleTime() > 180) {
			$this->client->getServerName();
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
		$this->chat->sendInformation($message, $player->login);
	}

	/**
	 * Handle Restart AdminCommand
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function commandRestart(array $chatCallback, Player $player) {
		if (!$this->authenticationManager->checkPermission($player, self::SETTING_PERMISSION_RESTART)) {
			$this->authenticationManager->sendNotAllowed($player);
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
		// Shutdown callback
		$this->callbackManager->triggerCallback(Callbacks::ONSHUTDOWN);

		// Announce restart
		$this->chat->sendInformation('Restarting ManiaControl...');
		if ($message) {
			$this->log($message);
		}

		// Hide widgets
		if ($this->client) {
			$this->client->sendHideManialinkPage();
		}

		$this->log('Restarting ManiaControl!');

		// Execute start script in background
		if (SystemUtil::isUnix()) {
			// Unix
			if (!SystemUtil::checkFunctionAvailability('exec')) {
				$this->log("Can't restart ManiaControl because the function 'exec' is disabled!");
				return;
			}
			$fileName = ManiaControlDir . 'ManiaControl.sh';
			if (!is_readable($fileName)) {
				$this->log("Can't restart ManiaControl because the file 'ManiaControl.sh' doesn't exist or isn't readable!");
				return;
			}
			$command = 'sh ' . escapeshellarg($fileName) . ' > /dev/null &';
			exec($command);
		} else {
			// Windows
			if (!SystemUtil::checkFunctionAvailability('system')) {
				$this->log("Can't restart ManiaControl because the function 'system' is disabled!");
				return;
			}
			$command = escapeshellarg(ManiaControlDir . "ManiaControl.bat");
			system($command); // TODO: windows gets stuck here as long controller is running
		}

		// Quit the old instance
		$this->quit('Quitting ManiaControl to restart.');
	}

	/**
	 * Handle Shutdown Command
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function commandShutdown(array $chat, Player $player) {
		if (!$this->authenticationManager->checkPermission($player, self::SETTING_PERMISSION_SHUTDOWN)) {
			$this->authenticationManager->sendNotAllowed($player);
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
		$this->log('Starting ManiaControl v' . self::VERSION . '!');

		// Connect to server
		$this->connect();

		// Check if the version of the server is high enough
		$version = $this->client->getVersion();
		if ($version->build < self::MIN_DEDIVERSION) {
			$this->quit("The Server has Version '{$version->build}', while at least '" . self::MIN_DEDIVERSION . "' is required!", true);
		}

		// Listen for shutdown
		$this->callbackManager->registerCallbackListener(CallbackManager::CB_MP_SERVERSTOP, $this, 'handleServerStopCallback');

		// OnInit callback
		$this->callbackManager->triggerCallback(Callbacks::ONINIT);

		// Load plugins
		$this->pluginManager->loadPlugins();
		$this->updateManager->pluginUpdateManager->checkPluginsUpdate();

		// AfterInit callback
		$this->callbackManager->triggerCallback(Callbacks::AFTERINIT);

		// Loading finished
		$this->log('Loading completed!');
		$this->log('Link: maniaplanet://#join=' . $this->server->login . '@' . $this->server->titleId);
		$this->chat->sendInformation('ManiaControl v' . self::VERSION . ' successfully started!');

		// Main loop
		while (!$this->requestQuitMessage) {
			$this->loop();
		}

		// Shutdown
		$this->quit($this->requestQuitMessage);
	}

	/**
	 * Connect to ManiaPlanet server
	 */
	private function connect() {
		// Load remote client
		$this->server->loadConfig();

		$this->log("Connecting to Server at {$this->server->config->host}:{$this->server->config->port}...");

		try {
			$this->client = Connection::factory($this->server->config->host, $this->server->config->port, self::SCRIPT_TIMEOUT, $this->server->config->user, $this->server->config->pass, self::API_VERSION);
		} catch (TransportException $exception) {
			$message = "Couldn't connect to the server: '{$exception->getMessage()}'";
			$this->quit($message, true);
		} catch (AuthenticationException $exception) {
			$message = "Couldn't authenticate on Server with User '{$this->server->config->user}' & Pass '{$this->server->config->pass}'! " . $exception->getMessage();
			$this->quit($message, true);
		}

		// Enable callback system
		$this->client->enableCallbacks(true);

		// Wait for server to be ready
		try {
			if (!$this->server->waitForStatus(4)) {
				$this->quit("Server couldn't get ready!");
			}
		} catch (Exception $e) {
			// TODO remove
			$this->errorHandler->handleException($e, false);
			$this->quit($e->getMessage(), true);
		}

		// Connect finished
		$this->log('Server Connection successfully established!');

		// Hide old widgets
		$this->client->sendHideManialinkPage();

		// Enable script callbacks
		$this->server->scriptManager->enableScriptCallbacks();
	}

	/**
	 * Perform the Main Loop
	 */
	private function loop() {
		$loopStart = microtime(true);

		// Extend script timeout
		set_time_limit(self::SCRIPT_TIMEOUT);

		try {
			$this->callbackManager->manageCallbacks();
		} catch (TransportException $e) {
			$this->log('Connection interrupted!');
			$this->quit($e->getMessage(), true);
		}

		// Manage FileReader
		$this->fileReader->appendData();

		// Yield for next tick
		$loopEnd      = microtime(true);
		$loopDuration = $loopEnd - $loopStart;
		$sleepTime    = (int)(2500 - $loopDuration * 1000000);
		if ($sleepTime > 0) {
			usleep($sleepTime);
		}
	}

	/**
	 * Handle Server Stop Callback
	 */
	public function handleServerStopCallback() {
		$this->requestQuit('The Server has been shut down!');
	}
}
