<?php

namespace MCTeam\Dedimania;


use ManiaControl\Callbacks\TimerListener;
use ManiaControl\Files\AsyncHttpRequest;
use ManiaControl\Logger;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;

/**
 * ManiaControl Dedimania Webhandler Class for Dedimania Plugin
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class DedimaniaWebHandler implements TimerListener {
	const XMLRPC_MULTICALL                = 'system.multicall';
	const DEDIMANIA_URL                   = 'http://dedimania.net:8082/Dedimania';
	const DEDIMANIA_OPEN_SESSION          = 'dedimania.OpenSession';
	const DEDIMANIA_CHECK_SESSION         = 'dedimania.CheckSession';
	const DEDIMANIA_GET_RECORDS           = 'dedimania.GetChallengeRecords';
	const DEDIMANIA_PLAYERCONNECT         = 'dedimania.PlayerConnect';
	const DEDIMANIA_PLAYERDISCONNECT      = 'dedimania.PlayerDisconnect';
	const DEDIMANIA_UPDATE_SERVER_PLAYERS = 'dedimania.UpdateServerPlayers';
	const DEDIMANIA_SET_CHALLENGE_TIMES   = 'dedimania.SetChallengeTimes';
	const DEDIMANIA_WARNINGSANDTTR2       = 'dedimania.WarningsAndTTR2';

	/** @var  ManiaControl $maniaControl */
	private $maniaControl;
	/** @var  \MCTeam\Dedimania\DedimaniaData $dedimaniaData */
	private $dedimaniaData;

	private $requests             = array();
	private $requestIndex         = 0;
	private $maniaLinkNeedsUpdate = false;

	public function __construct($maniaControl) {
		$this->maniaControl = $maniaControl;
	}

	/**
	 * Opens the Dedimania Session
	 *
	 * @param bool $updateRecords
	 */
	public function openDedimaniaSession($updateRecords = false) {
		//TODO updateRecords
		$content = $this->encodeRequest(self::DEDIMANIA_OPEN_SESSION, array($this->dedimaniaData->toArray()));

		Logger::logInfo("Try to connect on Dedimania");
		$asyncHttpRequest = new AsyncHttpRequest($this->maniaControl, self::DEDIMANIA_URL);
		$asyncHttpRequest->setCallable(function ($data, $error) use ($updateRecords) {
			if (!$data || $error) {
				Logger::logError("Dedimania Error while opening session: '{$error}' Line 42");
			}


			$data = $this->decode($data);
			if (!is_array($data) || empty($data)) {
				return;
			}

			$methodResponse = $data[0];
			if (xmlrpc_is_fault($methodResponse)) {
				$this->handleXmlRpcFault($methodResponse, self::DEDIMANIA_OPEN_SESSION);
				return;
			}

			$responseData                   = $methodResponse[0];
			$this->dedimaniaData->sessionId = $responseData['SessionId'];
			if ($this->dedimaniaData->sessionId) {
				Logger::logInfo("Dedimania connection successfully established.");

				if ($updateRecords) {
					$this->fetchDedimaniaRecords();
				}

			} else {
				Logger::logError("Error while opening Dedimania Connection");
			}
		});

		$asyncHttpRequest->setContent($content);
		$asyncHttpRequest->setCompression(true);
		$asyncHttpRequest->setTimeout(500);
		$asyncHttpRequest->postData();
	}


	/**
	 * Fetch Dedimania Records
	 *
	 * @param bool $reset
	 * @return bool
	 */
	public function fetchDedimaniaRecords($reset = true) {
		if (!isset($this->dedimaniaData) || !$this->dedimaniaData->sessionIdSet()) {
			return false;
		}

		// Reset records
		if ($reset) {
			$this->dedimaniaData->unsetRecords();
		}

		$serverInfo = $this->getServerInfo();
		$playerInfo = $this->getPlayerList();
		$mapInfo    = $this->getMapInfo();
		$gameMode   = $this->getGameModeString();

		if (!$serverInfo || !$playerInfo || !$mapInfo || !$gameMode) {
			$data = array($this->dedimaniaData->sessionId, $mapInfo, $gameMode, $serverInfo, $playerInfo);
			if ($serverInfo) { // No Players
				Logger::logError("Dedimania Records could not be fetched, debuginfo:" . json_encode($data));
			}

			if (!$gameMode) {
				$this->maniaControl->getChat()->sendError("Dedimania Error: Gamemode not found, try to restart map!");
			}

			return false;
		}

		$data = array($this->dedimaniaData->sessionId, $mapInfo, $gameMode, $serverInfo, $playerInfo);

		Logger::logInfo("Try to fetch Dedimania Records");
		$this->addRequest(self::DEDIMANIA_GET_RECORDS, $data);

		return true;
	}

	/**
	 * Checks If a Dedimania Session exists, if not create a new oen
	 */
	public function checkDedimaniaSession() { //TODO complete check and refactor
		if (!$this->dedimaniaData->sessionIdSet()) {
			return;
		}

		$this->addRequest(self::DEDIMANIA_CHECK_SESSION, array($this->dedimaniaData->sessionId));
	}

	/**
	 * Handle EndMap Callback
	 */
	public function submitChallengeTimes() {
		if (!$this->dedimaniaData->sessionIdSet()) {
			return;
		}

		if (!$this->getDedimaniaData()->recordsExisting()) {
			return;
		}

		//Finish Counts as CP somehow
		if ($this->maniaControl->getMapManager()->getCurrentMap()->nbCheckpoints < 2) {
			return;
		}

		// Send dedimania records
		$gameMode = $this->getGameModeString();
		$times    = array();
		$replays  = array();
		foreach ($this->dedimaniaData->records as $record) {
			if ($record->rank > $this->dedimaniaData->serverMaxRank) {
				break;
			}
			if (!$record->newRecord) {
				continue;
			}

			if ($record->vReplay == '' || !$record->vReplay) {
				Logger::logInfo("Dedimania Ignore time for " . $record->login . " no validation replay found");
				continue;
			}

			array_push($times, array('Login' => $record->login, 'Best' => $record->best, 'Checks' => $record->checkpoints));
			if (!isset($replays['VReplay'])) {
				$replays['VReplay'] = $record->vReplay;
			}

			if (!isset($replays['VReplayChecks'])) {
				$replays['VReplayChecks'] = $record->allCheckpoints;
			}
			if (!isset($replays['Top1GReplay'])) {
				$replays['Top1GReplay'] = $record->top1GReplay;
			}
		}

		xmlrpc_set_type($replays['VReplay'], 'base64');
		xmlrpc_set_type($replays['VReplayChecks'], 'base64');
		xmlrpc_set_type($replays['Top1GReplay'], 'base64');

		$data = array($this->dedimaniaData->sessionId, $this->getMapInfo(), $gameMode, $times, $replays);

		Logger::logInfo("Dedimania Submitting Map Times at End-Map Start");
		$this->addRequest(self::DEDIMANIA_SET_CHALLENGE_TIMES, $data);
	}

	/**
	 * Handle PlayerConnect callback
	 *
	 * @param Player $player
	 */
	public function handlePlayerConnect(Player $player) {
		if (!isset($this->dedimaniaData) || !$this->dedimaniaData->sessionIdSet()) {
			return;
		}

		// Send Dedimania request
		$data = array($this->dedimaniaData->sessionId, $player->login, $player->rawNickname, $player->path, $player->isSpectator);
		$this->addRequest(self::DEDIMANIA_PLAYERCONNECT, $data);
	}

	/**
	 * Handle Player Disconnect Callback
	 *
	 * @param Player $player
	 */
	public function handlePlayerDisconnect(Player $player) { //TODO move into webhandler
		if (!isset($this->dedimaniaData) || !$this->dedimaniaData->sessionIdSet()) {
			return;
		}

		$this->dedimaniaData->removePlayer($player->login);

		// Send Dedimania request
		$data = array($this->dedimaniaData->sessionId, $player->login, '');
		$this->addRequest(self::DEDIMANIA_PLAYERDISCONNECT, $data);
	}


	/**
	 * Update the PlayerList every 3 Minutes
	 */
	public function updatePlayerList() {
		$serverInfo = $this->getServerInfo();
		$playerList = $this->getPlayerList();
		$votesInfo  = $this->getVotesInfo();
		if (!$serverInfo || !$votesInfo || !$playerList || !isset($this->dedimaniaData) || !$this->dedimaniaData->sessionIdSet()) {
			return;
		}

		// Send Dedimania request
		$data = array($this->dedimaniaData->sessionId, $serverInfo, $votesInfo, $playerList);
		$this->addRequest(self::DEDIMANIA_UPDATE_SERVER_PLAYERS, $data);

	}

	/** Adds a Request to the Dedimania Queue */
	private function addRequest($method, $params) {
		$this->requests[$this->requestIndex] = array('methodName' => $method, 'params' => $params);
		$this->requestIndex++;
	}

	/**
	 * Process Dedimania Calls
	 */
	public function callDedimania() {
		if (empty($this->requests)) {
			return;
		}

		$this->addRequest(self::DEDIMANIA_WARNINGSANDTTR2, array());

		$content = xmlrpc_encode_request(self::XMLRPC_MULTICALL, array($this->requests), array('encoding' => 'UTF-8', 'escaping' => 'markup', 'verbosity' => 'no_white_space'));


		$asyncHttpRequest = new AsyncHttpRequest($this->maniaControl, self::DEDIMANIA_URL);
		$asyncHttpRequest->setCallable(function ($answerData, $error) {
			if ($error) {
				Logger::logError("Dedimania Error while Request: " . $error);
				$this->checkDedimaniaSession();
				return;
			}

			$data = $this->decode($answerData);
			if (!$data) {
				//TODO just Temporary
				var_dump("Dedimania Debug:");
				var_dump($answerData);
			}

			if (!is_array($data) || empty($data) || !isset($data[0]) || !isset($data[0][0])) {
				return;
			}

			//Get the Errors and Warnings to get the Method Order
			$errorsAndWarnings = $data[count($data) - 1][0];
			$methods           = $errorsAndWarnings['methods'];

			foreach ($data as $key => $methodResponse) {
				$methodName = $methods[$key]['methodName'];

				if (xmlrpc_is_fault($methodResponse)) {
					$this->handleXmlRpcFault($methodResponse, $methodName);
				}

				$responseParameters = $methodResponse[0];

				switch ($methodName) {
					case self::DEDIMANIA_UPDATE_SERVER_PLAYERS:
						Logger::logInfo("Dedimania Playerlist Updated");
						break;
					case self::DEDIMANIA_GET_RECORDS:
						if (!isset($responseParameters['Players']) || !isset($responseParameters['Records'])) {
							$this->maniaControl->getErrorHandler()->triggerDebugNotice('Invalid Dedimania response! ' . json_encode($responseParameters));
							return;
						}

						$this->dedimaniaData->serverMaxRank = $responseParameters['ServerMaxRank'];

						foreach ($responseParameters['Players'] as $player) {
							$dediPlayer = new DedimaniaPlayer($player);
							$this->dedimaniaData->addPlayer($dediPlayer);
						}
						foreach ($responseParameters['Records'] as $recordKey => $record) {
							$this->dedimaniaData->records[$recordKey] = new RecordData($record);
						}

						Logger::logInfo(count($this->dedimaniaData->records) . " Dedimania Records Fetched succesfully!");

						$this->maniaLinkNeedsUpdate = true;
						$this->maniaControl->getCallbackManager()->triggerCallback(DedimaniaPlugin::CB_DEDIMANIA_UPDATED, $this->dedimaniaData->records); //TODO

						//3 Minutes
						$this->maniaControl->getTimerManager()->registerOneTimeListening($this, function () {
							$this->updatePlayerList();
						}, 1000 * 60 * 3);

						break;
					case self::DEDIMANIA_CHECK_SESSION:
						//TODO Check and reopen if needed
						break;
					case self::DEDIMANIA_SET_CHALLENGE_TIMES:
						//TODO Proper Checks
						Logger::logInfo("Dedimania Times succesfully submitted");
						break;
					case self::DEDIMANIA_PLAYERCONNECT:
						$dediPlayer = new DedimaniaPlayer($responseParameters);
						$this->dedimaniaData->addPlayer($dediPlayer);

						// Fetch records if he is the first who joined the server
						if ($this->maniaControl->getPlayerManager()->getPlayerCount(false) === 1) {
							$this->fetchDedimaniaRecords(true);
						}

						Logger::logInfo("Dedimania Player added " . $dediPlayer->login);

						$this->maniaLinkNeedsUpdate = true; //TODO handle update for only one player instead of everyone as soon splitted
						break;
					case self::DEDIMANIA_PLAYERDISCONNECT:
						Logger::logInfo("Debug: Dedimania Player removed");
						break;
					case self::DEDIMANIA_WARNINGSANDTTR2:
						foreach ($responseParameters['methods'] as $method) {
							if ($method['errors']) {
								Logger::log('Dedimania Warning or Error: ' . $method['methodName'] . ': ' . json_encode($method['errors']));
							}
						}
				}
			}
		});

		$asyncHttpRequest->setContent($content);
		$asyncHttpRequest->setCompression(true);
		$asyncHttpRequest->setTimeout(500);
		$asyncHttpRequest->postData();

		$this->requests = array();
	}

	/**
	 * Encode the given xml rpc method and params
	 *
	 * @param string $method
	 * @param array  $params
	 * @return string
	 */
	private function encodeRequest($method, $params) {
		$paramArray = array(array('methodName' => $method, 'params' => $params), array('methodName' => self::DEDIMANIA_WARNINGSANDTTR2, 'params' => array()));
		return xmlrpc_encode_request(self::XMLRPC_MULTICALL, array($paramArray), array('encoding' => 'UTF-8', 'escaping' => 'markup', 'verbosity' => 'no_white_space'));
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
	 * Build Votes Info Array for Callbacks
	 */
	private function getVotesInfo() {
		$map = $this->maniaControl->getMapManager()->getCurrentMap();
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
	 * Build server info Structure for callbacks
	 */
	private function getServerInfo() {
		$server = $this->maniaControl->getClient()->getServerOptions();
		if (!$server) {
			return null;
		}

		if ($this->maniaControl->getPlayerManager()->getPlayerCount(false) <= 0) {
			return null;
		}

		$playerCount    = $this->maniaControl->getPlayerManager()->getPlayerCount();
		$spectatorCount = $this->maniaControl->getPlayerManager()->getSpectatorCount();

		return array('SrvName'  => $server->name, 'Comment' => $server->comment, 'Private' => (strlen($server->password) > 0), 'NumPlayers' => $playerCount, 'MaxPlayers' => $server->currentMaxPlayers,
		             'NumSpecs' => $spectatorCount, 'MaxSpecs' => $server->currentMaxSpectators);
	}

	/**
	 * Build simple player list for callbacks
	 */
	private function getPlayerList() {
		$players = $this->maniaControl->getPlayerManager()->getPlayers();

		if (empty($players)) {
			return null;
		}
		$playerInfo = array();
		foreach ($players as $player) {
			array_push($playerInfo, array('Login' => $player->login, 'IsSpec' => $player->isSpectator));
		}
		return $playerInfo;
	}

	/**
	 * Build Map Info Array for Dedimania Requests
	 *
	 * @return array
	 */
	private function getMapInfo() {
		$map = $this->maniaControl->getMapManager()->getCurrentMap();
		if (!$map) {
			return null;
		}
		$mapInfo                  = array();
		$mapInfo['UId']           = $map->uid;
		$mapInfo['Name']          = $map->rawName;
		$mapInfo['Environment']   = $map->environment;
		$mapInfo['Author']        = $map->authorLogin;
		$mapInfo['NbCheckpoints'] = $map->nbCheckpoints;
		$mapInfo['NbLaps']        = $map->nbLaps;
		return $mapInfo;
	}

	/**
	 * Get Dedimania String Representation of the current Game Mode
	 *
	 * @return String
	 */
	private function getGameModeString() {
		$gameMode = $this->maniaControl->getServer()->getGameMode();
		if ($gameMode === null) {
			Logger::logError("Couldn't retrieve game mode.");
			return null;
		}
		switch ($gameMode) {
			case 0: {
				$scriptNameResponse = $this->maniaControl->getClient()->getScriptName();
				$scriptName         = str_replace('.Script.txt', '', $scriptNameResponse['CurrentValue']);
				switch ($scriptName) {
					case 'Rounds':
					case 'Cup':
					case 'Team':
					case 'Chase':
						return 'Rounds';
					case 'TimeAttack':
					case 'Laps':
					case 'TeamAttack':
					case 'TimeAttackPlus':
						return 'TA';
				}
				break;
			}
			case 1:
			case 3:
			case 5: {
				return 'Rounds';
			}
			case 2:
			case 4: {
				return 'TA';
			}
		}
		return null;
	}

	/**
	 * Handle xml rpc fault
	 *
	 * @param array  $fault
	 * @param string $method
	 */
	private function handleXmlRpcFault(array $fault, $method) {
		trigger_error("XmlRpc Fault on '{$method}': '{$fault['faultString']} ({$fault['faultCode']})!");
	}

	/**
	 * @return \MCTeam\Dedimania\DedimaniaData
	 */
	public function getDedimaniaData() {
		return $this->dedimaniaData;
	}

	/**
	 * @param \MCTeam\Dedimania\DedimaniaData $dedimaniaData
	 */
	public function setDedimaniaData($dedimaniaData) {
		$this->dedimaniaData = $dedimaniaData;
	}

	/**
	 * @return bool
	 */
	public function doesManiaLinkNeedUpdate() {
		return $this->maniaLinkNeedsUpdate;
	}

	/**
	 * Call if ManiaLink got Updated
	 */
	public function maniaLinkUpdated() {
		$this->maniaLinkNeedsUpdate = false;
	}

	/**
	 * Call if You want a ManiaLink Update
	 */
	public function maniaLinkUpdateNeeded() {
		$this->maniaLinkNeedsUpdate = true;
	}
}