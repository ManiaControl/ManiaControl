<?php

namespace ManiaControl;

// TODO: Jump message "now playing stadium"
// TODO: put inactive server in idle (keeping same map)
// TODO: let next server wait for the first player
// TODO: check compatibility with other modes (laps, ...)
// TODO: max players setting

/**
 * ManiaControl United Plugin
 *
 * @author steeffeen
 */
class Plugin_United {
	/**
	 * Constants
	 */
	const VERSION = '1.0';
	const ML_ADDFAVORITE = 'MLID_UnitedPlugin.AddFavorite';

	/**
	 * Private properties
	 */
	private $mc = null;

	private $config = null;

	private $settings = null;

	private $gameServer = array();

	private $lobbies = array();

	private $currentClientIndex = 0;

	private $lastStatusCheck = 0;

	private $finishedBegin = -1;

	private $switchServerRequested = -1;

	private $manialinks = array();

	/**
	 * Constuct plugin
	 */
	public function __construct($mc) {
		$this->mc = $mc;
		
		// Load config
		$this->config = Tools::loadConfig('united.plugin.xml');
		$this->loadSettings();
		
		// Check for enabled setting
		if (!$this->settings->enabled) return;
		
		// Load clients
		$this->loadClients();
		
		// Register for callbacks
		$this->mc->callbacks->registerCallbackHandler(Callbacks::CB_IC_ONINIT, $this, 'handleOnInitCallback');
		$this->mc->callbacks->registerCallbackHandler(Callbacks::CB_IC_5_SECOND, $this, 'handle5Seconds');
		$this->mc->callbacks->registerCallbackHandler(Callbacks::CB_MP_PLAYERCONNECT, $this, 'handlePlayerConnect');
		$this->mc->callbacks->registerCallbackHandler(Callbacks::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 
				'handleManialinkPageAnswer');
		
		// Register for commands
		$this->mc->commands->registerCommandHandler('nextserver', $this, 'handleNextServerCommand');
		
		if ($this->settings->widgets_enabled) {
			// Build addfavorite manialink
			$this->buildFavoriteManialink();
		}
		
		error_log('United Pugin v' . self::VERSION . ' ready!');
	}

	/**
	 * Handle ManiaControl OnInit callback
	 *
	 * @param array $callback        	
	 */
	public function handleOnInitCallback($callback) {
		if ($this->settings->widgets_enabled) {
			// Send widgets to all players
			
			if (Tools::toBool($this->config->widgets->addfavorite->enabled)) {
				// Send favorite widget
				if (!$this->mc->client->query('SendDisplayManialinkPage', $this->manialinks[self::ML_ADDFAVORITE]->asXml(), 0, 
						false)) {
					trigger_error("Couldn't send favorite widget! " . $this->mc->getClientErrorText());
				}
			}
		}
	}

	/**
	 * Load settings from config
	 */
	private function loadSettings() {
		$this->settings = new \stdClass();
		
		// Enabled
		$this->settings->enabled = Tools::toBool($this->config->enabled);
		
		// Timeout
		$timeout = $this->mc->server->config->xpath('timeout');
		if ($timeout) {
			$this->settings->timeout = (int) $timeout[0];
		}
		else {
			$this->settings->timeout = 30;
		}
		
		// Game mode
		$mode = $this->config->xpath('mode');
		if ($mode) {
			$mode = (int) $mode[0];
			if ($mode < 1 || $mode > 6) {
				$this->settings->gamemode = 2;
			}
			else {
				$this->settings->gamemode = $mode;
			}
		}
		
		// Server status
		$hide_game_server = $this->config->xpath('hide_game_server');
		if ($hide_game_server) {
			$this->settings->hide_game_server = Tools::toBool($hide_game_server[0]);
		}
		else {
			$this->settings->hide_game_server = true;
		}
		
		// Passwords
		$lobbyPassword = $this->config->xpath('lobbies/password');
		if ($lobbyPassword) {
			$this->settings->lobbyPassword = (string) $lobbyPassword[0];
		}
		else {
			$this->settings->lobbyPassword = '';
		}
		$gamePassword = $this->config->xpath('gameserver/password');
		if ($gamePassword) {
			$this->settings->gamePassword = (string) $gamePassword[0];
		}
		else {
			$this->settings->gamePassword = '';
		}
		
		// Widgets
		$this->settings->widgets_enabled = Tools::toBool($this->config->widgets->enabled);
	}

