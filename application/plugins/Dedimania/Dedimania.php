<?php
namespace Dedimania;

require_once "DedimaniaData.php";
use FML\Controls\Control;
use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Controls\Quad;
use FML\ManiaLink;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
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
	const SETTING_WIDGET_TITLE          = 'Widget Title';
	const SETTING_WIDGET_POSX           = 'Widget Position: X';
	const SETTING_WIDGET_POSY           = 'Widget Position: Y';
	const SETTING_WIDGET_WIDTH          = 'Widget Width';
	const SETTING_WIDGET_LINESCOUNT     = 'Widget Displayed Lines Count';
	const SETTING_WIDGET_LINEHEIGHT     = 'Widget Line Height';
	const SETTING_SERVER_LOGINS         = 'Serverlogins, splitted by ;';
	const SETTING_DEDIMANIA_CODES       = 'Dedimania Codes, splitted by ;';

	/**
	 * Private Properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;
	/** @var DedimaniaData $dedimaniaData */
	private $dedimaniaData = null;
	private $updateManialink = false;
	private $checkpoints = array();
	private $init = false;

	/**
	 * Prepares the Plugin
	 *
	 * @param ManiaControl $maniaControl
	 * @return mixed
	 */
	public static function prepare(ManiaControl $maniaControl) {
		$maniaControl->settingManager->initSetting(get_class(), self::SETTING_SERVER_LOGINS, '');
		$maniaControl->settingManager->initSetting(get_class(), self::SETTING_DEDIMANIA_CODES, '');
	}

	/**
	 * Load the plugin
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 * @return bool
	 */
	public function load(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		$this->maniaControl->settingManager->initSetting($this, self::SETTING_WIDGET_TITLE, 'Dedimania');
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_WIDGET_POSX, -139);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_WIDGET_POSY, 7);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_WIDGET_WIDTH, 40);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_WIDGET_LINEHEIGHT, 4);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_WIDGET_LINESCOUNT, 12);

		//TODO what was CB_IC_ClientUpdated?
		$this->maniaControl->callbackManager->registerCallbackListener(MapManager::CB_BEGINMAP, $this, 'handleBeginMap');
		$this->maniaControl->callbackManager->registerCallbackListener(MapManager::CB_ENDMAP, $this, 'handleMapEnd');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERCONNECT, $this, 'handlePlayerConnect');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERCONNECT, $this, 'handlePlayerDisconnect');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_TM_PLAYERCHECKPOINT, $this, 'handlePlayerCheckpoint');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_TM_PLAYERFINISH, $this, 'handlePlayerFinished');
		$this->maniaControl->timerManager->registerTimerListening($this, 'updateEverySecond', 1000);
		$this->maniaControl->timerManager->registerTimerListening($this, 'handleEveryMinute', 1000 * 60);
		$this->maniaControl->timerManager->registerTimerListening($this, 'updatePlayerList', 1000 * 60 * 3);

		// Open session
		$serverInfo    = $this->maniaControl->server->getInfo();
		$serverVersion = $this->maniaControl->client->getVersion();
		$packMask      = substr($this->maniaControl->server->titleId, 2);

		$logins = $this->maniaControl->settingManager->getSetting($this, self::SETTING_SERVER_LOGINS);
		$codes  = $this->maniaControl->settingManager->getSetting($this, self::SETTING_DEDIMANIA_CODES);

		if ($logins == '' || $codes == '') {
			throw new \Exception("No Dedimania Data Specified, check the settings!");
		}

		$logins = explode(";", $logins);
		$codes  = explode(";", $codes);

		$dedimaniaCode = "";
		foreach($logins as $key => $login) {
			if ($login == $serverInfo->login) {
				if (!isset($codes[$key])) {
					throw new \Exception("No Dedimania Code Specified, check the settings!");
				}
				$dedimaniaCode = $codes[$key];
			}
		}

		if ($dedimaniaCode == '') {
			throw new \Exception("No Valid Serverlogin Specified, check the settings!");
		}

		$this->dedimaniaData = new DedimaniaData($serverInfo->login, $dedimaniaCode, $serverInfo->path, $packMask, $serverVersion);

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
						$this->handleXmlRpcFault($methodResponse, self::DEDIMANIA_OPENSESSION);
					} else if ($index <= 0) {
						$responseData                   = $methodResponse[0];
						$this->dedimaniaData->sessionId = $responseData['SessionId'];
						if ($this->dedimaniaData->sessionId != '') {
							$this->maniaControl->log("Dedimania connection successfully established.");
							$this->fetchDedimaniaRecords();
							$this->init = true;
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
		if (!$this->updateManialink) {
			return;
		}
		var_dump($this->dedimaniaData->records);
		if (!$this->dedimaniaData->records) {
			return;
		}
		var_dump("update");

		$this->updateManialink = false;

		$manialink = $this->buildManialink();
		$this->maniaControl->manialinkManager->sendManialink($manialink);
	}

	/**
	 * Check if the session is alive every minute
	 *
	 * @param null $callback
	 */
	public function handleEveryMinute($callback = null) {
		if (!$this->init) {
			return;
		}
		$this->checkDedimaniaSession();
	}

	/**
	 * Handle PlayerConnect callback
	 *
	 * @param \ManiaControl\Players\Player $player
	 */
	public function handlePlayerConnect(Player $player) {
		// Send Dedimania request
		$data    = array($this->dedimaniaData->sessionId, $player->login, $player->nickname, $player->path, $player->isSpectator);
		$content = $this->encode_request(self::DEDIMANIA_PLAYERCONNECT, $data);

		$this->maniaControl->fileReader->postData(self::DEDIMANIA_URL, function ($data, $error) use (&$player) {
			if ($error != '') {
				$this->maniaControl->log("Dedimania Error: " . $error);
			}

			$data = $this->decode($data);
			if (is_array($data)) {
				foreach($data as $methodResponse) {
					if (xmlrpc_is_fault($methodResponse)) {
						$this->handleXmlRpcFault($methodResponse, self::DEDIMANIA_PLAYERCONNECT);
					}
				}
			} else {
				if (!$data) {
					trigger_error('XmlRpc Error.');
					var_dump($data);
				}
			}

			$manialink = $this->buildManialink();
			$this->maniaControl->manialinkManager->sendManialink($manialink, $player->login);

			return true;
		}, $content, true);
	}

	/**
	 * Handle PlayerDisconnect callback
	 *
	 * @param \ManiaControl\Players\Player $player
	 */
	public function handlePlayerDisconnect(Player $player) {
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
						$this->handleXmlRpcFault($methodResponse, self::DEDIMANIA_PLAYERDISCONNECT);
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
	}


	/**
	 * Handle EndMap callback
	 *
	 * @param $callback
	 */
	public function handleMapEnd($callback) {
		if (!$this->dedimaniaData->records) {
			return;
		}

		// Send dedimania records
		$gameMode = $this->getGameModeString();
		$times    = array();
		$replays  = array();
		foreach($this->dedimaniaData->records['Records'] as $record) {
			if (!isset($record['New']) || !$record['New']) {
				continue;
			}
			array_push($times, array('Login' => $record['Login'], 'Best' => $record['Best'], 'Checks' => $record['Checks']));
			if (!isset($replays['VReplay'])) {
				$replays['VReplay'] = $record['VReplay'];
			}
			if (!isset($replays['Top1GReplay']) && isset($record['Top1GReplay'])) {
				$replays['Top1GReplay'] = $record['Top1GReplay'];
			}
			// TODO: VReplayChecks
		}
		if (!isset($replays['VReplay'])) {
			$replays['VReplay'] = '';
		}
		if (!isset($replays['VReplayChecks'])) {
			$replays['VReplayChecks'] = '';
		}
		if (!isset($replays['Top1GReplay'])) {
			$replays['Top1GReplay'] = '';
		}

		xmlrpc_set_type($replays['VReplay'], 'base64');
		xmlrpc_set_type($replays['Top1GReplay'], 'base64');

		$data    = array($this->dedimaniaData->sessionId, $this->getMapInfo(), $gameMode, $times, $replays);
		$content = $this->encode_request(self::DEDIMANIA_SETCHALLENGETIMES, $data);

		$this->maniaControl->fileReader->postData(self::DEDIMANIA_URL, function ($data, $error) {
			if ($error != '') {
				$this->maniaControl->log("Dedimania Error: " . $error);
			}

			$data = $this->decode($data);
			if (is_array($data)) {
				foreach($data as $index => $methodResponse) {
					if (xmlrpc_is_fault($methodResponse)) {
						$this->handleXmlRpcFault($methodResponse, self::DEDIMANIA_SETCHALLENGETIMES);
					} else {
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
		}, $content, true);
	}

	/**
	 * Update the Playerlist every 3 Minutes
	 *
	 * @param $callback
	 */
	public function updatePlayerList($callback) {
		$serverInfo = $this->getServerInfo();
		$playerList = $this->getPlayerList();
		$votesInfo  = $this->getVotesInfo();
		if (!$serverInfo || !$votesInfo || !$playerList || !isset($this->dedimaniaData) || $this->dedimaniaData->sessionId == '') {
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
						$this->handleXmlRpcFault($methodResponse, self::DEDIMANIA_UPDATESERVERPLAYERS);
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
	 *
	 * @param $callback
	 */
	public function handlePlayerCheckpoint($callback) {
		$data  = $callback[1];
		$login = $data[1];
		$time  = $data[2];
		//$lap     = $data[3];
		$cpIndex = $data[4];
		if (!isset($this->checkpoints[$login]) || $cpIndex <= 0) {
			$this->checkpoints[$login] = array();
		}
		$this->checkpoints[$login][$cpIndex] = $time;
	}

	/**
	 * Plyer finished callback
	 *
	 * @param $callback
	 */
	public function handlePlayerFinished($callback) {
		//var_dump($callback);
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
		if ($oldRecord) {
			if ($oldRecord['Best'] <= $time) {
				$save = false;
			}

			if ($save) {
				$player = $this->maniaControl->playerManager->getPlayer($login);
				// Save time
				$newRecord = array('Login' => $login, 'NickName' => $player->nickname, 'Best' => $data[2], 'Checks' => $this->getCheckpoints($login), 'New' => true);
				if ($this->insertDedimaniaRecord($newRecord, $oldRecord)) {
					$this->displayRecordData($player, $oldRecord, $newRecord);
				}
			}
		}

	}

	/**
	 * Display the Record Data
	 *
	 * @param $player
	 * @param $oldRecord
	 * @param $newRecord
	 */
	private function displayRecordData($player, $oldRecord, $newRecord) {
		// Get newly saved record
		foreach($this->dedimaniaData['records']['Records'] as &$record) {
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
						$this->handleXmlRpcFault($methodResponse, self::DEDIMANIA_GETRECORDS);
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
						$this->handleXmlRpcFault($methodResponse, self::DEDIMANIA_CHECKSESSION);
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
		if (!$newRecord || !$this->dedimaniaData->records || !isset($this->dedimaniaData->records['Records'])) {
			return false;
		}

		$insert = false;

		// Get max possible rank
		$maxRank = $this->getMaxRank($newRecord['Login']);
		if (!$maxRank) {
			$maxRank = 30;
		}

		// Loop through existing records
		foreach($this->dedimaniaData->records['Records'] as $key => &$record) {
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
				foreach($this->dedimaniaData->records['Records'] as $key2 => $record2) {
					if ($record2['Login'] !== $oldRecord['Login']) {
						continue;
					}
					unset($this->dedimaniaData->records['Records'][$key2]);
					break;
				}
			}
			break;
		}

		if (!$insert && count($this->dedimaniaData->records['Records']) < $maxRank) {
			// Records list not full - Append new record
			$insert = true;
		}

		if ($insert) {
			// Insert new record
			array_push($this->dedimaniaData->records['Records'], $newRecord);

			// Update ranks
			$this->updateDedimaniaRecordRanks();

			// Save replays
			foreach($this->dedimaniaData->records['Records'] as &$record) {
				if ($record['Login'] !== $newRecord['Login']) {
					continue;
				}
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
	 * Update the sorting and the ranks of all dedimania records
	 */
	private function updateDedimaniaRecordRanks() {
		if (!$this->dedimaniaData->records|| !isset($this->dedimaniaData->records['Records'])) {
			return;
		}

		// Sort records
		usort($this->dedimaniaData->records['Records'], array($this, 'compareRecords'));

		// Update ranks
		$rank = 1;
		foreach($this->dedimaniaData->records['Records'] as &$record) {
			$record['Rank'] = $rank;
			$rank++;
		}
	}

	/**
	 * Compare function for sorting dedimania records
	 *
	 * @param array $first
	 * @param array $second
	 * @return int
	 */
	private function compareRecords($first, $second) {
		if ($first['Best'] < $second['Best']) {
			return -1;
		} else if ($first['Best'] > $second['Best']) {
			return 1;
		} else {
			if ($first['Rank'] < $second['Rank']) {
				return -1;
			} else {
				return 1;
			}
		}
	}

	/**
	 * Updates the replay values for the given record
	 *
	 * @param array $record
	 */
	private function setRecordReplays(&$record) {
		// Set validation replay
		$validationReplay = $this->maniaControl->server->getValidationReplay($record['Login']);
		if ($validationReplay) {
			$record['VReplay'] = $validationReplay;
		}

		// Set ghost replay
		if ($record['Rank'] <= 1) {
			$dataDirectory = $this->maniaControl->server->getDataDirectory();
			if (!isset($this->dedimaniaData->directoryAccessChecked)) {
				$access = $this->maniaControl->server->checkAccess($dataDirectory);
				if (!$access) {
					trigger_error("No access to the servers data directory. Can't retrieve ghost replays.");
				}
				$this->dedimaniaData->directoryAccessChecked = $access;
			}
			if ($this->dedimaniaData->directoryAccessChecked) {
				$ghostReplay = $this->maniaControl->server->getGhostReplay($record['Login']);
				if ($ghostReplay) {
					$record['Top1GReplay'] = $ghostReplay;
				}
			}
		}
	}

	/**
	 * Get max rank for given login
	 */
	private function getMaxRank($login) {
		if (!isset($this->dedimaniaData->records)) {
			return null;
		}
		$records = $this->dedimaniaData->records;
		$maxRank = $records['ServerMaxRank'];
		foreach($records['Players'] as $player) {
			if ($player['Login'] === $login) {
				if ($player['MaxRank'] > $maxRank) {
					$maxRank = $player['MaxRank'];
				}
				break;
			}
		}
		return $maxRank;
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
		if (!$this->dedimaniaData->records) {
			return null;
		}
		$records = $this->dedimaniaData->records['Records'];
		foreach($records as $record) {
			if ($record['Login'] === $login) {
				return $record;
			}
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
	 * Get current checkpoint string for dedimania record
	 *
	 * @param string $login
	 * @return string
	 */
	private function getCheckpoints($login) {
		if (!$login || !isset($this->checkpoints[$login])) {
			return null;
		}
		$string = '';
		$count  = count($this->checkpoints[$login]);
		foreach($this->checkpoints[$login] as $index => $check) {
			$string .= $check;
			if ($index < $count - 1) {
				$string .= ',';
			}
		}
		return $string;
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
	 * @param $method
	 */
	private function handleXmlRpcFault($fault, $method) {
		trigger_error('XmlRpc Fault on ' . $method . ': ' . $fault['faultString'] . ' (' . $fault['faultCode'] . ')');
	}


	private function buildManialink() {
		if (!$this->dedimaniaData->records) {
			return '';
		}
		$records = $this->dedimaniaData->records['Records'];

		$title        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_WIDGET_TITLE);
		$pos_x        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_WIDGET_POSX);
		$pos_y        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_WIDGET_POSY);
		$width        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_WIDGET_WIDTH);
		$lines        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_WIDGET_LINESCOUNT);
		$lineHeight   = $this->maniaControl->settingManager->getSetting($this, self::SETTING_WIDGET_LINEHEIGHT);
		$labelStyle   = $this->maniaControl->manialinkManager->styleManager->getDefaultLabelStyle();
		$quadStyle    = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadStyle();
		$quadSubstyle = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadSubstyle();


		$manialink = new ManiaLink(self::MLID_DEDIMANIA);
		$frame     = new Frame();
		$manialink->add($frame);
		$frame->setPosition($pos_x, $pos_y);

		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setVAlign(Control::TOP);
		$height = 7. + $lines * $lineHeight;
		$backgroundQuad->setSize($width * 1.05, $height);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);

		$titleLabel = new Label();
		$frame->add($titleLabel);
		$titleLabel->setPosition(0, $lineHeight * -0.9);
		$titleLabel->setWidth($width);
		$titleLabel->setStyle($labelStyle);
		$titleLabel->setTextSize(2);
		$titleLabel->setText($title);
		$titleLabel->setTranslate(true);

		foreach($records as $index => $record) {
			if ($index >= $lines) {
				break;
			}

			$y = -8. - $index * $lineHeight;

			$recordFrame = new Frame();
			$frame->add($recordFrame);
			$recordFrame->setPosition(0, $y);

			$backgroundQuad = new Quad();
			$recordFrame->add($backgroundQuad);
			$backgroundQuad->setSize($width * 1.04, $lineHeight * 1.4);
			$backgroundQuad->setStyles($quadStyle, $quadSubstyle);

			//Rank
			$rankLabel = new Label();
			$recordFrame->add($rankLabel);
			$rankLabel->setHAlign(Control::LEFT);
			$rankLabel->setX($width * -0.47);
			$rankLabel->setSize($width * 0.06, $lineHeight);
			$rankLabel->setTextSize(1);
			$rankLabel->setTextPrefix('$o');
			$rankLabel->setText($record['Rank']);

			//Name
			$nameLabel = new Label();
			$recordFrame->add($nameLabel);
			$nameLabel->setHAlign(Control::LEFT);
			$nameLabel->setX($width * -0.4);
			$nameLabel->setSize($width * 0.6, $lineHeight);
			$nameLabel->setTextSize(1);
			$nameLabel->setText(Formatter::stripDirtyCodes($record['NickName']));

			//Time
			$timeLabel = new Label();
			$recordFrame->add($timeLabel);
			$timeLabel->setHAlign(Control::RIGHT);
			$timeLabel->setX($width * 0.47);
			$timeLabel->setSize($width * 0.25, $lineHeight);
			$timeLabel->setTextSize(1);
			$timeLabel->setText(Formatter::formatTime($record['Best']));
		}

		return $manialink->render()->saveXML();
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