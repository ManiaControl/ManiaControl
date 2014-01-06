<?php

namespace ManiaControl;

use ManiaControl\Admin\ActionsMenu;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Commands\CommandListener;
use ManiaControl\Commands\CommandManager;
use ManiaControl\Configurators\Configurator;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Maps\MapManager;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;
use ManiaControl\Plugins\PluginManager;
use ManiaControl\Server\Server;
use ManiaControl\Statistics\StatisticManager;

require_once __DIR__ . '/Callbacks/CallbackListener.php';
require_once __DIR__ . '/Commands/CommandListener.php';
require_once __DIR__ . '/Manialinks/ManialinkPageAnswerListener.php';
require_once __DIR__ . '/Admin/ActionsMenu.php';
require_once __DIR__ . '/Admin/AuthenticationManager.php';
require_once __DIR__ . '/Callbacks/CallbackManager.php';
require_once __DIR__ . '/Chat.php';
require_once __DIR__ . '/ColorUtil.php';
require_once __DIR__ . '/Commands/CommandManager.php';
require_once __DIR__ . '/Configurators/Configurator.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/FileUtil.php';
require_once __DIR__ . '/Formatter.php';
require_once __DIR__ . '/GbxDataFetcher/gbxdatafetcher.inc.php';
require_once __DIR__ . '/ManiaExchange/mxinfofetcher.inc.php';
require_once __DIR__ . '/ManiaExchange/mxinfosearcher.inc.php';
require_once __DIR__ . '/Manialinks/ManialinkManager.php';
require_once __DIR__ . '/Statistics/StatisticManager.php';
require_once __DIR__ . '/Maps/Map.php';
require_once __DIR__ . '/Maps/MapManager.php';
require_once __DIR__ . '/Maps/MapList.php';
require_once __DIR__ . '/Maps/MapQueue.php';
require_once __DIR__ . '/Players/PlayerManager.php';
require_once __DIR__ . '/Players/PlayerActions.php';
require_once __DIR__ . '/Plugins/PluginManager.php';
require_once __DIR__ . '/Players/PlayerList.php';
require_once __DIR__ . '/Server/Server.php';
require_once __DIR__ . '/Settings/SettingManager.php';
require_once __DIR__ . '/UpdateManager.php';
list($endiantest) = array_values(unpack('L1L', pack('V', 1)));
if ($endiantest == 1) {
	require_once __DIR__ . '/PhpRemote/GbxRemote.inc.php';
}
else {
	require_once __DIR__ . '/PhpRemote/GbxRemote.bem.php';
}

/**
 * ManiaControl Server Controller for ManiaPlanet Server
 *
 * @author steeffeen & kremsy
 */
class ManiaControl implements CommandListener {
	/**
	 * Constants
	 */
	const VERSION = '0.01';
	const API_VERSION = '2013-04-16';
	const OS_UNIX = 'Unix';
	const OS_WIN = 'Windows';
	
	/**
	 * Public properties
	 */
	public $actionsMenu = null;
	public $authenticationManager = null;
	public $callbackManager = null;
	public $chat = null;
	public $config = null;
	public $configurator = null;
	/**
	 *
	 * @var \IXR_ClientMulticall_Gbx
	 */
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
	public $updateManager = null;
	
	/**
	 * Private properties
	 */
	private $shutdownRequested = false;

	/**
	 * Construct ManiaControl
	 */
	public function __construct() {
		$this->log('Loading ManiaControl v' . self::VERSION . '...');
		
		// Load config
		$this->config = FileUtil::loadConfig('server.xml');
		
		// Load ManiaControl Modules
		$this->database = new Database($this);
		$this->callbackManager = new CallbackManager($this);
		$this->settingManager = new SettingManager($this);
		$this->statisticManager = new StatisticManager($this);
		$this->manialinkManager = new ManialinkManager($this);
		$this->actionsMenu = new ActionsMenu($this);
		$this->chat = new Chat($this);
		$this->commandManager = new CommandManager($this);
		$this->server = new Server($this);
		$this->playerManager = new PlayerManager($this);
		$this->authenticationManager = new AuthenticationManager($this);
		$this->mapManager = new MapManager($this);
		$this->configurator = new Configurator($this);
		$this->pluginManager = new PluginManager($this);
		$this->updateManager = new UpdateManager($this);
		
		// Register for commands
		$this->commandManager->registerCommandListener('version', $this, 'command_Version');
		$this->commandManager->registerCommandListener('restart', $this, 'command_Restart', true);
		$this->commandManager->registerCommandListener('shutdown', $this, 'command_Shutdown', true);
	}