	/**
	 * Loop events on clients
	 */
	public function loop() {
		if (!$this->settings->enabled) return;
		
		// Check callbacks all clients
		$clients = array_merge($this->gameServer, $this->lobbies);
		$currentServer = $this->gameServer[$this->currentClientIndex];
		foreach ($clients as $index => $client) {
			$client->resetError();
			$client->readCB();
			$callbacks = $client->getCBResponses();
			if (!is_array($callbacks) || $client->isError()) {
				trigger_error("Error reading server callbacks! " . $this->mc->getClientErrorText($client));
			}
			else {
				if ($client == $currentServer) {
					// Currently active game server
					foreach ($callbacks as $index => $callback) {
						$callbackName = $callback[0];
						switch ($callbackName) {
							case Callbacks::CB_MP_ENDMAP:
								{
									$this->switchToNextServer(false);
									break;
								}
						}
					}
					
					if ($this->lastStatusCheck + 2 > time()) continue;
					$this->lastStatusCheck = time();
					
					if (!$client->query('CheckEndMatchCondition')) {
						trigger_error("Couldn't get game server status. " . $this->mc->getClientErrorText($client));
					}
					else {
						$response = $client->getResponse();
						switch ($response) {
							case 'Finished':
								{
									if ($this->finishedBegin < 0) {
										$this->finishedBegin = time();
									}
									else if ($this->finishedBegin + 13 <= time()) {
										$this->switchToNextServer(true);
									}
									break;
								}
							default:
								{
									$this->finishedBegin = -1;
									break;
								}
						}
					}
				}
				else {
					// Lobby or inactive game server -> Redirect players
					foreach ($callbacks as $callback) {
						switch ($callback[0]) {
							case Callbacks::CB_MP_PLAYERCONNECT:
								{
									$this->playerJoinedLobby($client, $callback);
									break;
								}
						}
					}
				}
			}
		}
		
		// Check for switch server request
		if ($this->switchServerRequested > 0 && $this->switchServerRequested <= time()) {
			$this->switchServerRequested = -1;
			
			// Switch server
			$this->switchToNextServer(true);
		}
	}

	/**
	 * Handle 5 seconds callback
	 */
	public function handle5Seconds($callback = null) {
		// Update lobby infos
		$players = $this->mc->server->getPlayers();
		if (is_array($players)) {
			$playerCount = count($players);
			$playerLevel = 0.;
			if ($playerCount > 0) {
				foreach ($players as $player) {
					$playerLevel += $player['LadderRanking'];
				}
				$playerLevel /= $playerCount;
			}
			foreach ($this->lobbies as $lobby) {
				if (!$lobby->query('SetLobbyInfo', true, $playerCount, 255, $playerLevel)) {
					trigger_error("Couldn't update lobby info. " . $this->mc->getClientErrorText($lobby));
				}
			}
		}
		
		// Check for not-redirected players
		$clients = array_merge($this->gameServer, $this->lobbies);
		$joinLink = $this->getJoinLink();
		foreach ($clients as $client) {
			if ($client == $this->gameServer[$this->currentClientIndex]) continue;
			$players = $this->mc->server->getPlayers($client);
			if (!is_array($players)) continue;
			foreach ($players as $player) {
				$login = $player['Login'];
				if (!$client->query('SendOpenLinkToLogin', $login, $joinLink, 1)) {
					trigger_error(
							"Couldn't redirect player '" . $login . "' to active game server. " .
									 $this->mc->getClientErrorText($client));
				}
			}
		}
	}

	/**
	 * Handle player manialink page answer callback
	 */
	public function handleManialinkPageAnswer($callback) {
		$login = $callback[1][1];
		$action = $callback[1][2];
		switch ($action) {
			case self::ML_ADDFAVORITE:
				{
					// Open manialink to add server logins to favorite
					$serverLogins = array();
					$add_all = Tools::toBool($this->config->widgets->addfavorite->add_all);
					if ($add_all) {
						// Add all server
						foreach ($this->gameServer as $serverClient) {
							array_push($serverLogins, $this->mc->server->getLogin($serverClient));
						}
						foreach ($this->lobbies as $serverClient) {
							array_push($serverLogins, $this->mc->server->getLogin($serverClient));
						}
					}
					else {
						// Add only current server
						array_push($serverLogins, $this->mc->server->getLogin());
					}
					
					// Build manialink url
					$manialink = 'mc?favorite';
					foreach ($serverLogins as $serverLogin) {
						$manialink .= '&' . $serverLogin;
					}
					
					// Send url to player
					if (!$this->mc->client->query('SendOpenLinkToLogin', $login, $manialink, 1)) {
						trigger_error(
								"Couldn't open manialink to add server to favorite for '" . $login . "'! " .
										 $this->mc->getClientErrorText());
					}
					break;
				}
		}
	}

