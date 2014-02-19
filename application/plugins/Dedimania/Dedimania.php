<?php
namespace Dedimania;

require_once "DedimaniaData.php";
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\Formatter;
use ManiaControl\ManiaControl;
use ManiaControl\Maps\MapManager;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;
use ManiaControl\Plugins\Plugin;

class Dedimania implements CallbackListener, TimerListener, Plugin {
	/**
	 * Constants
	 */
	const ID                            = 100;
	const VERSION                       = 0.1;
	const MLID_DEDIMANIA                = 'Dedimania.ManialinkId';
	const XMLRPC_MULTICALL              = 'system.multicall';
	const DEDIMANIA_URL                 = 'http://dedimania.net:8081/Dedimania';
	const DEDIMANIA_OPENSESSION         = 'dedimania.OpenSession';
	const DEDIMANIA_CHECKSESSION        = 'dedimania.CheckSession';
	const DEDIMANIA_GETRECORDS          = 'dedimania.GetChallengeRecords';
	const DEDIMANIA_PLAYERCONNECT       = 'dedimania.PlayerConnect';
	const DEDIMANIA_PLAYERDISCONNECT    = 'dedimania.PlayerDisconnect';
	const DEDIMANIA_UPDATESERVERPLAYERS = 'dedimania.UpdateServerPlayers';
	const DEDIMANIA_SETCHALLENGETIMES   = 'dedimania.SetChallengeTimes';
	const DEDIMANIA_WARNINGSANDTTR2     = 'dedimania.WarningsAndTTR2';
	const USE_COMPRESSION               = false;

	/**
	 * Private Properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;
	/** @var DedimaniaData $dedimaniaData */
	private $dedimaniaData = null;
	private $manialink = null;
	//private $lastSendManialink = array();
	private $updateManialink = false;

	/**
	 * Prepares the Plugin
	 *
	 * @param ManiaControl $maniaControl
	 * @return mixed
	 */
	public static function prepare(ManiaControl $maniaControl) {
		// TODO: Implement prepare() method.
	}

