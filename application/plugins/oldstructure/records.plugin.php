<?php

namespace ManiaControl;

// TODO: show mix of best and next records (depending on own one)
// TODO: enable keep-alive
// TODO: enable gzip compression
// TODO: log errors from WarningsAndTTR2
// TODO: check map requirements (cp count + length)
// TODO: check checkpoints on finish
// TODO: threaded requests

/**
 * ManiaControl Records Plugin
 *
 * @author steeffeen
 */
class Plugin_Records {
	/**
	 * Constants
	 */
	const VERSION = '1.0';
	const MLID_LOCAL = 'ml_local_records';
	const MLID_DEDI = 'ml_dedimania_records';
	const TABLE_RECORDS = 'ic_records';
	const XMLRPC_MULTICALL = 'system.multicall';
	const DEDIMANIA_URL = 'http://dedimania.net:8081/Dedimania';
	const DEDIMANIA_OPENSESSION = 'dedimania.OpenSession';
	const DEDIMANIA_CHECKSESSION = 'dedimania.CheckSession';
	const DEDIMANIA_GETRECORDS = 'dedimania.GetChallengeRecords';
	const DEDIMANIA_PLAYERCONNECT = 'dedimania.PlayerConnect';
	const DEDIMANIA_PLAYERDISCONNECT = 'dedimania.PlayerDisconnect';
	const DEDIMANIA_UPDATESERVERPLAYERS = 'dedimania.UpdateServerPlayers';
	const DEDIMANIA_SETCHALLENGETIMES = 'dedimania.SetChallengeTimes';
	const DEDIMANIA_WARNINGSANDTTR2 = 'dedimania.WarningsAndTTR2';

	/**
	 * Private properties
	 */
	private $mc = null;

	private $settings = null;

	private $config = null;

	private $mapInfo = null;

	private $manialinks = array();

	private $lastSendManialinks = array();

	private $updateManialinks = array();

	private $dedimaniaData = array();

	private $checkpoints = array();

	/**
	 * Constuct plugin
	 */
	public function __construct($mc) {
		$this->mc = $mc;
		
		// Load config
		$this->config = Tools::loadConfig('records.plugin.xml');
		
		// Check for enabled setting
		if (!Tools::toBool($this->config->enabled)) return;
		
		// Load settings
		$this->loadSettings();
		
		// Init tables
		$this->initTables();
		
		// Register for callbacks
		$this->mc->callbacks->registerCallbackHandler(Callbacks::CB_IC_ONINIT, $this, 'handleOnInit');
		$this->mc->callbacks->registerCallbackHandler(Callbacks::CB_IC_1_SECOND, $this, 'handle1Second');
		$this->mc->callbacks->registerCallbackHandler(Callbacks::CB_IC_1_MINUTE, $this, 'handle1Minute');
		$this->mc->callbacks->registerCallbackHandler(Callbacks::CB_IC_3_MINUTE, $this, 'handle3Minute');
		$this->mc->callbacks->registerCallbackHandler(Callbacks::CB_IC_BEGINMAP, $this, 'handleMapBegin');
		$this->mc->callbacks->registerCallbackHandler(Callbacks::CB_IC_CLIENTUPDATED, $this, 'handleClientUpdated');
		$this->mc->callbacks->registerCallbackHandler(Callbacks::CB_IC_ENDMAP, $this, 'handleMapEnd');
		$this->mc->callbacks->registerCallbackHandler(Callbacks::CB_MP_PLAYERCONNECT, $this, 'handlePlayerConnect');
		$this->mc->callbacks->registerCallbackHandler(Callbacks::CB_MP_PLAYERDISCONNECT, $this, 'handlePlayerDisconnect');
		$this->mc->callbacks->registerCallbackHandler(Callbacks::CB_TM_PLAYERFINISH, $this, 'handlePlayerFinish');
		$this->mc->callbacks->registerCallbackHandler(Callbacks::CB_TM_PLAYERCHECKPOINT, $this, 'handlePlayerCheckpoint');
		
		error_log('Records Pugin v' . self::VERSION . ' ready!');
	}