	/**
	 * Switch to the next server
	 *
	 * @param bool $simulateMapEnd
	 *        	Simulate end of the map by sending callbacks
	 */
	private function switchToNextServer($simulateMapEnd) {
		$this->finishedBegin = -1;
		$oldClient = $this->gameServer[$this->currentClientIndex];
		
		$random_order = Tools::toBool($this->config->random_order);
		if ($random_order) {
			// Random next server
			$this->currentClientIndex = rand(0, count($this->gameServer) - 1);
		}
		else {
			// Next server in list
			$this->currentClientIndex++;
		}
		if ($this->currentClientIndex >= count($this->gameServer)) $this->currentClientIndex = 0;
		
		$newClient = $this->gameServer[$this->currentClientIndex];
		if ($newClient == $oldClient) return;
		
		// Restart map on next game server
		if (!$newClient->query('RestartMap')) {
			trigger_error("Couldn't restart map on next game server. " . $this->mc->getClientErrorText($newClient));
		}
		
		if ($simulateMapEnd) {
			// Simulate EndMap on old client
			$this->mc->callbacks->triggerCallback(Callbacks::CB_IC_ENDMAP, array(Callbacks::CB_IC_ENDMAP));
		}
		
		// Transfer players to next server
		$joinLink = $this->getJoinLink($newClient);
		if (!$oldClient->query('GetPlayerList', 255, 0)) {
			trigger_error("Couldn't get player list. " . $this->mc->getClientErrorText($oldClient));
		}
		else {
			$playerList = $oldClient->getResponse();
			foreach ($playerList as $player) {
				$login = $player['Login'];
				if (!$oldClient->query('SendOpenLinkToLogin', $login, $joinLink, 1)) {
					trigger_error("Couldn't redirect player to next game server. " . $this->mc->getClientErrorText($oldClient));
				}
			}
			
			$this->mc->client = $newClient;
		}
		
		// Trigger client updated callback
		$this->mc->callbacks->triggerCallback(Callbacks::CB_IC_CLIENTUPDATED, "Plugin_United.SwitchedServer");
		
		if ($simulateMapEnd) {
			// Simulate BeginMap on new client
			$map = $this->mc->server->getMap();
			if ($map) {
				$this->mc->callbacks->triggerCallback(Callbacks::CB_IC_BEGINMAP, array(Callbacks::CB_IC_BEGINMAP, array($map)));
			}
		}
	}

	/**
	 * Handle nextserver command
	 *
	 * @param mixed $command        	
	 */
	public function handleNextServerCommand($command) {
		if (!$command) return;
		$login = $command[1][1];
		
		if (!$this->mc->authentication->checkRight($login, 'operator')) {
			// Not allowed
			$this->mc->authentication->sendNotAllowed($login);
			return;
		}
		
		// Request skip to next server
		$this->switchServerRequested = time() + 3;
		
		// Send chat message
		$this->mc->chat->sendInformation("Switching to next server in 3 seconds...");
	}

	/**
	 * Handle PlayerConnect callback
	 */
	public function playerJoinedLobby($client, $callback) {
		if (!$client) return;
		
		$data = $callback[1];
		$login = $data[0];
		
		// Redirect player to current game server
		$gameserver = $this->gameServer[$this->currentClientIndex];
		$joinLink = $this->getJoinLink($gameserver, !$data[1]);
		if (!$client->query('SendOpenLinkToLogin', $login, $joinLink, 1)) {
			trigger_error(
					"United Plugin: Couldn't redirect player to current game server. " . $this->mc->getClientErrorText($client));
		}
	}