	/**
	 * Load the plugin
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 * @return bool
	 */
	public function load(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		return;

		$this->maniaControl->callbackManager->registerCallbackListener(MapManager::CB_BEGINMAP, $this, 'handleBeginMap');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERCONNECT, $this, 'handlePlayerConnect');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERCONNECT, $this, 'handlePlayerDisconnect');
		$this->maniaControl->timerManager->registerTimerListening($this, 'updateEverySecond', 1000);
		$this->maniaControl->timerManager->registerTimerListening($this, 'handleEveryMinute', 1000 * 60);
		//TODO parse settings

		// Open session
		$serverInfo          = $this->maniaControl->server->getInfo();
		$serverVersion       = $this->maniaControl->client->getVersion();
		$packMask            = substr($this->maniaControl->server->titleId, 2);
		$this->dedimaniaData = new DedimaniaData(".paragoncanyon", "a3ee654ac8", $serverInfo->path, $packMask, $serverVersion);

		$this->openDedimaniaSession();
	}

	/**
	 * Opens the Dedimania Session
	 */
	private function openDedimaniaSession() {
		$content = $this->encode_request(self::DEDIMANIA_OPENSESSION, array($this->dedimaniaData->toArray()));

		$this->maniaControl->fileReader->postData(self::DEDIMANIA_URL, function ($data, $error) {
			$this->maniaControl->log("Try to connect on Dedimania");

			if ($error != '') {
				$this->maniaControl->log("Dedimania Error: " . $error);
			}

			$data = $this->decode($data);
			if (is_array($data)) {
				foreach($data as $index => $methodResponse) {
					if (xmlrpc_is_fault($methodResponse)) {
						$this->handleXmlRpcFault($methodResponse);
					} else if ($index <= 0) {
						$responseData                   = $methodResponse[0];
						$this->dedimaniaData->sessionId = $responseData['SessionId'];
						if ($this->dedimaniaData->sessionId != '') {
							$this->maniaControl->log("Dedimania connection successfully established.");
							$this->fetchDedimaniaRecords();
						} else {
							$this->maniaControl->log("Error while opening Dedimania Connection");
						}
					}
				}
			}
		}, $content, true);
	}

	/**
	 * Handle 1Second callback
	 */
	public function updateEverySecond($time) {
		$this->updateManialink = false;
		//TODO send manialink
	}

	/**
	 * Check if the session is alive every minute
	 *
	 * @param null $callback
	 */
	public function handleEveryMinute($callback = null) {
		$this->checkDedimaniaSession();
	}


	/**
	 * Handle PlayerConnect callback
	 *
	 * @param $callback
	 */
	public function handlePlayerConnect($callback) {
		/** @var Player $player */
		$player = $callback[1];
		if (!$player) {
			return;
		}

		// Send Dedimania request
		$data    = array($this->dedimaniaData->sessionId, $player->login, $player->nickname, $player->path, $player->isSpectator);
		$content = $this->encode_request(self::DEDIMANIA_PLAYERCONNECT, $data);

		$this->maniaControl->fileReader->postData(self::DEDIMANIA_URL, function ($data, $error) {
			if ($error != '') {
				$this->maniaControl->log("Dedimania Error: " . $error);
			}

			$data = $this->decode($data);
			if (is_array($data)) {
				foreach($data as $methodResponse) {
					if (xmlrpc_is_fault($methodResponse)) {
						$this->handleXmlRpcFault($methodResponse);
					}
				}
			} else {
				if (!$data) {
					trigger_error('XmlRpc Error.');
					var_dump($data);
				}
			}
			return true;
		}, $content, true);

		//TODO send manialink to login
	}

	/**
	 * Handle PlayerDisconnect callback
	 *
	 * @param $callback
	 */
	public function handlePlayerDisconnect($callback) {
		/** @var Player $player */
		$player = $callback[1];
		if (!$player) {
			return;
		}

		// Send Dedimania request
		$data    = array($this->dedimaniaData->sessionId, $player->login, '');
		$content = $this->encode_request(self::DEDIMANIA_PLAYERDISCONNECT, $data);

		$this->maniaControl->fileReader->postData(self::DEDIMANIA_URL, function ($data, $error) {
			if ($error != '') {
				$this->maniaControl->log("Dedimania Error: " . $error);
			}

			$data = $this->decode($data);
			if (is_array($data)) {
				foreach($data as $methodResponse) {
					if (xmlrpc_is_fault($methodResponse)) {
						$this->handleXmlRpcFault($methodResponse);
					}
				}
			} else {
				if (!$data) {
					trigger_error('XmlRpc Error.');
					var_dump($data);
				}
			}
			return true;
		}, $content, true);
	}

	/**
	 * Handle Begin Map
	 *
	 * @param $callback
	 */
	public function handleBeginMap($callback) {
		$this->fetchDedimaniaRecords(true);
		//TODO update manialink
	}

	public function updatePlayerList($callback) {
		$serverInfo = $this->getServerInfo();
		$playerList = $this->getPlayerList();
		$votesInfo  = $this->getVotesInfo();
		if (!$serverInfo || !$votesInfo || !$playerList) {
			return;
		}

		// Send Dedimania request
		$data    = array($this->dedimaniaData->sessionId, $serverInfo, $votesInfo, $playerList);
		$content = $this->encode_request(self::DEDIMANIA_UPDATESERVERPLAYERS, $data);

		$this->maniaControl->fileReader->postData(self::DEDIMANIA_URL, function ($data, $error) {
			if ($error != '') {
				$this->maniaControl->log("Dedimania Error: " . $error);
			}

			$data = $this->decode($data);
			if (is_array($data)) {
				foreach($data as $methodResponse) {
					if (xmlrpc_is_fault($methodResponse)) {
						$this->handleXmlRpcFault($methodResponse);
					}
				}
			} else {
				if (!$data) {
					trigger_error('XmlRpc Error.');
					var_dump($data);
				}
			}
			return true;
		}, $content, true);
	}

	/**
	 * Handle PlayerCheckpoint callback
	 */
	public function handlePlayerCheckpoint($callback) {
		//TODO ?
		/*	$data = $callback[1];
			$login = $data[1];
			$time = $data[2];
			$lap = $data[3];
			$cpIndex = $data[4];
			if (!isset($this->checkpoints[$login]) || $cpIndex <= 0) $this->checkpoints[$login] = array();
			$this->checkpoints[$login][$cpIndex] = $time;*/
	}

	public function handlePlayerFinished($callback) {
		$data = $callback[1];
		if ($data[0] <= 0 || $data[2] <= 0) {
			return;
		}

		$login = $data[1];
		$time  = $data[2];
		$map   = $this->maniaControl->mapManager->getCurrentMap();
		if (!$map) {
			return;
		}


		$oldRecord = $this->getDedimaniaRecord($login);

		$save = true;
		//TODO getcheckpoints, insertDedirecord
		if ($oldRecord) {
			if ($oldRecord['Best'] <= $time) {
				$save = false;
			}
			if ($save) {
				$player = $this->maniaControl->playerManager->getPlayer($login);
				// Save time
				$newRecord = array('Login' => $login, 'NickName' => $player->nickname, 'Best' => $data[2], 'Checks' => $this->getCheckpoints($login), 'New' => true);
				$inserted  = $this->insertDediRecord($newRecord, $oldRecord);
				if ($inserted) {
					// Get newly saved record
					foreach($this->dedimaniaData['records']['Records'] as $key => &$record) {
						if ($record['Login'] !== $newRecord['Login']) {
							continue;
						}
						$newRecord = $record;
						break;
					}

					// Announce record
					if (!$oldRecord || $newRecord['Rank'] < $oldRecord['Rank']) {
						// Gained rank
						$improvement = 'gained the';
					} else {
						// Only improved time
						$improvement = 'improved her/his';
					}
					$message = '$<' . $player['NickName'] . '$> ' . $improvement . ' $<$o' . $newRecord['Rank'] . '.$> Dedimania Record: ' . Formatter::formatTime($newRecord['Best']);
					$this->maniaControl->chat->sendInformation($message);
					$this->updateManialink = true;
				}
			}
		}
	}

	/**
	 * Fetch Dedimania Records
	 *
	 * @param bool $reset
	 */
	private function fetchDedimaniaRecords($reset = true) {
		if ($this->dedimaniaData->sessionId == '') {
			return false;
		}

		// Reset records
		if ($reset) {
			$this->dedimaniaData->records = array();
		}

		$serverInfo = $this->getServerInfo();
		$playerInfo = $this->getPlayerList();
		$mapInfo    = $this->getMapInfo();
		$gameMode   = $this->getGameModeString();

		if (!$serverInfo || !$playerInfo || !$mapInfo || !$gameMode) {
			return false;
		}

		$data    = array($this->dedimaniaData->sessionId, $mapInfo, $gameMode, $serverInfo, $playerInfo);
		$content = $this->encode_request(self::DEDIMANIA_GETRECORDS, $data);

		$this->maniaControl->fileReader->postData(self::DEDIMANIA_URL, function ($data, $error) {
			if ($error != '') {
				$this->maniaControl->log("Dedimania Error: " . $error);
			}

			$data = $this->decode($data);
			if (is_array($data)) {
				foreach($data as $index => $methodResponse) {
					if (xmlrpc_is_fault($methodResponse)) {
						$this->handleXmlRpcFault($methodResponse);
						return false;
					} else if ($index <= 0) {
						$responseData                 = $methodResponse[0];
						$this->dedimaniaData->records = $responseData;
					}
				}
			}
			$this->updateManialink = true;
			return true;
		}, $content, true);

		return true;
	}

	/**
	 * Checks If a Dedimania Session exists, if not create a new oen
	 */
	private function checkDedimaniaSession() {
		if ($this->dedimaniaData->sessionId == '') {
			$this->openDedimaniaSession();
			return;
		}

		$content = $this->encode_request(self::DEDIMANIA_CHECKSESSION, array($this->dedimaniaData->sessionId));

		$this->maniaControl->fileReader->postData(self::DEDIMANIA_URL, function ($data, $error) {
			if ($error != '') {
				$this->maniaControl->log("Dedimania Error: " . $error);
			}

			$data = $this->decode($data);
			if (is_array($data)) {
				foreach($data as $methodResponse) {
					if (xmlrpc_is_fault($methodResponse)) {
						$this->handleXmlRpcFault($methodResponse);
					} else {
						$responseData = $methodResponse[0];
						if (is_bool($responseData)) {
							if (!$responseData) {
								$this->openDedimaniaSession();
							}
						}
					}
				}
			}
		}, $content, true);
		return;
	}

	/**
	 * Inserts the given new Dedimania record at the proper position
	 *
	 * @param array $newRecord
	 * @return bool
	 */
	private function insertDedimaniaRecord(&$newRecord, $oldRecord) {
		if (!$newRecord || !isset($this->dedimaniaData->records) || !isset($this->dedimaniaData->records['Records'])) return false;

		$insert = false;
		$newRecords = array();

		// Loop through existing records
		foreach ($this->dedimaniaData->records['Records'] as $key => &$record) {
			if ($record['Login'] === $newRecord['Login']) {
				// Old record of the same player
				if ($record['Best'] <= $newRecord['Best']) {
					// It's better - Do nothing
					return false;
				}

				// Replace old record
				unset($this->dedimaniaData->records['Records'][$key]);
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
				foreach ($this->dedimaniaData->records['Records'] as $key2 => $record2) {
					if ($record2['Login'] !== $oldRecord['Login']) continue;
					unset($this->dedimaniaData->records['Records'][$key2]);
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
			//$this->updateDediRecordRanks();  //TODO

			// Save replays
			foreach ($this->dedimaniaData->records['Records'] as $key => &$record) {
				if ($record['Login'] !== $newRecord['Login']) continue;
				//$this->setRecordReplays($record); //TODO
				break;
			}

			// Record inserted
			return true;
		}

		// No new record
		return false;
	}


	/**
	 * Build server info Structure for callbacks
	 */
	private function getServerInfo() {
		$server = $this->maniaControl->client->getServerOptions();
		if (!$server) {
			return null;
		}

		if (count($this->maniaControl->playerManager->getPlayers()) == 0) {
			return null;
		}

		$playerCount    = $this->maniaControl->playerManager->getPlayerCount();
		$spectatorCount = $this->maniaControl->playerManager->getSpectatorCount();

		return array('SrvName' => $server->name, 'Comment' => $server->comment, 'Private' => (strlen($server->password) > 0), 'NumPlayers' => $playerCount, 'MaxPlayers' => $server->currentMaxPlayers, 'NumSpecs' => $spectatorCount, 'MaxSpecs' => $server->currentMaxSpectators);
	}

	/**
	 * Build simple player list for callbacks
	 */
	private function getPlayerList() {
		$client = null;

		$players = $this->maniaControl->playerManager->getPlayers();

		if (count($players) == 0) {
			return null;
		}
		$playerInfo = array();
		foreach($players as $player) {
			/** @var Player $player */
			array_push($playerInfo, array('Login' => $player->login, 'IsSpec' => $player->isSpectator));
		}
		return $playerInfo;
	}

	/**
	 * Build map info struct for dedimania requests
	 */
	private function getMapInfo() {
		$map = $this->maniaControl->mapManager->getCurrentMap();
		if (!$map) {
			return null;
		}
		$mapInfo                  = array();
		$mapInfo['UId']           = $map->uid;
		$mapInfo['Name']          = $map->name;
		$mapInfo['Author']        = $map->authorLogin;
		$mapInfo['Environment']   = $map->environment;
		$mapInfo['NbCheckpoints'] = $map->nbCheckpoints;
		$mapInfo['NbLaps']        = $map->nbLaps;
		return $mapInfo;
	}

	/**
	 * Build votes info struct for callbacks
	 */
	private function getVotesInfo() {
		$map = $this->maniaControl->mapManager->getCurrentMap();
		if (!$map) {
			return null;
		}
		$gameMode = $this->getGameModeString();
		if (!$gameMode) {
			return null;
		}
		return array('UId' => $map->uid, 'GameMode' => $gameMode);
	}

	/**
	 * Get the dedimania record of the given login
	 *
	 * @param string $login
	 * @return array $record
	 */
	private function getDedimaniaRecord($login) {
		if (!isset($this->dedimaniaData->records))
			return null;
		$records = $this->dedimaniaData->records['Records'];
		foreach($records as $index => $record) {
			if ($record['Login'] === $login)
				return $record;
		}
		return null;
	}

	/**
	 * Get Dedimania string representation of the current game mode
	 *
	 * @return String
	 */
	private function getGameModeString() {
		$gameMode = $this->maniaControl->server->getGameMode();
		if ($gameMode === null) {
			trigger_error("Couldn't retrieve game mode. ");
			return null;
		}
		switch($gameMode) {
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
	 * Encodes the given xml rpc method and params
	 *
	 * @param string $method
	 * @param array  $params
	 * @return string
	 */
	private function encode_request($method, $params) {
		$paramArray = array(array('methodName' => $method, 'params' => $params), array('methodName' => self::DEDIMANIA_WARNINGSANDTTR2, 'params' => array()));
		return xmlrpc_encode_request(self::XMLRPC_MULTICALL, array($paramArray), array('encoding' => 'UTF-8', 'escaping' => 'markup'));
	}

	/**
	 * Handles xml rpc fault
	 *
	 * @param $fault
	 */
	private function handleXmlRpcFault($fault) {
		trigger_error('XmlRpc Fault: ' . $fault['faultString'] . ' (' . $fault['faultCode'] . ')');
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
	 * Unload the plugin and its resources
	 */
	public function unload() {
		$this->maniaControl->timerManager->unregisterTimerListenings($this);
		$this->maniaControl->callbackManager->unregisterCallbackListener($this);
		unset($this->maniaControl);
	}

	/**
	 * Get plugin id
	 *
	 * @return int
	 */
	public static function getId() {
		return self::ID;
	}

	/**
	 * Get Plugin Name
	 *
	 * @return string
	 */
	public static function getName() {
		return "Dedimania Plugin";
	}

	/**
	 * Get Plugin Version
	 *
	 * @return float
	 */
	public static function getVersion() {
		return self::VERSION;
	}

	/**
	 * Get Plugin Author
	 *
	 * @return string
	 */
	public static function getAuthor() {
		return "kremsy and steeffeen";
	}

	/**
	 * Get Plugin Description
	 *
	 * @return string
	 */
	public static function getDescription() {
		return "Dedimania Plugin for Trackmania";
	}
}