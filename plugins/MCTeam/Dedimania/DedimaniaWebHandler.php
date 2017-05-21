<?php

namespace MCTeam\Dedimania;


use ManiaControl\Files\AsyncHttpRequest;
use ManiaControl\Logger;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;

/**
 * ManiaControl Dedimania Webhandler Class for Dedimania Plugin
 * Notice its not completely finished yet
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class DedimaniaWebHandler {
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
		$content = $this->encodeRequest(self::DEDIMANIA_OPEN_SESSION, array($this->dedimaniaData->toArray()));

		$asyncHttpRequest = new AsyncHttpRequest($this->maniaControl, self::DEDIMANIA_URL);
		$asyncHttpRequest->setCallable(function ($data, $error) use ($updateRecords) {
			Logger::logInfo("Try to connect on Dedimania");

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
		if (!isset($this->dedimaniaData) || !$this->dedimaniaData->sessionId) {
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
		$content = $this->encodeRequest(self::DEDIMANIA_GET_RECORDS, $data);

		$asyncHttpRequest = new AsyncHttpRequest($this->maniaControl, self::DEDIMANIA_URL);
		$asyncHttpRequest->setCallable(function ($data, $error) {
			$data = $this->decode($data);

			//Data[0][0] can be false in error case like map has no checkpoints
			if (!is_array($data) || empty($data) || !isset($data[0]) || !isset($data[0][0]) || $data[0][0] == false) {
				return;
			}

			$methodResponse = $data[0];
			if (xmlrpc_is_fault($methodResponse)) {
				$this->handleXmlRpcFault($methodResponse, self::DEDIMANIA_GET_RECORDS);
				return;
			}

			$responseData = $methodResponse[0];

			if (!isset($responseData['Players']) || !isset($responseData['Records'])) {
				$this->maniaControl->getErrorHandler()->triggerDebugNotice('Invalid Dedimania response! ' . json_encode($responseData));
				return;
			}

			$this->dedimaniaData->serverMaxRank = $responseData['ServerMaxRank'];

			foreach ($responseData['Players'] as $player) {
				$dediPlayer = new DedimaniaPlayer($player);
				$this->dedimaniaData->addPlayer($dediPlayer);
			}
			foreach ($responseData['Records'] as $key => $record) {
				$this->dedimaniaData->records[$key] = new RecordData($record);
			}

			Logger::logInfo(count($this->dedimaniaData->records) . " Dedimania Records Fetched succesfully!");

			$this->maniaLinkNeedsUpdate = true;
			$this->maniaControl->getCallbackManager()->triggerCallback(DedimaniaPlugin::CB_DEDIMANIA_UPDATED, $this->dedimaniaData->records); //TODO
		});

		$asyncHttpRequest->setContent($content);
		$asyncHttpRequest->setCompression(true);
		$asyncHttpRequest->setTimeout(500);
		$asyncHttpRequest->postData();

		return true;
	}

	/**
	 * Checks If a Dedimania Session exists, if not create a new oen
	 */
	public function checkDedimaniaSession() { //TODO complete check and refactor
		if (!$this->dedimaniaData->sessionId) {
			$this->openDedimaniaSession();
			return;
		}

		$content = $this->encodeRequest(self::DEDIMANIA_CHECK_SESSION, array($this->dedimaniaData->sessionId));

		$asyncHttpRequest = new AsyncHttpRequest($this->maniaControl, self::DEDIMANIA_URL);
		$asyncHttpRequest->setCallable(function ($data, $error) {
			if ($error) {
				//Reopen session in Timeout case
				$this->openDedimaniaSession();
				Logger::logError("Dedimania Error while checking session (opening new Session): " . $error);
			}

			$data = $this->decode($data);
			if (!is_array($data) || empty($data)) {
				return;
			}

			$methodResponse = $data[0];
			if (xmlrpc_is_fault($methodResponse)) {
				$this->handleXmlRpcFault($methodResponse, self::DEDIMANIA_CHECK_SESSION);
				$this->openDedimaniaSession();
				return;
			}

			$responseData = $methodResponse[0];
			if (is_bool($responseData)) {
				if (!$responseData) {
					$this->openDedimaniaSession();
				}
			}
		});
		$asyncHttpRequest->setContent($content);
		$asyncHttpRequest->setCompression(true);
		$asyncHttpRequest->setTimeout(500);
		$asyncHttpRequest->postData();
	}

	/**
	 * Handle EndMap Callback
	 */
	public function submitChallengeTimes() {
		if (!$this->getDedimaniaData()->recordsExisting()) {
			return;
		}

		//Finish Counts as CP somehow
		if ($this->maniaControl->getMapManager()->getCurrentMap()->nbCheckpoints < 2) {
			return;
		}

		//Make Sure Dedimania Session is okay
		$this->checkDedimaniaSession(); //TODO needed?

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

			if (!$record->vReplay) {
				Logger::logInfo("Ignore time for " . $record->login . " no validation replay found");
				continue;
			}

			array_push($times, array('Login' => $record->login, 'Best' => $record->best, 'Checks' => $record->checkpoints));
			if (!isset($replays['VReplay'])) {
				$replays['VReplay'] = $record->vReplay;
			}
			if (!isset($replays['Top1GReplay'])) {
				$replays['Top1GReplay'] = $record->top1GReplay;
			}
			if (!isset($replays['VReplayChecks'])) {
				$replays['VReplayChecks'] = '';
				// TODO: VReplayChecks
			}
		}

		xmlrpc_set_type($replays['VReplay'], 'base64');
		xmlrpc_set_type($replays['Top1GReplay'], 'base64');

		$data    = array($this->dedimaniaData->sessionId, $this->getMapInfo(), $gameMode, $times, $replays);
		$content = $this->encodeRequest(self::DEDIMANIA_SET_CHALLENGE_TIMES, $data);

		Logger::logInfo("Dedimania Submitting Map Times at End-Map Start");


		$asyncHttpRequest = new AsyncHttpRequest($this->maniaControl, self::DEDIMANIA_URL);
		$asyncHttpRequest->setCallable(function ($data, $error) {
			if ($error) {
				Logger::logError("Dedimania Error while submitting times: " . $error);
			}

			$data = $this->decode($data);
			if (!is_array($data) || empty($data)) {
				return;
			}

			$methodResponse = $data[0];
			if (xmlrpc_is_fault($methodResponse)) {
				$this->handleXmlRpcFault($methodResponse, self::DEDIMANIA_SET_CHALLENGE_TIMES);
				return;
			}

			// Called method response
			if (!$methodResponse[0]) {
				Logger::logError("Records Plugin: Submitting dedimania records failed.");
				Logger::logError(json_encode($data));
			} else {
				Logger::logInfo("Dedimania Times succesfully submitted");
			}

		});
		$asyncHttpRequest->setContent($content);
		$asyncHttpRequest->setCompression(false);
		$asyncHttpRequest->setTimeout(500);
		$asyncHttpRequest->postData();
	}

	/**
	 * Handle PlayerConnect callback
	 *
	 * @param Player $player
	 */
	public function handlePlayerConnect(Player $player) {
		if (!isset($this->dedimaniaData)) {
			return;
		}

		// Send Dedimania request
		$data    = array($this->dedimaniaData->sessionId, $player->login, $player->rawNickname, $player->path, $player->isSpectator);
		$content = $this->encodeRequest(self::DEDIMANIA_PLAYERCONNECT, $data);

		$asyncHttpRequest = new AsyncHttpRequest($this->maniaControl, self::DEDIMANIA_URL);
		$asyncHttpRequest->setCallable(function ($data, $error) use (&$player) {
			if ($error) {
				$this->openDedimaniaSession(); //TODO Verify why?
				var_dump($data);
				var_dump("Dedimania test3");
			}

			$data = $this->decode($data);
			if (!is_array($data) || empty($data)) {
				return;
			}

			$methodResponse = $data[0];
			if (xmlrpc_is_fault($methodResponse)) {
				$this->handleXmlRpcFault($methodResponse, self::DEDIMANIA_PLAYERCONNECT);
				return;
			}

			$responseData = $methodResponse[0];
			$dediPlayer   = new DedimaniaPlayer($responseData);
			$this->dedimaniaData->addPlayer($dediPlayer);

			// Fetch records if he is the first who joined the server
			if ($this->maniaControl->getPlayerManager()->getPlayerCount(false) === 1) {
				$this->fetchDedimaniaRecords(true);
			}

			Logger::logInfo("Dedimania Player added " . $dediPlayer->login);

			$this->maniaLinkNeedsUpdate = true; //TODO handle update for only one player instead of everyone as soon splitted
		});

		$asyncHttpRequest->setContent($content);
		$asyncHttpRequest->setCompression(true);
		$asyncHttpRequest->setTimeout(500);
		$asyncHttpRequest->postData();
	}

	/**
	 * Handle Player Disconnect Callback
	 *
	 * @param Player $player
	 */
	public function handlePlayerDisconnect(Player $player) { //TODO move into webhandler
		if (!isset($this->dedimaniaData)) {
			return;
		}
		$this->dedimaniaData->removePlayer($player->login);

		// Send Dedimania request
		$data    = array($this->dedimaniaData->sessionId, $player->login, '');
		$content = $this->encodeRequest(self::DEDIMANIA_PLAYERDISCONNECT, $data);

		$asyncHttpRequest = new AsyncHttpRequest($this->maniaControl, self::DEDIMANIA_URL);
		$asyncHttpRequest->setCallable(function ($data, $error) {
			if ($error) {
				$this->openDedimaniaSession();
				var_dump($data);
				var_dump("Dedimania test4");
			}

			$data = $this->decode($data);
			if (!is_array($data) || empty($data)) {
				return;
			}

			$methodResponse = $data[0];
			if (xmlrpc_is_fault($methodResponse)) {
				$this->handleXmlRpcFault($methodResponse, self::DEDIMANIA_PLAYERDISCONNECT);
			}

			Logger::logInfo("Debug: Dedimania Player removed");
		});

		$asyncHttpRequest->setContent($content);
		$asyncHttpRequest->setCompression(true);
		$asyncHttpRequest->setTimeout(500);
		$asyncHttpRequest->postData();
	}


	/**
	 * Update the PlayerList every 3 Minutes
	 */
	public function updatePlayerList() {
		$serverInfo = $this->getServerInfo();
		$playerList = $this->getPlayerList();
		$votesInfo  = $this->getVotesInfo();
		if (!$serverInfo || !$votesInfo || !$playerList || !isset($this->dedimaniaData) || !$this->dedimaniaData->sessionId) {
			return;
		}

		// Send Dedimania request
		$data    = array($this->dedimaniaData->sessionId, $serverInfo, $votesInfo, $playerList);
		$content = $this->encodeRequest(self::DEDIMANIA_UPDATE_SERVER_PLAYERS, $data);

		$asyncHttpRequest = new AsyncHttpRequest($this->maniaControl, self::DEDIMANIA_URL);
		$asyncHttpRequest->setCallable(function ($data, $error) {
			if ($error) {
				Logger::logError("Dedimania Error while update playerlist: " . $error);
				var_dump($data);
				var_dump("test4");

			}

			$data = $this->decode($data);
			if (!is_array($data) || empty($data)) {
				return;
			}

			$methodResponse = $data[0];
			if (xmlrpc_is_fault($methodResponse)) {
				$this->handleXmlRpcFault($methodResponse, self::DEDIMANIA_UPDATE_SERVER_PLAYERS);
			}

			Logger::logInfo("Dedimania Playerlist Updated");
		});

		$asyncHttpRequest->setContent($content);
		$asyncHttpRequest->setCompression(true);
		$asyncHttpRequest->setTimeout(500);
		$asyncHttpRequest->postData();
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
		$mapInfo['Author']        = $map->authorLogin;
		$mapInfo['Environment']   = $map->environment;
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