	/**
	 * Connect to the game server defined in the config
	 */
	private function loadClients() {
		$gameserver = $this->config->xpath('gameserver/server');
		$lobbies = $this->config->xpath('lobbies/server');
		
		$clientsConfig = array_merge($gameserver, $lobbies);
		foreach ($clientsConfig as $index => $serv) {
			$isGameServer = (in_array($serv, $gameserver));
			
			$host = $serv->xpath('host');
			$port = $serv->xpath('port');
			if (!$host || !$port) {
				trigger_error("Invalid configuration!", E_USER_ERROR);
			}
			$host = (string) $host[0];
			$port = (string) $port[0];
			
			error_log("Connecting to united " . ($isGameServer ? 'game' : 'lobby') . " server at " . $host . ":" . $port . "...");
			$client = new \IXR_ClientMulticall_Gbx();
			
			// Connect
			if (!$client->InitWithIp($host, $port, $this->settings->timeout)) {
				trigger_error(
						"Couldn't connect to united " . ($isGameServer ? 'game' : lobby) . " server! " . $client->getErrorMessage() .
								 "(" . $client->getErrorCode() . ")", E_USER_ERROR);
			}
			
			$login = $serv->xpath('login');
			$pass = $serv->xpath('pass');
			if (!$login || !$pass) {
				trigger_error("Invalid configuration!", E_USER_ERROR);
			}
			$login = (string) $login[0];
			$pass = (string) $pass[0];
			
			// Authenticate
			if (!$client->query('Authenticate', $login, $pass)) {
				trigger_error(
						"Couldn't authenticate on united " . ($isGameServer ? 'game' : 'lobby') . " server with user '" . $login . "'! " .
								 $client->getErrorMessage() . "(" . $client->getErrorCode() . ")", E_USER_ERROR);
			}
			
			// Enable callback system
			if (!$client->query('EnableCallbacks', true)) {
				trigger_error("Couldn't enable callbacks! " . $client->getErrorMessage() . "(" . $client->getErrorCode() . ")", 
						E_USER_ERROR);
			}
			
			// Wait for server to be ready
			if (!$this->mc->server->waitForStatus($client, 4)) {
				trigger_error("Server couldn't get ready!", E_USER_ERROR);
			}
			
			// Set api version
			if (!$client->query('SetApiVersion', ManiaControl::API_VERSION)) {
				trigger_error(
						"Couldn't set API version '" . ManiaControl::API_VERSION . "'! This might cause problems. " .
								 $this->mc->getClientErrorText($client));
			}
			
			// Set server settings
			$password = ($isGameServer ? $this->settings->gamePassword : $this->settings->lobbyPassword);
			$hideServer = ($isGameServer && $this->settings->hide_game_server ? 1 : 0);
			// Passwords
			if (!$client->query('SetServerPassword', $password)) {
				trigger_error("Couldn't set server join password. " . $this->mc->getClientErrorText($client));
			}
			if (!$client->query('SetServerPasswordForSpectator', $password)) {
				trigger_error("Couldn't set server spec password. " . $this->mc->getClientErrorText($client));
			}
			// Show/Hide server
			if (!$client->query('SetHideServer', $hideServer)) {
				trigger_error(
						"Couldn't set server '" . ($hideServer == 0 ? 'shown' : 'hidden') . "'. " .
								 $this->mc->getClientErrorText($client));
			}
			
			// Enable service announces
			if (!$client->query("DisableServiceAnnounces", false)) {
				trigger_error("Couldn't enable service announces. " . $this->mc->getClientErrorText($client));
			}
			
			// Set game mode
			if (!$client->query('SetGameMode', $this->settings->gamemode)) {
				trigger_error(
						"Couldn't set game mode (" . $this->settings->gamemode . "). " . $this->mc->getClientErrorText($client));
			}
			else if (!$client->query('RestartMap')) {
				trigger_error("Couldn't restart map to change game mode. " . $this->mc->getClientErrorText($client));
			}
			
			// Save client
			$client->index = $index;
			if ($isGameServer) {
				array_push($this->gameServer, $client);
				if (count($this->gameServer) === 1) {
					$this->mc->client = $client;
				}
			}
			else {
				array_push($this->lobbies, $client);
			}
		}
		
		error_log("United Plugin: Connected to all game and lobby server!");
	}

	/**
	 * Handle PlayerConnect callback
	 *
	 * @param array $callback        	
	 */
	public function handlePlayerConnect($callback) {
		if ($this->settings->widgets_enabled) {
			// Send manialinks to the client
			$login = $callback[1][0];
			
			if (Tools::toBool($this->config->widgets->addfavorite->enabled)) {
				// Send favorite widget
				if (!$this->mc->client->query('SendDisplayManialinkPageToLogin', $login, 
						$this->manialinks[self::ML_ADDFAVORITE]->asXml(), 0, false)) {
					trigger_error("Couldn't send favorite widget to player '" . $login . "'! " . $this->mc->getClientErrorText());
				}
			}
		}
	}