	/**
	 * Print a message to console and log
	 *
	 * @param string $message
	 */
	public function log($message, $stripCodes = false) {
		if ($stripCodes) {
			$message = Formatter::stripCodes($message);
		}
		logMessage($message);
	}

	/**
	 * Get the Operating System on which ManiaControl is running
	 *
	 * @param string $compareOS
	 * @return string
	 */
	public function getOS($compareOS = null) {
		$windows = defined('PHP_WINDOWS_VERSION_MAJOR');
		if ($compareOS) {
			// Return bool whether OS equals $compareOS
			if ($compareOS == self::OS_WIN) {
				return $windows;
			}
			return !$windows;
		}
		// Return OS
		if ($windows) {
			return self::OS_WIN;
		}
		return self::OS_UNIX;
	}

	/**
	 * Return message composed of client error message and error code
	 *
	 * @param object $client
	 * @return string
	 */
	public function getClientErrorText($client = null) {
		if (is_object($client)) {
			return $client->getErrorMessage() . ' (' . $client->getErrorCode() . ')';
		}
		return $this->client->getErrorMessage() . ' (' . $this->client->getErrorCode() . ')';
	}

	/**
	 * Handle Version Command
	 *
	 * @param array $chatCallback
	 * @param Player $player
	 */
	public function command_Version(array $chatCallback, Player $player) {
		$message = 'This server is using ManiaControl v' . ManiaControl::VERSION . '!';
		$this->chat->sendInformation($message, $player->login);
	}

	/**
	 * Handle Restart AdminCommand
	 *
	 * @param array $chatCallback
	 * @param Player $player
	 */
	public function command_Restart(array $chatCallback, Player $player) {
		if (!AuthenticationManager::checkRight($player, AuthenticationManager::AUTH_LEVEL_SUPERADMIN)) {
			$this->authenticationManager->sendNotAllowed($player);
			return;
		}
		$this->restart("ManiaControl Restart requested by '{$player->login}'!");
	}

