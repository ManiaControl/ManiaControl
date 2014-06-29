<?php

namespace MCTeam\Dedimania;

use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_BgsPlayerCard;
use FML\ManiaLink;
use FML\Script\Features\Paging;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Callbacks\Callbacks;
use ManiaControl\Callbacks\Models\RecordCallback;
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\Commands\CommandListener;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;
use ManiaControl\Plugins\Plugin;
use ManiaControl\Utils\Formatter;

/**
 * ManiaControl Dedimania Plugin
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class DedimaniaPlugin implements CallbackListener, CommandListener, TimerListener, Plugin {
	/*
	 * Constants
	 */
	const ID                              = 8;
	const VERSION                         = 0.1;
	const AUTHOR                          = 'MCTeam';
	const NAME                            = 'Dedimania Plugin';
	const MLID_DEDIMANIA                  = 'Dedimania.ManialinkId';
	const XMLRPC_MULTICALL                = 'system.multicall';
	const DEDIMANIA_URL                   = 'http://dedimania.net:8081/Dedimania';
	const DEDIMANIA_OPEN_SESSION          = 'dedimania.OpenSession';
	const DEDIMANIA_CHECK_SESSION         = 'dedimania.CheckSession';
	const DEDIMANIA_GET_RECORDS           = 'dedimania.GetChallengeRecords';
	const DEDIMANIA_PLAYERCONNECT         = 'dedimania.PlayerConnect';
	const DEDIMANIA_PLAYERDISCONNECT      = 'dedimania.PlayerDisconnect';
	const DEDIMANIA_UPDATE_SERVER_PLAYERS = 'dedimania.UpdateServerPlayers';
	const DEDIMANIA_SET_CHALLENGE_TIMES   = 'dedimania.SetChallengeTimes';
	const DEDIMANIA_WARNINGSANDTTR2       = 'dedimania.WarningsAndTTR2';
	const SETTING_WIDGET_ENABLE           = 'Enable Dedimania Widget';
	const SETTING_WIDGET_TITLE            = 'Widget Title';
	const SETTING_WIDGET_POSX             = 'Widget Position: X';
	const SETTING_WIDGET_POSY             = 'Widget Position: Y';
	const SETTING_WIDGET_WIDTH            = 'Widget Width';
	const SETTING_WIDGET_LINE_COUNT       = 'Widget Displayed Lines Count';
	const SETTING_WIDGET_LINE_HEIGHT      = 'Widget Line Height';
	const SETTING_DEDIMANIA_CODE          = '$l[http://dedimania.net/tm2stats/?do=register]Dedimania Code for ';
	const CB_DEDIMANIA_CHANGED            = 'Dedimania.Changed';
	const CB_DEDIMANIA_UPDATED            = 'Dedimania.Updated';
	const ACTION_SHOW_DEDIRECORDSLIST     = 'Dedimania.ShowDediRecordsList';

	/*
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
	 * @see \ManiaControl\Plugins\Plugin::prepare()
	 */
	public static function prepare(ManiaControl $maniaControl) {
		$servers = $maniaControl->server->getAllServers();
		foreach ($servers as $server) {
			$maniaControl->settingManager->initSetting(get_class(), self::SETTING_DEDIMANIA_CODE . $server->login . '$l', '');
		}
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getId()
	 */
	public static function getId() {
		return self::ID;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getName()
	 */
	public static function getName() {
		return self::NAME;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getVersion()
	 */
	public static function getVersion() {
		return self::VERSION;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getAuthor()
	 */
	public static function getAuthor() {
		return self::AUTHOR;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getDescription()
	 */
	public static function getDescription() {
		return 'Dedimania Plugin for TrackMania';
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::load()
	 */
	public function load(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		if (!extension_loaded('xmlrpc')) {
			throw new \Exception("You need to activate the PHP extension xmlrpc to run this Plugin!");
		}

		$this->maniaControl->settingManager->initSetting($this, self::SETTING_WIDGET_ENABLE, true);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_WIDGET_TITLE, 'Dedimania');
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_WIDGET_POSX, -139);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_WIDGET_POSY, 7);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_WIDGET_WIDTH, 40);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_WIDGET_LINE_HEIGHT, 4);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_WIDGET_LINE_COUNT, 12);

		$this->maniaControl->callbackManager->registerCallbackListener(Callbacks::BEGINMAP, $this, 'handleBeginMap');
		$this->maniaControl->callbackManager->registerCallbackListener(Callbacks::ENDMAP, $this, 'handleMapEnd');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 'handleManialinkPageAnswer');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERCONNECT, $this, 'handlePlayerConnect');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERDISCONNECT, $this, 'handlePlayerDisconnect');
		$this->maniaControl->callbackManager->registerCallbackListener(RecordCallback::CHECKPOINT, $this, 'handleCheckpointCallback');
		$this->maniaControl->callbackManager->registerCallbackListener(RecordCallback::LAPFINISH, $this, 'handleLapFinishCallback');
		$this->maniaControl->callbackManager->registerCallbackListener(RecordCallback::FINISH, $this, 'handleFinishCallback');

		$this->maniaControl->timerManager->registerTimerListening($this, 'updateEverySecond', 1000);
		$this->maniaControl->timerManager->registerTimerListening($this, 'handleEveryMinute', 1000 * 60);
		$this->maniaControl->timerManager->registerTimerListening($this, 'updatePlayerList', 1000 * 60 * 3);

		$this->maniaControl->commandManager->registerCommandListener(array('dedirecs', 'dedirecords'), $this, 'showDediRecordsList', false, 'Shows a list of Dedimania records of the current map.');

		// Open session
		$serverInfo    = $this->maniaControl->server->getInfo();
		$serverVersion = $this->maniaControl->client->getVersion();
		$packMask      = $this->maniaControl->server->titleId;
		if ($packMask !== 'Trackmania_2@nadeolabs') {
			$packMask = substr($this->maniaControl->server->titleId, 2);
		}

		$dedimaniaCode = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_DEDIMANIA_CODE . $serverInfo->login . '$l');
		if (!$dedimaniaCode) {
			throw new \Exception("No Dedimania Code Specified, check the settings!");
		}

		$this->dedimaniaData = new DedimaniaData($serverInfo->login, $dedimaniaCode, $serverInfo->path, $packMask, $serverVersion);

		$this->openDedimaniaSession();
	}

	/**
	 * Opens the Dedimania Session
	 */
	private function openDedimaniaSession() {
		$content = $this->encode_request(self::DEDIMANIA_OPEN_SESSION, array($this->dedimaniaData->toArray()));

		$this->maniaControl->fileReader->postData(self::DEDIMANIA_URL, function ($data, $error) {
			$this->maniaControl->log("Try to connect on Dedimania");

			if (!$data || $error) {
				$this->maniaControl->log("Dedimania Error: '{$error}'");
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
				$this->maniaControl->log("Dedimania connection successfully established.");
				$this->fetchDedimaniaRecords();
				$this->init = true;
			} else {
				$this->maniaControl->log("Error while opening Dedimania Connection");
			}
		}, $content, true);
	}

	/**
	 * Encode the given xml rpc method and params
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
	 * Decodes xml rpc response
	 *
	 * @param string $response
	 * @return mixed
	 */
	private function decode($response) {
		return xmlrpc_decode($response, 'utf-8');
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
	 * Fetch Dedimania Records
	 *
	 * @param bool $reset
	 * @return bool
	 */
	private function fetchDedimaniaRecords($reset = true) {
		if (!$this->dedimaniaData || !$this->dedimaniaData->sessionId) {
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
		$content = $this->encode_request(self::DEDIMANIA_GET_RECORDS, $data);

		$this->maniaControl->fileReader->postData(self::DEDIMANIA_URL, function ($data, $error) {
			if ($error) {
				$this->maniaControl->log('Dedimania Error: ' . $error);
			}

			$data = $this->decode($data);
			if (!is_array($data) || empty($data)) {
				return;
			}

			$methodResponse = $data[0];
			if (xmlrpc_is_fault($methodResponse)) {
				$this->handleXmlRpcFault($methodResponse, self::DEDIMANIA_GET_RECORDS);
				return;
			}

			$responseData                       = $methodResponse[0];
			if (!isset($responseData['Players']) || !isset($responseData['Records'])) {
				$this->maniaControl->errorHandler->triggerDebugNotice('Invalid Dedimania response!', $responseData);
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

			$this->updateManialink = true;
			$this->maniaControl->callbackManager->triggerCallback(self::CB_DEDIMANIA_UPDATED, $this->dedimaniaData->records);
		}, $content, true);

		return true;
	}

	/**
	 * Build server info Structure for callbacks
	 */
	private function getServerInfo() {
		$server = $this->maniaControl->client->getServerOptions();
		if (!$server) {
			return null;
		}

		if ($this->maniaControl->playerManager->getPlayerCount(false) <= 0) {
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
		$players = $this->maniaControl->playerManager->getPlayers();

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
		$map = $this->maniaControl->mapManager->getCurrentMap();
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
		$gameMode = $this->maniaControl->server->getGameMode();
		if ($gameMode === null) {
			trigger_error("Couldn't retrieve game mode.");
			return null;
		}
		switch ($gameMode) {
			case 0:
			{
				$scriptNameResponse = $this->maniaControl->client->getScriptName();
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
	 * Handle 1 Second Callback
	 */
	public function updateEverySecond() {
		if (!$this->updateManialink || !$this->dedimaniaData->records) {
			return;
		}
		$this->updateManialink = false;

		if ($this->maniaControl->settingManager->getSettingValue($this, self::SETTING_WIDGET_ENABLE)) {
			$manialink = $this->buildManialink();
			$this->maniaControl->manialinkManager->sendManialink($manialink);
		}
	}

	/**
	 * Build Manialink
	 *
	 * @return \FML\ManiaLink
	 */
	private function buildManialink() {
		if (!$this->dedimaniaData->records) {
			return null;
		}
		$records = $this->dedimaniaData->records;

		$title        = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_WIDGET_TITLE);
		$posX         = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_WIDGET_POSX);
		$posY         = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_WIDGET_POSY);
		$width        = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_WIDGET_WIDTH);
		$lines        = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_WIDGET_LINE_COUNT);
		$lineHeight   = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_WIDGET_LINE_HEIGHT);
		$labelStyle   = $this->maniaControl->manialinkManager->styleManager->getDefaultLabelStyle();
		$quadStyle    = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadStyle();
		$quadSubstyle = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadSubstyle();


		$manialink = new ManiaLink(self::MLID_DEDIMANIA);
		$frame     = new Frame();
		$manialink->add($frame);
		$frame->setPosition($posX, $posY);

		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setVAlign($backgroundQuad::TOP);
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

		foreach ($records as $index => $record) {
			/** @var RecordData $record */
			if ($index >= $lines) {
				break;
			}

			$y = -8. - $index * $lineHeight;

			$recordFrame = new Frame();
			$frame->add($recordFrame);
			$recordFrame->setPosition(0, $y);

			/*$backgroundQuad = new Quad();
			$recordFrame->add($backgroundQuad);
			$backgroundQuad->setSize($width * 1.04, $lineHeight * 1.4);
			$backgroundQuad->setStyles($quadStyle, $quadSubstyle);*/

			//Rank
			$rankLabel = new Label();
			$recordFrame->add($rankLabel);
			$rankLabel->setHAlign($rankLabel::LEFT);
			$rankLabel->setX($width * -0.47);
			$rankLabel->setSize($width * 0.06, $lineHeight);
			$rankLabel->setTextSize(1);
			$rankLabel->setTextPrefix('$o');
			$rankLabel->setText($record->rank);
			$rankLabel->setTextEmboss(true);

			//Name
			$nameLabel = new Label();
			$recordFrame->add($nameLabel);
			$nameLabel->setHAlign($nameLabel::LEFT);
			$nameLabel->setX($width * -0.4);
			$nameLabel->setSize($width * 0.6, $lineHeight);
			$nameLabel->setTextSize(1);
			$nameLabel->setText($record->nickName);
			$nameLabel->setTextEmboss(true);

			//Time
			$timeLabel = new Label();
			$recordFrame->add($timeLabel);
			$timeLabel->setHAlign($timeLabel::RIGHT);
			$timeLabel->setX($width * 0.47);
			$timeLabel->setSize($width * 0.25, $lineHeight);
			$timeLabel->setTextSize(1);
			$timeLabel->setText(Formatter::formatTime($record->best));
			$timeLabel->setTextEmboss(true);
		}

		return $manialink;
	}

	/**
	 * Handle 1 Minute Callback
	 */
	public function handleEveryMinute() {
		if (!$this->init) {
			return;
		}
		$this->checkDedimaniaSession();
	}

	/**
	 * Checks If a Dedimania Session exists, if not create a new oen
	 */
	private function checkDedimaniaSession() {
		if (!$this->dedimaniaData->sessionId) {
			$this->openDedimaniaSession();
			return;
		}

		$content = $this->encode_request(self::DEDIMANIA_CHECK_SESSION, array($this->dedimaniaData->sessionId));

		$this->maniaControl->fileReader->postData(self::DEDIMANIA_URL, function ($data, $error) {
			if ($error) {
				$this->maniaControl->log("Dedimania Error: " . $error);
			}

			$data = $this->decode($data);
			if (!is_array($data) || empty($data)) {
				return;
			}

			$methodResponse = $data[0];
			if (xmlrpc_is_fault($methodResponse)) {
				$this->handleXmlRpcFault($methodResponse, self::DEDIMANIA_CHECK_SESSION);
				return;
			}

			$responseData = $methodResponse[0];
			if (is_bool($responseData)) {
				if (!$responseData) {
					$this->openDedimaniaSession();
				}
			}
		}, $content, true);
		return;
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
		$content = $this->encode_request(self::DEDIMANIA_PLAYERCONNECT, $data);

		$this->maniaControl->fileReader->postData(self::DEDIMANIA_URL, function ($data, $error) use (&$player) {
			if ($error) {
				$this->maniaControl->log("Dedimania Error: " . $error);
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
			if ($this->maniaControl->playerManager->getPlayerCount(false) === 1) {
				$this->fetchDedimaniaRecords(true);
			}

			if ($this->maniaControl->settingManager->getSettingValue($this, self::SETTING_WIDGET_ENABLE)) {
				$manialink = $this->buildManialink();
				$this->maniaControl->manialinkManager->sendManialink($manialink, $player->login);
			}
		}, $content, true);
	}

	/**
	 * Handle Player Disconnect Callback
	 *
	 * @param Player $player
	 */
	public function handlePlayerDisconnect(Player $player) {
		if (!$this->dedimaniaData) {
			return;
		}
		$this->dedimaniaData->removePlayer($player->login);

		// Send Dedimania request
		$data    = array($this->dedimaniaData->sessionId, $player->login, '');
		$content = $this->encode_request(self::DEDIMANIA_PLAYERDISCONNECT, $data);

		$this->maniaControl->fileReader->postData(self::DEDIMANIA_URL, function ($data, $error) {
			if ($error) {
				$this->maniaControl->log("Dedimania Error: " . $error);
			}

			$data = $this->decode($data);
			if (!is_array($data) || empty($data)) {
				return;
			}

			$methodResponse = $data[0];
			if (xmlrpc_is_fault($methodResponse)) {
				$this->handleXmlRpcFault($methodResponse, self::DEDIMANIA_PLAYERDISCONNECT);
			}
		}, $content, true);
	}

	/**
	 * Handle Begin Map Callback
	 */
	public function handleBeginMap() {
		unset($this->dedimaniaData->records);
		$this->fetchDedimaniaRecords(true);
	}

	/**
	 * Handle EndMap Callback
	 */
	public function handleMapEnd() {
		if (!$this->dedimaniaData || !$this->dedimaniaData->records) {
			return;
		}

		// Send dedimania records
		$gameMode = $this->getGameModeString();
		$times    = array();
		$replays  = array();
		foreach ($this->dedimaniaData->records as $record) {
			/** @var RecordData $record */
			if ($record->rank > $this->dedimaniaData->serverMaxRank) {
				break;
			}

			if (!$record->newRecord) {
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

		//var_dump($replays);
		$data = array($this->dedimaniaData->sessionId, $this->getMapInfo(), $gameMode, $times, $replays);
		//var_dump($data);
		$content = $this->encode_request(self::DEDIMANIA_SET_CHALLENGE_TIMES, $data);

		$this->maniaControl->fileReader->postData(self::DEDIMANIA_URL, function ($data, $error) {
			if ($error) {
				$this->maniaControl->log("Dedimania Error: " . $error);
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
				trigger_error("Records Plugin: Submitting dedimania records failed.");
			}
		}, $content, false);
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
		$content = $this->encode_request(self::DEDIMANIA_UPDATE_SERVER_PLAYERS, $data);

		$this->maniaControl->fileReader->postData(self::DEDIMANIA_URL, function ($data, $error) {
			if ($error) {
				$this->maniaControl->log("Dedimania Error: " . $error);
			}

			$data = $this->decode($data);
			if (!is_array($data) || empty($data)) {
				return;
			}

			$methodResponse = $data[0];
			if (xmlrpc_is_fault($methodResponse)) {
				$this->handleXmlRpcFault($methodResponse, self::DEDIMANIA_UPDATE_SERVER_PLAYERS);
			}
		}, $content, true);
	}

	/**
	 * Build Votes Info Array for Callbacks
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
	 * Handle Checkpoint Callback
	 *
	 * @param RecordCallback $callback
	 */
	public function handleCheckpointCallback(RecordCallback $callback) {
		if ($callback->isLegacyCallback) {
			return;
		}
		if (!isset($this->checkpoints[$callback->login])) {
			$this->checkpoints[$callback->login] = array();
		}
		$this->checkpoints[$callback->login][$callback->lapCheckpoint] = $callback->lapTime;
	}

	/**
	 * Handle LapFinish Callback
	 *
	 * @param RecordCallback $callback
	 */
	public function handleLapFinishCallback(RecordCallback $callback) {
		$this->handleFinishCallback($callback);
	}

	/**
	 * Handle Finish Callback
	 *
	 * @param RecordCallback $callback
	 */
	public function handleFinishCallback(RecordCallback $callback) {
		if ($callback->isLegacyCallback) {
			return;
		}
		if ($callback->time <= 0) {
			// Invalid time
			return;
		}

		$map = $this->maniaControl->mapManager->getCurrentMap();
		if (!$map) {
			return;
		}

		$oldRecord = $this->getDedimaniaRecord($callback->login);
		if ($oldRecord->nullRecord || $oldRecord && $oldRecord->best > $callback->lapTime) {
			// Save time
			$newRecord = new RecordData(null);
			$newRecord->constructNewRecord($callback->login, $callback->player->nickname, $callback->lapTime, $this->getCheckpoints($callback->login), true);
			if ($this->insertDedimaniaRecord($newRecord, $oldRecord)) {
				// Get newly saved record
				foreach ($this->dedimaniaData->records as &$record) {
					/** @var RecordData $record */
					if ($record->login !== $newRecord->login) {
						continue;
					}
					$newRecord = $record;
					break;
				}

				$this->maniaControl->callbackManager->triggerCallback(self::CB_DEDIMANIA_CHANGED, $newRecord);

				// Announce record
				if ($oldRecord->nullRecord || $newRecord->rank < $oldRecord->rank) {
					// Gained rank
					$improvement = 'gained the';
				} else {
					// Only improved time
					$improvement = 'improved his/her';
				}
				$message = '$390$<$fff' . $callback->player->nickname . '$> ' . $improvement . ' $<$ff0' . $newRecord->rank . '.$> Dedimania Record: $<$fff' . Formatter::formatTime($newRecord->best) . '$>';
				if (!$oldRecord->nullRecord) {
					$message .= ' ($<$ff0' . $oldRecord->rank . '.$> $<$fff-' . Formatter::formatTime(($oldRecord->best - $callback->lapTime)) . '$>)';
				}
				$this->maniaControl->chat->sendInformation($message . '!');

				$this->updateManialink = true;
			}
		}
	}

	/**
	 * Get the dedimania record of the given login
	 *
	 * @param string $login
	 * @return RecordData $record
	 */
	private function getDedimaniaRecord($login) {
		if (!$this->dedimaniaData->records) {
			return new RecordData(null);
		}
		$records = $this->dedimaniaData->records;
		foreach ($records as &$record) {
			/** @var RecordData $record */
			if ($record->login === $login) {
				return $record;
			}
		}
		return new RecordData(null);
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
		foreach ($this->checkpoints[$login] as $index => $check) {
			$string .= $check;
			if ($index < $count - 1) {
				$string .= ',';
			}
		}
		return $string;
	}

	/**
	 * Inserts the given new Dedimania record at the proper position
	 *
	 * @param RecordData $newRecord
	 * @param RecordData $oldRecord
	 * @return bool
	 */
	private function insertDedimaniaRecord(RecordData &$newRecord, RecordData $oldRecord) {
		if ($newRecord->nullRecord) {
			return false;
		}

		$insert = false;

		// Get max possible rank
		$maxRank = $this->dedimaniaData->getPlayerMaxRank($newRecord->login);

		// Loop through existing records
		foreach ($this->dedimaniaData->records as $key => &$record) {
			/** @var RecordData $record */
			if ($record->rank > $maxRank) {
				// Max rank reached
				return false;
			}
			if ($record->login === $newRecord->login) {
				// Old record of the same player
				if ($record->best <= $newRecord->best) {
					// It's better - Do nothing
					return false;
				}

				// Replace old record
				unset($this->dedimaniaData->records[$key]);
				$insert = true;
				break;
			}

			// Other player's record
			if ($record->best <= $newRecord->best) {
				// It's better - Skip
				continue;
			}

			// New record is better - Insert it
			$insert = true;
			if ($oldRecord) {
				// Remove old record
				foreach ($this->dedimaniaData->records as $key2 => $record2) {
					/** @var RecordData $record2 */
					if ($record2->login !== $oldRecord->login) {
						continue;
					}
					unset($this->dedimaniaData->records[$key2]);
					break;
				}
			}
			break;
		}

		if (!$insert && count($this->dedimaniaData->records) < $maxRank) {
			// Records list not full - Append new record
			$insert = true;
		}

		if ($insert) {
			// Insert new record
			array_push($this->dedimaniaData->records, $newRecord);

			// Update ranks
			$this->updateDedimaniaRecordRanks();

			// Save replays
			foreach ($this->dedimaniaData->records as &$record) {
				if ($record->login !== $newRecord->login) {
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
		if ($this->dedimaniaData->getRecordCount() === 0) {
			$this->maniaControl->callbackManager->triggerCallback(self::CB_DEDIMANIA_UPDATED, $this->dedimaniaData->records);
			return;
		}

		$this->dedimaniaData->sortRecords();

		// Update ranks
		$rank = 1;
		foreach ($this->dedimaniaData->records as &$record) {
			/** @var RecordData $record */
			$record->rank = $rank;
			$rank++;
		}
		$this->maniaControl->callbackManager->triggerCallback(self::CB_DEDIMANIA_UPDATED, $this->dedimaniaData->records);
	}

	/**
	 * Update the replay values for the given record
	 *
	 * @param RecordData $record
	 */
	private function setRecordReplays(RecordData &$record) {
		// Set validation replay
		$validationReplay = $this->maniaControl->server->getValidationReplay($record->login);
		if ($validationReplay) {
			$record->vReplay = $validationReplay;
		}

		// Set ghost replay
		if ($record->rank <= 1) {
			$dataDirectory = $this->maniaControl->server->directory->getGameDataFolder();
			if (!isset($this->dedimaniaData->directoryAccessChecked)) {
				$access = $this->maniaControl->server->checkAccess($dataDirectory);
				if (!$access) {
					trigger_error("No access to the servers data directory. Can't retrieve ghost replays.");
				}
				$this->dedimaniaData->directoryAccessChecked = $access;
			}
			if ($this->dedimaniaData->directoryAccessChecked) {
				$ghostReplay = $this->maniaControl->server->getGhostReplay($record->login);
				if ($ghostReplay) {
					$record->top1GReplay = $ghostReplay;
				}
			}
		}
	}

	/**
	 * Handle PlayerManialinkPageAnswer callback
	 *
	 * @param array $callback
	 */
	public function handleManialinkPageAnswer(array $callback) {
		$actionId = $callback[1][2];

		$login  = $callback[1][1];
		$player = $this->maniaControl->playerManager->getPlayer($login);

		if ($actionId === self::ACTION_SHOW_DEDIRECORDSLIST) {
			$this->showDediRecordsList(array(), $player);
		}
	}

	/**
	 * Shows a ManiaLink list with the local records.
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function showDediRecordsList(array $chat, Player $player) {
		$width  = $this->maniaControl->manialinkManager->styleManager->getListWidgetsWidth();
		$height = $this->maniaControl->manialinkManager->styleManager->getListWidgetsHeight();

		// get PlayerList
		$records = $this->dedimaniaData->records;
		if (!$records) {
			$this->maniaControl->chat->sendInformation('There are no Dedimania records on this map!');
			return;
		}

		//create manialink
		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);
		$script    = $maniaLink->getScript();
		$paging    = new Paging();
		$script->addFeature($paging);

		// Main frame
		$frame = $this->maniaControl->manialinkManager->styleManager->getDefaultListFrame($script, $paging);
		$maniaLink->add($frame);

		// Start offsets
		$posX = -$width / 2;
		$posY = $height / 2;

		// Predefine Description Label
		$descriptionLabel = $this->maniaControl->manialinkManager->styleManager->getDefaultDescriptionLabel();
		$frame->add($descriptionLabel);

		// Headline
		$headFrame = new Frame();
		$frame->add($headFrame);
		$headFrame->setY($posY - 5);
		$array = array("Rank" => $posX + 5, "Nickname" => $posX + 18, "Login" => $posX + 70, "Time" => $posX + 101);
		$this->maniaControl->manialinkManager->labelLine($headFrame, $array);

		$index     = 0;
		$posY      = $height / 2 - 10;
		$pageFrame = null;

		foreach ($records as $listRecord) {
			if ($index % 15 === 0) {
				$pageFrame = new Frame();
				$frame->add($pageFrame);
				$posY = $height / 2 - 10;
				$paging->addPage($pageFrame);
			}

			$recordFrame = new Frame();
			$pageFrame->add($recordFrame);

			if ($index % 2 !== 0) {
				$lineQuad = new Quad_BgsPlayerCard();
				$recordFrame->add($lineQuad);
				$lineQuad->setSize($width, 4);
				$lineQuad->setSubStyle($lineQuad::SUBSTYLE_BgPlayerCardBig);
				$lineQuad->setZ(0.001);
			}

			if (strlen($listRecord->nickName) < 2) {
				$listRecord->nickName = $listRecord->login;
			}
			$array = array($listRecord->rank => $posX + 5, '$fff' . $listRecord->nickName => $posX + 18, $listRecord->login => $posX + 70, Formatter::formatTime($listRecord->best) => $posX + 101);
			$this->maniaControl->manialinkManager->labelLine($recordFrame, $array);

			$recordFrame->setY($posY);

			$posY -= 4;
			$index++;
		}

		// Render and display xml
		$this->maniaControl->manialinkManager->displayWidget($maniaLink, $player, 'DediRecordsList');
	}

	/**
	 * Function to retrieve the dedimania records on the current map
	 *
	 * @return array|RecordData
	 */
	public function getDedimaniaRecords() {
		if ($this->dedimaniaData->records) {
			return $this->dedimaniaData->records;
		}
		return null;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::unload()
	 */
	public function unload() {
	}
}