	/**
	 * Build join link for the given client
	 */
	private function getJoinLink(&$client = null, $play = true) {
		if (!$client) {
			$client = $this->gameServer[$this->currentClientIndex];
		}
		if (!$client->query('GetSystemInfo')) {
			trigger_error("Couldn't fetch server system info. " . $this->mc->getClientErrorText($client));
			return null;
		}
		else {
			$systemInfo = $client->getResponse();
			$password = '';
			if (!$client->query('GetServerPassword')) {
				trigger_error("Couldn't get server password. " . $this->mc->getClientErrorText($client));
			}
			else {
				$password = $client->getResponse();
			}
			return '#q' . ($play ? 'join' : 'spectate') . '=' . $systemInfo['ServerLogin'] .
					 (strlen($password) > 0 ? ':' . $password : '') . '@' . $systemInfo['TitleId'];
		}
	}

	/**
	 * Build manialink for addfavorite button
	 */
	private function buildFavoriteManialink() {
		// Load configs
		$config = $this->config->widgets->addfavorite;
		if (!Tools::toBool($config->enabled)) return;
		
		$pos_x = (float) $config->pos_x;
		$pos_y = (float) $config->pos_y;
		$height = (float) $config->height;
		$width = (float) $config->width;
		$add_all = Tools::toBool($config->add_all);
		
		// Build manialink
		$xml = Tools::newManialinkXml(self::ML_ADDFAVORITE);
		
		$frameXml = $xml->addChild('frame');
		$frameXml->addAttribute('posn', $pos_x . ' ' . $pos_y);
		
		// Background
		$quadXml = $frameXml->addChild('quad');
		Tools::addAlignment($quadXml);
		$quadXml->addAttribute('posn', '0 0 0');
		$quadXml->addAttribute('sizen', $width . ' ' . $height);
		$quadXml->addAttribute('style', 'Bgs1InRace');
		$quadXml->addAttribute('substyle', 'BgTitleShadow');
		$quadXml->addAttribute('action', self::ML_ADDFAVORITE);
		
		// Heart
		$quadXml = $frameXml->addChild('quad');
		Tools::addAlignment($quadXml);
		$quadXml->addAttribute('id', 'Quad_AddFavorite');
		$quadXml->addAttribute('posn', '0 0 1');
		$quadXml->addAttribute('sizen', ($width - 1.) . ' ' . ($height - 0.8));
		$quadXml->addAttribute('style', 'Icons64x64_1');
		$quadXml->addAttribute('substyle', 'StateFavourite');
		$quadXml->addAttribute('scriptevents', '1');
		
		// Tooltip
		$tooltipFrameXml = $frameXml->addChild('frame');
		$tooltipFrameXml->addAttribute('id', 'Frame_FavoriteTooltip');
		$tooltipFrameXml->addAttribute('posn', '0 ' . ($pos_y >= 0 ? '-' : '') . '13');
		$tooltipFrameXml->addAttribute('hidden', '1');
		
		$quadXml = $tooltipFrameXml->addChild('quad');
		Tools::addAlignment($quadXml);
		$quadXml->addAttribute('posn', '0 0 2');
		$quadXml->addAttribute('sizen', '28 16');
		$quadXml->addAttribute('style', 'Bgs1InRace');
		$quadXml->addAttribute('substyle', 'BgTitleShadow');
		
		$labelXml = $tooltipFrameXml->addChild('label');
		Tools::addAlignment($labelXml);
		Tools::addTranslate($labelXml);
		$labelXml->addAttribute('posn', '0 0 3');
		$labelXml->addAttribute('sizen', '26 0');
		$labelXml->addAttribute('style', 'TextTitle1');
		$labelXml->addAttribute('textsize', '2');
		$labelXml->addAttribute('autonewline', '1');
		$countText = '';
		if ($add_all) {
			$count = count($this->gameServer) + count($this->lobbies);
			$countText = 'all ' . $count . ' ';
		}
		$labelXml->addAttribute('text', 'Add ' . $countText . 'server to Favorite!');
		
		// Script for tooltip
		$script = '
declare Frame_FavoriteTooltip <=> (Page.GetFirstChild("Frame_FavoriteTooltip") as CMlFrame);
while (True) {
	yield;
	foreach (Event in PendingEvents) {
		switch (Event.Type) {
			case CMlEvent::Type::MouseOver: {
				switch (Event.ControlId) {
					case "Quad_AddFavorite": {
						Frame_FavoriteTooltip.Visible = True;
					}
				}
			}
			case CMlEvent::Type::MouseOut: {
				switch (Event.ControlId) {
					case "Quad_AddFavorite": {
						Frame_FavoriteTooltip.Visible = False;
					}
				}
			}
		}
	}
}';
		$xml->addChild('script', $script);
		
		$this->manialinks[self::ML_ADDFAVORITE] = $xml;
	}
}

?>
	