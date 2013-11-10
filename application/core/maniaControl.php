<?php

namespace ManiaControl;

/**
 * Needed includes
 */
require_once __DIR__ . '/authentication.php';
require_once __DIR__ . '/callbacks.php';
require_once __DIR__ . '/chat.php';
require_once __DIR__ . '/commands.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/fileUtil.php';
require_once __DIR__ . '/manialinkIdHandler.php';
require_once __DIR__ . '/playerHandler.php';
require_once __DIR__ . '/pluginHandler.php';
require_once __DIR__ . '/server.php';
require_once __DIR__ . '/settingManager.php';
require_once __DIR__ . '/settingConfigurator.php';
require_once __DIR__ . '/mapHandler.php';
require_once __DIR__ . '/GbxDataFetcher/gbxdatafetcher.inc.php';

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
 * @author steeffeen and Lukas
 */
class ManiaControl {
	/**
	 * Constants
	 */
	const VERSION = '0.1';
	const API_VERSION = '2013-04-16';
	const DATE = 'd-m-y h:i:sa T';
	
	/**
	 * Public properties
	 */
	public $authentication = null;
	public $callbacks = null;
	public $chat = null;
	public $client = null;
	public $commands = null;
	public $database = null;
	public $manialinkIdHandler = null;
	public $playerHandler = null;
	public $pluginHandler = null;
	public $server = null;
	public $settingConfigurator = null;
	public $settingManager = null;
	/**
	 * Private properties
	 */
	private $shutdownRequested = false;

	/**
	 * Construct ManiaControl
	 */
	public function __construct() {
		$this->database = new Database($this);
		$this->settingManager = new SettingManager($this);
		$this->chat = new Chat($this);
		$this->callbacks = new Callbacks($this);
		$this->server = new Server($this);
		$this->authentication = new Authentication($this);
		$this->playerHandler = new PlayerHandler($this);
		$this->manialinkIdHandler = new ManialinkIdHandler();
		$this->commands = new Commands($this);
		$this->pluginHandler = new PluginHandler($this);
		$this->settingConfigurator = new SettingConfigurator($this);
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
	 * Quit ManiaControl and log the given message
	 */
	public function quit($message = false) {
		if ($this->client) {
			// Announce quit
			$this->chat->sendInformation('ManiaControl shutting down.');
			
			// Hide manialinks
			$this->client->query('SendHideManialinkPage');
		}
		
		// Log quit reason
		if ($message) {
			error_log($message);
		}
		
		// Shutdown
		if ($this->client) {
			$this->client->Terminate();
		}
		
		error_log("Quitting ManiaControl!");
		exit();
	}

	/**
	 * Run ManiaControl
	 */
	public function run() {
		error_log('Starting ManiaControl v' . self::VERSION . '!');
		
		// Load plugins
		$this->pluginHandler->loadPlugins();
		
		// Connect to server
		$this->connect();
		
		// Loading finished
		error_log("Loading completed!");
		
		// Announce ManiaControl
		$this->chat->sendInformation('ManiaControl v' . self::VERSION . ' successfully started!');
		
		// OnInit
		$this->callbacks->onInit();
		
		// Main loop
		while (!$this->shutdownRequested) {
			$loopStart = microtime(true);
			
			// Disable script timeout
			set_time_limit(30);
			
			// Handle server callbacks
			$this->callbacks->handleCallbacks();
			
			// Yield for next tick
			$loopEnd = microtime(true);
			$sleepTime = 300000 - $loopEnd + $loopStart;
			if ($sleepTime > 0) {
				usleep($sleepTime);
			}
		}
		
		// Shutdown
		$this->client->Terminate();
	}

	/**
	 * Connect to ManiaPlanet server
	 */
	private function connect() {
		// Load remote client
		$this->client = new \IXR_ClientMulticall_Gbx();
		
		$host = $this->server->config->xpath('host');
		if (!$host) trigger_error("Invalid server configuration (host).", E_USER_ERROR);
		$host = (string) $host[0];
		$port = $this->server->config->xpath('port');
		if (!$host) trigger_error("Invalid server configuration (port).", E_USER_ERROR);
		$port = (string) $port[0];
		
		error_log("Connecting to server at {$host}:{$port}...");
		
		// Connect
		if (!$this->client->InitWithIp($host, $port, 20)) {
			trigger_error("Couldn't connect to server! " . $this->getClientErrorText(), E_USER_ERROR);
		}
		
		$login = $this->server->config->xpath('login');
		if (!$login) trigger_error("Invalid server configuration (login).", E_USER_ERROR);
		$login = (string) $login[0];
		$pass = $this->server->config->xpath('pass');
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
		if (!$this->server->waitForStatus($this->client, 4)) {
			trigger_error("Server couldn't get ready!", E_USER_ERROR);
		}
		
		// Set api version
		if (!$this->client->query('SetApiVersion', self::API_VERSION)) {
			trigger_error(
					"Couldn't set API version '" . self::API_VERSION . "'! This might cause problems. " . $this->getClientErrorText());
		}
		
		// Connect finished
		error_log("Server connection succesfully established!");
		
		// Enable script callbacks if needed
		if ($this->server->getGameMode() === 0) {
			if (!$this->client->query('GetModeScriptSettings')) {
				trigger_error("Couldn't get mode script settings. " . $this->getClientErrorText());
			}
			else {
				$scriptSettings = $this->client->getResponse();
				if (array_key_exists('S_UseScriptCallbacks', $scriptSettings)) {
					$scriptSettings['S_UseScriptCallbacks'] = true;
					if (!$this->client->query('SetModeScriptSettings', $scriptSettings)) {
						trigger_error("Couldn't set mode script settings to enable script callbacks. " . $this->getClientErrorText());
					}
					else {
						error_log("Script callbacks successfully enabled.");
					}
				}
			}
		}
	}
}

?>