	/**
	 * Init needed database tables
	 */
	private function initTables() {
		$database = $this->mc->database;
		
		// Records table
		$query = "CREATE TABLE IF NOT EXISTS `" . self::TABLE_RECORDS . "` (
			`index` int(11) NOT NULL AUTO_INCREMENT,
			`mapUId` varchar(100) NOT NULL,
			`Login` varchar(100) NOT NULL,
			`time` int(11) NOT NULL,
			`changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (`index`),
			UNIQUE KEY `player_map_record` (`mapUId`,`Login`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
		if (!$database->query($query)) {
			trigger_error("Couldn't create records table. " . $database->mysqli->error);
		}
	}

	/**
	 * Load settings from config
	 */
	private function loadSettings() {
		$this->settings = new \stdClass();
		
		$this->settings->enabled = Tools::toBool($this->config->enabled);
		
		$this->settings->local_records_enabled = $this->settings->enabled && Tools::toBool($this->config->local_records->enabled);
		$this->settings->dedimania_enabled = $this->settings->enabled && Tools::toBool($this->config->dedimania_records->enabled);
	}

	/**
	 * Handle ManiaControl init
	 */
	public function handleOnInit($callback = null) {
		// Let manialinks update
		if ($this->settings->local_records_enabled) {
			$this->updateManialinks[self::MLID_LOCAL] = true;
		}
		
		// Update mapinfo
		$this->mapInfo = $this->getMapInfo();
		
		if ($this->settings->dedimania_enabled) {
			// Open dedimania session
			$accounts = $this->config->xpath('dedimania_records/account');
			if (!$accounts) {
				trigger_error('Invalid dedimania_code in config.');
				$this->settings->dedimania_enabled = false;
			}
			else {
				$this->openDedimaniaSession();
			}
			$this->fetchDedimaniaRecords();
			$this->updateManialinks[self::MLID_DEDI] = true;
		}
	}

	/**
	 * Fetch dedimania records of the current map
	 */
	private function fetchDedimaniaRecords($reset = true) {
		if (!isset($this->dedimaniaData['context'])) return false;
		if ($reset || !isset($this->dedimaniaData['records']) && !is_array($this->dedimaniaData['records'])) {
			// Reset records
			$this->dedimaniaData['records'] = array();
		}
		
		// Fetch records
		$servInfo = $this->getSrvInfo();
		$playerInfo = $this->getPlayerList();
		$gameMode = $this->getGameModeString();
		$data = array($this->dedimaniaData['sessionId'], $this->mapInfo, $gameMode, $servInfo, $playerInfo);
		$request = $this->encode_request(self::DEDIMANIA_GETRECORDS, $data);
		stream_context_set_option($this->dedimaniaData['context'], 'http', 'content', $request);
		$file = file_get_contents(self::DEDIMANIA_URL, false, $this->dedimaniaData['context']);
		
		// Handle response
		$response = $this->decode($file);
		if (is_array($response)) {
			foreach ($response as $index => $methodResponse) {
				if (xmlrpc_is_fault($methodResponse)) {
					$this->handleXmlRpcFault($methodResponse);
					return false;
				}
				else if ($index <= 0) {
					$responseData = $methodResponse[0];
					$this->dedimaniaData['records'] = $responseData;
				}
			}
		}
		return true;
	}

	/**
	 * Checks dedimania session
	 */
	private function checkDedimaniaSession() {
		if (!$this->settings->dedimania_enabled) return false;
		if (!isset($this->dedimaniaData['context'])) return false;
		if (!isset($this->dedimaniaData['sessionId']) || !is_string($this->dedimaniaData['sessionId'])) return false;
		
		// Check session
		$request = $this->encode_request(self::DEDIMANIA_CHECKSESSION, array($this->dedimaniaData['sessionId']));
		stream_context_set_option($this->dedimaniaData['context'], 'http', 'content', $request);
		$file = file_get_contents(self::DEDIMANIA_URL, false, $this->dedimaniaData['context']);
		
		// Handle response
		$response = $this->decode($file);
		$result = false;
		if (is_array($response)) {
			foreach ($response as $methodResponse) {
				if (xmlrpc_is_fault($methodResponse)) {
					$this->handleXmlRpcFault($methodResponse);
				}
				else {
					$responseData = $methodResponse[0];
					if (is_bool($responseData)) {
						$result = $responseData;
					}
				}
			}
		}
		return $result;
	}

	/**
	 * Renews dedimania session
	 */
	private function openDedimaniaSession($init = false) {
		if (!$this->settings->dedimania_enabled) return false;
		
		// Get server data
		if ($init || !array_key_exists('serverData', $this->dedimaniaData) || !is_array($this->dedimaniaData['serverData'])) {
			$serverData = array();
			$serverData['Game'] = 'TM2';
			$serverInfo = $this->mc->server->getInfo(true);
			
			// Get dedimania account data
			$accounts = $this->config->xpath('dedimania_records/account');
			foreach ($accounts as $account) {
				$login = (string) $account->login;
				if ($login != $serverInfo['Login']) continue;
				$code = (string) $account->code;
				$serverData['Login'] = $login;
				$serverData['Code'] = $code;
				break;
			}
			
			if (!isset($serverData['Login']) || !isset($serverData['Code'])) {
				// Wrong configuration for current server
				trigger_error("Records Plugin: Invalid dedimania configuration for login '" . $serverInfo['Login'] . "'.");
				if (isset($this->dedimaniaData['context'])) unset($this->dedimaniaData['context']);
				if (isset($this->dedimaniaData['sessionId'])) unset($this->dedimaniaData['sessionId']);
				return false;
			}
			
			// Complete seesion data
			$serverData['Path'] = $serverInfo['Path'];
			$systemInfo = $this->mc->server->getSystemInfo();
			$serverData['Packmask'] = substr($systemInfo['TitleId'], 2);
			$serverVersion = $this->mc->server->getVersion();
			$serverData['ServerVersion'] = $serverVersion['Version'];
			$serverData['ServerBuild'] = $serverVersion['Build'];
			$serverData['Tool'] = 'ManiaControl';
			$serverData['Version'] = ManiaControl::VERSION;
			$this->dedimaniaData['serverData'] = $serverData;
		}
		
		// Init header
		if ($init || !array_key_exists('header', $this->dedimaniaData) || !is_array($this->dedimaniaData['header'])) {
			$header = '';
			$header .= 'Accept-Charset: utf-8;' . PHP_EOL;
			$header .= 'Accept-Encoding: gzip;' . PHP_EOL;
			$header .= 'Content-Type: text/xml; charset=utf-8;' . PHP_EOL;
			$header .= 'Keep-Alive: 300;' . PHP_EOL;
			$header .= 'User-Agent: ManiaControl v' . ManiaControl::VERSION . ';' . PHP_EOL;
			$this->dedimaniaData['header'] = $header;
		}
		
		// Open session
		$request = $this->encode_request(self::DEDIMANIA_OPENSESSION, array($this->dedimaniaData['serverData']));
		$context = stream_context_create(array('http' => array('method' => 'POST', 'header' => $this->dedimaniaData['header'])));
		stream_context_set_option($context, 'http', 'content', $request);
		$file = file_get_contents(self::DEDIMANIA_URL, false, $context);
		
		// Handle response
		$response = $this->decode($file);
		$result = false;
		if (is_array($response)) {
			foreach ($response as $index => $methodResponse) {
				if (xmlrpc_is_fault($methodResponse)) {
					$this->handleXmlRpcFault($methodResponse);
				}
				else if ($index <= 0) {
					$responseData = $methodResponse[0];
					$this->dedimaniaData['context'] = $context;
					$this->dedimaniaData['sessionId'] = $responseData['SessionId'];
					$result = true;
				}
			}
		}
		if ($result) error_log("Dedimania connection successfully established.");
		return $result;
	}

	/**
	 * Handle 1Second callback
	 */
	public function handle1Second() {
		if (!$this->settings->enabled) return;
		
		// Send records manialinks if needed
		foreach ($this->updateManialinks as $id => $update) {
			if (!$update) continue;
			if (array_key_exists($id, $this->lastSendManialinks) && $this->lastSendManialinks[$id] + 2 > time()) continue;
			
			switch ($id) {
				case self::MLID_LOCAL:
					{
						$this->manialinks[$id] = $this->buildLocalManialink();
						break;
					}
				case self::MLID_DEDI:
					{
						$this->manialinks[$id] = $this->buildDedimaniaManialink();
						break;
					}
				default:
					{
						continue 2;
						break;
					}
			}
			$this->updateManialinks[$id] = false;
			$this->lastSendManialinks[$id] = time();
			$this->sendManialink($this->manialinks[$id]);
		}
	}

	/**
	 * Handle 1Minute callback
	 */
	public function handle1Minute($callback = null) {
		if ($this->settings->dedimania_enabled) {
			// Keep dedimania session alive
			if (!$this->checkDedimaniaSession()) {
				// Renew session
				$this->openDedimaniaSession();
			}
		}
	}

	/**
	 * Handle PlayerConnect callback
	 */
	public function handlePlayerConnect($callback) {
		$login = $callback[1][0];
		if ($this->settings->local_records_enabled) $this->sendManialink($this->manialinks[self::MLID_LOCAL], $login);
		
		if ($this->settings->dedimania_enabled && $this->dedimaniaData['context']) {
			$player = $this->mc->server->getPlayer($login, true);
			if ($player) {
				// Send dedimania request
				$data = array($this->dedimaniaData['sessionId'], $player['Login'], $player['NickName'], $player['Path'], 
					$player['IsSpectator']);
				$request = $this->encode_request(self::DEDIMANIA_PLAYERCONNECT, $data);
				stream_context_set_option($this->dedimaniaData['context'], 'http', 'content', $request);
				$file = file_get_contents(self::DEDIMANIA_URL, false, $this->dedimaniaData['context']);
				
				// Handle response
				$response = $this->decode($file);
				if (is_array($response)) {
					foreach ($response as $methodResponse) {
						if (xmlrpc_is_fault($methodResponse)) {
							$this->handleXmlRpcFault($methodResponse);
						}
					}
				}
				else {
					if (!$response) {
						trigger_error('XmlRpc Error.');
						var_dump($response);
					}
				}
			}
			$this->sendManialink($this->manialinks[self::MLID_DEDI], $login);
		}
	}

	/**
	 * Handle PlayerDisconnect callback
	 */
	public function handlePlayerDisconnect($callback) {
		$login = $callback[1][0];
		
		if ($this->settings->dedimania_enabled && $this->dedimaniaData['context']) {
			// Send dedimania request
			$data = array($this->dedimaniaData['sessionId'], $login, '');
			$request = $this->encode_request(self::DEDIMANIA_PLAYERDISCONNECT, $data);
			stream_context_set_option($this->dedimaniaData['context'], 'http', 'content', $request);
			$file = file_get_contents(self::DEDIMANIA_URL, false, $this->dedimaniaData['context']);
			
			// Handle response
			$response = $this->decode($file);
			if (is_array($response)) {
				foreach ($response as $methodResponse) {
					if (xmlrpc_is_fault($methodResponse)) {
						$this->handleXmlRpcFault($methodResponse);
					}
				}
			}
			else {
				if (!$response) {
					trigger_error('XmlRpc Error.');
					var_dump($response);
				}
			}
		}
	}

	/**
	 * Handle BeginMap callback
	 */
	public function handleMapBegin($callback) {
		// Update map
		$this->mapInfo = $this->getMapInfo();
		
		if ($this->settings->local_records_enabled) {
			// Update local records
			$this->updateManialinks[self::MLID_LOCAL] = true;
		}
		
		if ($this->settings->dedimania_enabled) {
			// Update dedimania records
			$this->fetchDedimaniaRecords(true);
			$this->updateManialinks[self::MLID_DEDI] = true;
		}
	}

	/**
	 * Build map info struct for dedimania requests
	 */
	private function getMapInfo() {
		$map = $this->mc->server->getMap();
		if (!$map) return null;
		$mapInfo = array();
		$mapInfo['UId'] = $map['UId'];
		$mapInfo['Name'] = $map['Name'];
		$mapInfo['Author'] = $map['Author'];
		$mapInfo['Environment'] = $map['Environnement'];
		$mapInfo['NbCheckpoints'] = $map['NbCheckpoints'];
		$mapInfo['NbLaps'] = $map['NbLaps'];
		return $mapInfo;
	}

	/**
	 * Handle EndMap callback
	 */
	public function handleMapEnd($callback) {
		if ($this->settings->dedimania_enabled) {
			// Send dedimania records
			$gameMode = $this->getGameModeString();
			$times = array();
			$replays = array();
			foreach ($this->dedimaniaData['records']['Records'] as $record) {
				if (!isset($record['New']) || !$record['New']) continue;
				array_push($times, array('Login' => $record['Login'], 'Best' => $record['Best'], 'Checks' => $record['Checks']));
				if (!isset($replays['VReplay'])) {
					$replays['VReplay'] = $record['VReplay'];
				}
				if (!isset($replays['Top1GReplay']) && isset($record['Top1GReplay'])) {
					$replays['Top1GReplay'] = $record['Top1GReplay'];
				}
				// TODO: VReplayChecks
			}
			if (!isset($replays['VReplay'])) $replays['VReplay'] = '';
			if (!isset($replays['VReplayChecks'])) $replays['VReplayChecks'] = '';
			if (!isset($replays['Top1GReplay'])) $replays['Top1GReplay'] = '';
			
			xmlrpc_set_type($replays['VReplay'], 'base64');
			xmlrpc_set_type($replays['Top1GReplay'], 'base64');
			
			$data = array($this->dedimaniaData['sessionId'], $this->mapInfo, $gameMode, $times, $replays);
			$request = $this->encode_request(self::DEDIMANIA_SETCHALLENGETIMES, $data);
			stream_context_set_option($this->dedimaniaData['context'], 'http', 'content', $request);
			$file = file_get_contents(self::DEDIMANIA_URL, false, $this->dedimaniaData['context']);
			
			// Handle response
			$response = $this->decode($file);
			if (is_array($response)) {
				foreach ($response as $index => $methodResponse) {
					if (xmlrpc_is_fault($methodResponse)) {
						$this->handleXmlRpcFault($methodResponse);
					}
					else {
						if ($index <= 0) {
							// Called method response
							$responseData = $methodResponse[0];
							if (!$responseData) {
								trigger_error("Records Plugin: Submitting dedimania records failed.");
							}
							continue;
						}
						
						// Warnings and TTR
						$errors = $methodResponse[0]['methods'][0]['errors'];
						if ($errors) {
							trigger_error($errors);
						}
					}
				}
			}
		}
	}

	/**
	 * Get current checkpoint string for dedimania record
	 *
	 * @param string $login        	
	 * @return string
	 */
	private function getChecks($login) {
		if (!$login || !isset($this->checkpoints[$login])) return null;
		$string = '';
		$count = count($this->checkpoints[$login]);
		foreach ($this->checkpoints[$login] as $index => $check) {
			$string .= $check;
			if ($index < $count - 1) $string .= ',';
		}
		return $string;
	}

	/**
	 * Build server info struct for callbacks
	 */
	private function getSrvInfo() {
		$server = $this->mc->server->getOptions();
		if (!$server) return null;
		$client = null;
		$players = null;
		$spectators = null;
		$this->mc->server->getPlayers($client, $players, $spectators);
		if (!is_array($players) || !is_array($spectators)) return null;
		return array('SrvName' => $server['Name'], 'Comment' => $server['Comment'], 'Private' => (strlen($server['Password']) > 0), 
			'NumPlayers' => count($players), 'MaxPlayers' => $server['CurrentMaxPlayers'], 'NumSpecs' => count($spectators), 
			'MaxSpecs' => $server['CurrentMaxSpectators']);
	}

	/**
	 * Build simple player list for callbacks
	 */
	private function getPlayerList($votes = false) {
		$client = null;
		$players;
		$spectators;
		$allPlayers = $this->mc->server->getPlayers($client, $players, $spectators);
		if (!is_array($players) || !is_array($spectators)) return null;
		$playerInfo = array();
		foreach ($allPlayers as $player) {
			array_push($playerInfo, array('Login' => $player['Login'], 'IsSpec' => in_array($player, $spectators)));
		}
		return $playerInfo;
	}

	/**
	 * Get dedi string representation of the current game mode
	 */
	private function getGameModeString() {
		$gameMode = $this->mc->server->getGameMode();
		if ($gameMode === null) {
			trigger_error("Couldn't retrieve game mode. " . $this->mc->getClientErrorText());
			return null;
		}
		switch ($gameMode) {
			case 1:
			case 3:
			case 5:
				{
					return 'Rounds';
				}
			case 2:
			case 4:
				{
					return 'TA';
				}
		}
		return null;
	}

	/**
	 * Build votes info struct for callbacks
	 */
	private function getVotesInfo() {
		$map = $this->mc->server->getMap();
		if (!$map) return null;
		$gameMode = $this->getGameModeString();
		if (!$gameMode) return null;
		return array('UId' => $map['UId'], 'GameMode' => $gameMode);
	}

	/**
	 * Handle 3Minute callback
	 */
	public function handle3Minute($callback = null) {
		if ($this->settings->dedimania_enabled) {
			// Update dedimania players
			$servInfo = $this->getSrvInfo();
			$votesInfo = $this->getVotesInfo();
			$playerList = $this->getPlayerList(true);
			if ($servInfo && $votesInfo && $playerList) {
				$data = array($this->dedimaniaData['sessionId'], $servInfo, $votesInfo, $playerList);
				$request = $this->encode_request(self::DEDIMANIA_UPDATESERVERPLAYERS, $data);
				stream_context_set_option($this->dedimaniaData['context'], 'http', 'content', $request);
				$file = file_get_contents(self::DEDIMANIA_URL, false, $this->dedimaniaData['context']);
				
				// Handle response
				$response = $this->decode($file);
				if (is_array($response)) {
					foreach ($response as $methodResponse) {
						if (xmlrpc_is_fault($methodResponse)) {
							$this->handleXmlRpcFault($methodResponse);
						}
					}
				}
				else if (!$response) {
					trigger_error('XmlRpc Error.');
					var_dump($response);
				}
			}
		}
	}

	/**
	 * Handle PlayerCheckpoint callback
	 */
	public function handlePlayerCheckpoint($callback) {
		$data = $callback[1];
		$login = $data[1];
		$time = $data[2];
		$lap = $data[3];
		$cpIndex = $data[4];
		if (!isset($this->checkpoints[$login]) || $cpIndex <= 0) $this->checkpoints[$login] = array();
		$this->checkpoints[$login][$cpIndex] = $time;
	}

	/**
	 * Handle PlayerFinish callback
	 */
	public function handlePlayerFinish($callback) {
		$data = $callback[1];
		if ($data[0] <= 0 || $data[2] <= 0) return;
		
		$login = $data[1];
		$time = $data[2];
		$newMap = $this->mc->server->getMap();
		if (!$newMap) return;
		if (!$this->mapInfo || $this->mapInfo['UId'] !== $newMap['UId']) {
			$this->mapInfo = $this->getMapInfo();
		}
		$map = $newMap;
		
		$player = $this->mc->server->getPlayer($login);
		
		if ($this->settings->local_records_enabled) {
			// Get old record of the player
			$oldRecord = $this->getLocalRecord($map['UId'], $login);
			$save = true;
			if ($oldRecord) {
				if ($oldRecord['time'] < $time) {
					// Not improved
					$save = false;
				}
				else if ($oldRecord['time'] == $time) {
					// Same time
					$message = '$<' . $player['NickName'] . '$> equalized her/his $<$o' . $oldRecord['rank'] . '.$> Local Record: ' .
							 Tools::formatTime($oldRecord['time']);
					$this->mc->chat->sendInformation($message);
					$save = false;
				}
			}
			if ($save) {
				// Save time
				$database = $this->mc->database;
				$query = "INSERT INTO `" . self::TABLE_RECORDS . "` (
					`mapUId`,
					`Login`,
					`time`
					) VALUES (
					'" . $database->escape($map['UId']) . "',
					'" . $database->escape($login) . "',
					" . $time . "
					) ON DUPLICATE KEY UPDATE
					`time` = VALUES(`time`);";
				if (!$database->query($query)) {
					trigger_error("Couldn't save player record. " . $database->mysqli->error);
				}
				else {
					// Announce record
					$newRecord = $this->getLocalRecord($map['UId'], $login);
					if ($oldRecord == null || $newRecord['rank'] < $oldRecord['rank']) {
						// Gained rank
						$improvement = 'gained the';
					}
					else {
						// Only improved time
						$improvement = 'improved her/his';
					}
					$message = '$<' . $player['NickName'] . '$> ' . $improvement . ' $<$o' . $newRecord['rank'] . '.$> Local Record: ' .
							 Tools::formatTime($newRecord['time']);
					$this->mc->chat->sendInformation($message);
					$this->updateManialinks[self::MLID_LOCAL] = true;
				}
			}
		}
		
		if ($this->settings->dedimania_enabled) {
			// Get old record of the player
			$oldRecord = $this->getDediRecord($login);
			$save = true;
			if ($oldRecord) {
				if ($oldRecord['Best'] < $time) {
					// Not improved
					$save = false;
				}
				else if ($oldRecord['Best'] == $time) {
					// Same time
					$save = false;
				}
			}
			if ($save) {
				// Save time
				$newRecord = array('Login' => $login, 'NickName' => $player['NickName'], 'Best' => $data[2], 
					'Checks' => $this->getChecks($login), 'New' => true);
				$inserted = $this->insertDediRecord($newRecord, $oldRecord);
				if ($inserted) {
					// Get newly saved record
					foreach ($this->dedimaniaData['records']['Records'] as $key => &$record) {
						if ($record['Login'] !== $newRecord['Login']) continue;
						$newRecord = $record;
						break;
					}
					
					// Announce record
					if (!$oldRecord || $newRecord['Rank'] < $oldRecord['Rank']) {
						// Gained rank
						$improvement = 'gained the';
					}
					else {
						// Only improved time
						$improvement = 'improved her/his';
					}
					$message = '$<' . $player['NickName'] . '$> ' . $improvement . ' $<$o' . $newRecord['Rank'] .
							 '.$> Dedimania Record: ' . Tools::formatTime($newRecord['Best']);
					$this->mc->chat->sendInformation($message);
					$this->updateManialinks[self::MLID_DEDI] = true;
				}
			}
		}
	}

	/**
	 * Get max rank for given login
	 */
	private function getMaxRank($login) {
		if (!isset($this->dedimaniaData['records'])) return null;
		$records = $this->dedimaniaData['records'];
		$maxRank = $records['ServerMaxRank'];
		foreach ($records['Players'] as $player) {
			if ($player['Login'] === $login) {
				if ($player['MaxRank'] > $maxRank) $maxRank = $player['MaxRank'];
				break;
			}
		}
		return $maxRank;
	}

	/**
	 * Inserts the given new dedimania record at the proper position
	 *
	 * @param struct $newRecord        	
	 * @return bool
	 */
	private function insertDediRecord(&$newRecord, $oldRecord) {
		if (!$newRecord || !isset($this->dedimaniaData['records']) || !isset($this->dedimaniaData['records']['Records'])) return false;
		
		$insert = false;
		$newRecords = array();
		
		// Get max possible rank
		$maxRank = $this->getMaxRank($newRecord['Login']);
		if (!$maxRank) $maxRank = 30;
		
		// Loop through existing records
		foreach ($this->dedimaniaData['records']['Records'] as $key => &$record) {
			if ($record['Rank'] > $maxRank) {
				// Max rank reached
				return false;
			}
			
			if ($record['Login'] === $newRecord['Login']) {
				// Old record of the same player
				if ($record['Best'] <= $newRecord['Best']) {
					// It's better - Do nothing
					return false;
				}
				
				// Replace old record
				unset($this->dedimaniaData['records']['Records'][$key]);
				$insert = true;
				break;
			}
			
			// Other player's record
			if ($record['Best'] <= $newRecord['Best']) {
				// It's better - Skip
				continue;
			}
			
			// New record is better - Insert it
			$insert = true;
			if ($oldRecord) {
				// Remove old record
				foreach ($this->dedimaniaData['records']['Records'] as $key2 => $record2) {
					if ($record2['Login'] !== $oldRecord['Login']) continue;
					unset($this->dedimaniaData['records']['Records'][$key2]);
					break;
				}
			}
			break;
		}
		
		if (!$insert && count($this->dedimaniaData['records']['Records']) < $maxRank) {
			// Records list not full - Append new record
			$insert = true;
		}
		
		if ($insert) {
			// Insert new record
			array_push($this->dedimaniaData['records']['Records'], $newRecord);
			
			// Update ranks
			$this->updateDediRecordRanks();
			
			// Save replays
			foreach ($this->dedimaniaData['records']['Records'] as $key => &$record) {
				if ($record['Login'] !== $newRecord['Login']) continue;
				$this->setRecordReplays($record);
				break;
			}
			
			// Record inserted
			return true;
		}
		
		// No new record
		return false;
	}

	/**
	 * Updates the replay values for the given record
	 *
	 * @param struct $record        	
	 */
	private function setRecordReplays(&$record) {
		if (!$record || !$this->settings->dedimania_enabled) return;
		
		// Set validation replay
		$validationReplay = $this->mc->server->getValidationReplay($record['Login']);
		if ($validationReplay) $record['VReplay'] = $validationReplay;
		
		// Set ghost replay
		if ($record['Rank'] <= 1) {
			$dataDirectory = $this->mc->server->getDataDirectory();
			if (!isset($this->dedimaniaData['directoryAccessChecked'])) {
				$access = $this->mc->server->checkAccess($dataDirectory);
				if (!$access) {
					trigger_error("No access to the servers data directory. Can't retrieve ghost replays.");
				}
				$this->dedimaniaData['directoryAccessChecked'] = $access;
			}
			if ($this->dedimaniaData['directoryAccessChecked']) {
				$ghostReplay = $this->mc->server->getGhostReplay($record['Login']);
				if ($ghostReplay) $record['Top1GReplay'] = $ghostReplay;
			}
		}
	}

	/**
	 * Update the sorting and the ranks of all dedimania records
	 */
	private function updateDediRecordRanks() {
		if (!isset($this->dedimaniaData['records']) || !isset($this->dedimaniaData['records']['Records'])) return;
		
		// Sort records
		usort($this->dedimaniaData['records']['Records'], array($this, 'compareRecords'));
		
		// Update ranks
		$rank = 1;
		foreach ($this->dedimaniaData['records']['Records'] as &$record) {
			$record['Rank'] = $rank;
			$rank++;
		}
	}

	/**
	 * Compare function for sorting dedimania records
	 *
	 * @param struct $first        	
	 * @param struct $second        	
	 * @return int
	 */
	private function compareRecords($first, $second) {
		if ($first['Best'] < $second['Best']) {
			return -1;
		}
		else if ($first['Best'] > $second['Best']) {
			return 1;
		}
		else {
			if ($first['Rank'] < $second['Rank']) {
				return -1;
			}
			else {
				return 1;
			}
		}
	}

	/**
	 * Get the dedimania record of the given login
	 *
	 * @param string $login        	
	 * @return struct
	 */
	private function getDediRecord($login) {
		if (!isset($this->dedimaniaData['records'])) return null;
		$records = $this->dedimaniaData['records']['Records'];
		foreach ($records as $index => $record) {
			if ($record['Login'] === $login) return $record;
		}
		return null;
	}

	/**
	 * Send manialink to clients
	 */
	private function sendManialink($manialink, $login = null) {
		if (!$manialink || !$this->mc->client) return;
		if (!$login) {
			if (!$this->mc->client->query('SendDisplayManialinkPage', $manialink->asXML(), 0, false)) {
				trigger_error("Couldn't send manialink to players. " . $this->mc->getClientErrorText());
			}
		}
		else {
			if (!$this->mc->client->query('SendDisplayManialinkPageToLogin', $login, $manialink->asXML(), 0, false)) {
				trigger_error("Couldn't send manialink to player '" . $login . "'. " . $this->mc->getClientErrorText());
			}
		}
	}

	/**
	 * Handle ClientUpdated callback
	 *
	 * @param mixed $data        	
	 */
	public function handleClientUpdated($data) {
		$this->openDedimaniaSession(true);
		if (isset($this->updateManialinks[self::MLID_LOCAL])) $this->updateManialinks[self::MLID_LOCAL] = true;
		if (isset($this->updateManialinks[self::MLID_DEDI])) $this->updateManialinks[self::MLID_DEDI] = true;
	}

	/**
	 * Update local records manialink
	 */
	private function buildLocalManialink() {
		$map = $this->mc->server->getMap();
		if (!$map) {
			return null;
		}
		
		$pos_x = (float) $this->config->local_records->widget->pos_x;
		$pos_y = (float) $this->config->local_records->widget->pos_y;
		$title = (string) $this->config->local_records->widget->title;
		$width = (float) $this->config->local_records->widget->width;
		$lines = (int) $this->config->local_records->widget->lines;
		$line_height = (float) $this->config->local_records->widget->line_height;
		
		$recordResult = $this->getLocalRecords($map['UId']);
		if (!$recordResult) {
			trigger_error("Couldn't fetch player records.");
			return null;
		}
		
		$xml = Tools::newManialinkXml(self::MLID_LOCAL);
		
		$frame = $xml->addChild('frame');
		$frame->addAttribute('posn', $pos_x . ' ' . $pos_y);
		
		// Background
		$quad = $frame->addChild('quad');
		Tools::addAlignment($quad, 'center', 'top');
		$quad->addAttribute('sizen', ($width * 1.05) . ' ' . (7. + $lines * $line_height));
		$quad->addAttribute('style', 'Bgs1InRace');
		$quad->addAttribute('substyle', 'BgTitleShadow');
		
		// Title
		$label = $frame->addChild('label');
		Tools::addAlignment($label);
		Tools::addTranslate($xml);
		$label->addAttribute('posn', '0 ' . ($line_height * -0.9));
		$label->addAttribute('sizen', $width . ' 0');
		$label->addAttribute('style', 'TextTitle1');
		$label->addAttribute('textsize', '2');
		$label->addAttribute('text', $title);
		
		// Times
		$index = 0;
		while ($record = $recordResult->fetch_assoc()) {
			$y = -8. - $index * $line_height;
			
			$recordFrame = $frame->addChild('frame');
			$recordFrame->addAttribute('posn', '0 ' . $y);
			
			// Background
			$quad = $recordFrame->addChild('quad');
			Tools::addAlignment($quad);
			$quad->addAttribute('sizen', $width . ' ' . $line_height);
			$quad->addAttribute('style', 'Bgs1InRace');
			$quad->addAttribute('substyle', 'BgTitleGlow');
			
			// Rank
			$label = $recordFrame->addChild('label');
			Tools::addAlignment($label, 'left');
			$label->addAttribute('posn', ($width * -0.47) . ' 0');
			$label->addAttribute('sizen', ($width * 0.06) . ' ' . $line_height);
			$label->addAttribute('textsize', '1');
			$label->addAttribute('textprefix', '$o');
			$label->addAttribute('text', $record['rank']);
			
			// Name
			$label = $recordFrame->addChild('label');
			Tools::addAlignment($label, 'left');
			$label->addAttribute('posn', ($width * -0.4) . ' 0');
			$label->addAttribute('sizen', ($width * 0.6) . ' ' . $line_height);
			$label->addAttribute('textsize', '1');
			$label->addAttribute('text', $record['NickName']);
			
			// Time
			$label = $recordFrame->addChild('label');
			Tools::addAlignment($label, 'right');
			$label->addAttribute('posn', ($width * 0.47) . ' 0');
			$label->addAttribute('sizen', ($width * 0.25) . ' ' . $line_height);
			$label->addAttribute('textsize', '1');
			$label->addAttribute('text', Tools::formatTime($record['time']));
			
			$index++;
		}
		
		return $xml;
	}

	/**
	 * Update dedimania records manialink
	 */
	private function buildDedimaniaManialink() {
		if (!isset($this->dedimaniaData['records'])) {
			return;
		}
		$records = $this->dedimaniaData['records']['Records'];
		
		$pos_x = (float) $this->config->dedimania_records->widget->pos_x;
		$pos_y = (float) $this->config->dedimania_records->widget->pos_y;
		$title = (string) $this->config->dedimania_records->widget->title;
		$width = (float) $this->config->dedimania_records->widget->width;
		$lines = (int) $this->config->dedimania_records->widget->lines;
		$line_height = (float) $this->config->dedimania_records->widget->line_height;
		
		$xml = Tools::newManialinkXml(self::MLID_DEDI);
		
		$frame = $xml->addChild('frame');
		$frame->addAttribute('posn', $pos_x . ' ' . $pos_y);
		
		// Background
		$quad = $frame->addChild('quad');
		Tools::addAlignment($quad, 'center', 'top');
		$quad->addAttribute('sizen', ($width * 1.05) . ' ' . (7. + $lines * $line_height));
		$quad->addAttribute('style', 'Bgs1InRace');
		$quad->addAttribute('substyle', 'BgTitleShadow');
		
		// Title
		$label = $frame->addChild('label');
		Tools::addAlignment($label);
		Tools::addTranslate($xml);
		$label->addAttribute('posn', '0 ' . ($line_height * -0.9));
		$label->addAttribute('sizen', $width . ' 0');
		$label->addAttribute('style', 'TextTitle1');
		$label->addAttribute('textsize', '2');
		$label->addAttribute('text', $title);
		
		// Times
		foreach ($records as $index => $record) {
			$y = -8. - $index * $line_height;
			
			$recordFrame = $frame->addChild('frame');
			$recordFrame->addAttribute('posn', '0 ' . $y);
			
			// Background
			$quad = $recordFrame->addChild('quad');
			Tools::addAlignment($quad);
			$quad->addAttribute('sizen', $width . ' ' . $line_height);
			$quad->addAttribute('style', 'Bgs1InRace');
			$quad->addAttribute('substyle', 'BgTitleGlow');
			
			// Rank
			$label = $recordFrame->addChild('label');
			Tools::addAlignment($label, 'left');
			$label->addAttribute('posn', ($width * -0.47) . ' 0');
			$label->addAttribute('sizen', ($width * 0.06) . ' ' . $line_height);
			$label->addAttribute('textsize', '1');
			$label->addAttribute('textprefix', '$o');
			$label->addAttribute('text', $record['Rank']);
			
			// Name
			$label = $recordFrame->addChild('label');
			Tools::addAlignment($label, 'left');
			$label->addAttribute('posn', ($width * -0.4) . ' 0');
			$label->addAttribute('sizen', ($width * 0.6) . ' ' . $line_height);
			$label->addAttribute('textsize', '1');
			$label->addAttribute('text', $record['NickName']);
			
			// Time
			$label = $recordFrame->addChild('label');
			Tools::addAlignment($label, 'right');
			$label->addAttribute('posn', ($width * 0.47) . ' 0');
			$label->addAttribute('sizen', ($width * 0.25) . ' ' . $line_height);
			$label->addAttribute('textsize', '1');
			$label->addAttribute('text', Tools::formatTime($record['Best']));
			
			if ($index >= $lines - 1) break;
		}
		
		return $xml;
	}

	/**
	 * Fetch local records for the given map
	 *
	 * @param string $mapUId        	
	 * @param int $limit        	
	 * @return array
	 */
	private function getLocalRecords($mapUId, $limit = -1) {
		$query = "SELECT * FROM (
				SELECT recs.*, @rank := @rank + 1 as `rank` FROM `" . self::TABLE_RECORDS . "` recs, (SELECT @rank := 0) ra
				WHERE recs.`mapUId` = '" . $this->mc->database->escape($mapUId) . "'
				ORDER BY recs.`time` ASC
				" . ($limit > 0 ? "LIMIT " . $limit : "") . ") records
			LEFT JOIN `" . Database::TABLE_PLAYERS . "` players
			ON records.`Login` = players.`Login`;";
		return $this->mc->database->query($query);
	}

	/**
	 * Retrieve the local record for the given map and login
	 *
	 * @param string $mapUId        	
	 * @param string $login        	
	 * @return array
	 */
	private function getLocalRecord($mapUId, $login) {
		if (!$mapUId || !$login) return null;
		$database = $this->mc->database;
		$query = "SELECT records.* FROM (
			SELECT recs.*, @rank := @rank + 1 as `rank` FROM `" . self::TABLE_RECORDS . "` `recs`, (SELECT @rank := 0) r
			WHERE recs.`mapUid` = '" . $database->escape($mapUId) . "'
			ORDER BY recs.`time` ASC) `records`
			WHERE records.`Login` = '" . $database->escape($login) . "';";
		$result = $database->query($query);
		if (!$result || !is_object($result)) {
			trigger_error("Couldn't retrieve player record for '" . $login . "'." . $database->mysqli->error);
			return null;
		}
		while ($record = $result->fetch_assoc()) {
			return $record;
		}
		return null;
	}

	/**
	 * Encodes the given xml rpc method and params
	 *
	 * @param string $method        	
	 * @param array $params        	
	 * @return string
	 */
	private function encode_request($method, $params) {
		$paramArray = array(array('methodName' => $method, 'params' => $params), 
			array('methodName' => self::DEDIMANIA_WARNINGSANDTTR2, 'params' => array()));
		return xmlrpc_encode_request(self::XMLRPC_MULTICALL, array($paramArray), array('encoding' => 'UTF-8', 'escaping' => 'markup'));
	}

	/**
	 * Decodes xml rpc response
	 *
	 * @param string $response        	
	 * @return mixed
	 */
	private function decode($response) {
		return xmlrpc_decode($response, 'utf-8');
	}

	/**
	 * Handles xml rpc fault
	 *
	 * @param struct $fault        	
	 */
	private function handleXmlRpcFault($fault) {
		trigger_error('XmlRpc Fault: ' . $fault['faultString'] . ' (' . $fault['faultCode'] . ')');
	}
}

?>