	/**
	 * Handle //shutdown command
	 *
	 * @param array $chat
	 * @param Player $player
	 */
	public function command_Shutdown(array $chat, Player $player) {
		if (!$this->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_SUPERADMIN)) {
			$this->authenticationManager->sendNotAllowed($player);
			return;
		}
		$this->quit("ManiaControl Shutdown requested by '{$player->login}'!");
	}

	/**
	 * Quit ManiaControl and log the given message
	 *
	 * @param string $message
	 */
	public function quit($message = null) {
		if ($message) $this->log($message);
		exit();
	}

	/**
	 * Handle PHP Process Shutdown
	 */
	public function handleShutdown() {
		// OnShutdown callback
		$this->callbackManager->triggerCallback(CallbackManager::CB_MC_ONSHUTDOWN, array(CallbackManager::CB_MC_ONSHUTDOWN));
		
		// Announce quit
		$this->chat->sendInformation('ManiaControl shutting down.');
		
		// Hide manialinks
		$this->client->query('SendHideManialinkPage');
		
		// Close connection
		$this->client->Terminate();
		
		$this->log('Quitting ManiaControl!');
		exit();
	}

	/**
	 * Restart ManiaControl
	 *
	 * @param string $message
	 */
	public function restart($message = null) {
		// Shutdown callback
		$this->callbackManager->triggerCallback(CallbackManager::CB_MC_ONSHUTDOWN, array(CallbackManager::CB_MC_ONSHUTDOWN));
		
		// Announce restart
		$this->chat->sendInformation('Restarting ManiaControl...');
		if ($message) $this->log($message);
		
		// Hide widgets
		$this->client->query('SendHideManialinkPage');
		
		// Close connection
		$this->client->Terminate();
		
		$this->log('Restarting ManiaControl!');
		
		// Execute start script in background
		if ($this->getOS(self::OS_UNIX)) {
			$command = 'sh ' . escapeshellarg(ManiaControlDir . '/ManiaControl.sh') . ' > /dev/null &';
			exec($command);
		}
		else {
			// TODO: validate restart on windows
			$command = 'start /B ' . escapeshellarg(ManiaControlDir . '/ManiaControl.bat');
			pclose(popen($command, 'r'));
		}
		
		exit();
	}

	/**
	 * Run ManiaControl
	 */
	public function run() {
		$this->log('Starting ManiaControl v' . self::VERSION . '!');
		
		// Load plugins
		$this->pluginManager->loadPlugins();
		
		// Connect to server
		$this->connect();
		
		// Register shutdown handler
		register_shutdown_function(array($this, 'handleShutdown'));
		
		// Loading finished
		$this->log('Loading completed!');
		
		// Announce ManiaControl
		$this->chat->sendInformation('ManiaControl v' . self::VERSION . ' successfully started!');
		
		// OnInit callback
		$this->callbackManager->triggerCallback(CallbackManager::CB_MC_ONINIT, array(CallbackManager::CB_MC_ONINIT));
		
		// Main loop
		while (!$this->shutdownRequested) {
			$loopStart = microtime(true);
			
			// Disable script timeout
			set_time_limit(30);
			
			// Manager callbacks
			$this->callbackManager->manageCallbacks();
			
			// Yield for next tick
			$loopEnd = microtime(true);
			$sleepTime = 300000 - $loopEnd + $loopStart;
			if ($sleepTime > 0) {
				usleep($sleepTime);
			}
		}
		
		// Shutdown
		$this->quit();
	}

	/**
	 * Connect to ManiaPlanet server
	 */
	private function connect() {
		// Load remote client
		$this->client = new \IXR_ClientMulticall_Gbx();
		
		$host = $this->config->server->xpath('host');
		if (!$host) trigger_error("Invalid server configuration (host).", E_USER_ERROR);
		$host = (string) $host[0];
		$port = $this->config->server->xpath('port');
		if (!$host) trigger_error("Invalid server configuration (port).", E_USER_ERROR);
		$port = (string) $port[0];
		
		$this->log("Connecting to server at {$host}:{$port}...");
		
		// Connect
		if (!$this->client->InitWithIp($host, $port, 20)) {
			trigger_error("Couldn't connect to server! " . $this->getClientErrorText(), E_USER_ERROR);
		}
		
		$login = $this->config->server->xpath('login');
		if (!$login) trigger_error("Invalid server configuration (login).", E_USER_ERROR);
		$login = (string) $login[0];
		$pass = $this->config->server->xpath('pass');
		if (!$pass) trigger_error("Invalid server configuration (password).", E_USER_ERROR);
		$pass = (string) $pass[0];
		
		// Authenticate
		if (!$this->client->query('Authenticate', $login, $pass)) {
			trigger_error("Couldn't authenticate on server with user '{$login}'! " . $this->getClientErrorText(), E_USER_ERROR);
		}
		
		// Enable callback system
		if (!$this->client->query('EnableCallbacks', true)) {
			trigger_error("Couldn't enable callbacks! " . $this->getClientErrorText(), E_USER_ERROR);
		}
		
		// Wait for server to be ready
		if (!$this->server->waitForStatus(4)) {
			trigger_error("Server couldn't get ready!", E_USER_ERROR);
		}
		
		// Set api version
		if (!$this->client->query('SetApiVersion', self::API_VERSION)) {
			trigger_error("Couldn't set API version '" . self::API_VERSION . "'! This might cause problems. " . $this->getClientErrorText());
		}
		
		// Connect finished
		$this->log("Server Connection successfully established!");
		
		// Hide old widgets
		$this->client->query('SendHideManialinkPage');
		
		// Enable script callbacks if needed
		if ($this->server->getGameMode() != 0) return;
		if (!$this->client->query('GetModeScriptSettings')) {
			trigger_error("Couldn't get mode script settings. " . $this->getClientErrorText());
			return;
		}
		$scriptSettings = $this->client->getResponse();
		if (!array_key_exists('S_UseScriptCallbacks', $scriptSettings)) return;
		$scriptSettings['S_UseScriptCallbacks'] = true;
		if (!$this->client->query('SetModeScriptSettings', $scriptSettings)) {
			trigger_error("Couldn't set mode script settings to enable script callbacks. " . $this->getClientErrorText());
			return;
		}
		$this->log('Script Callbacks successfully enabled!');
	}
}
