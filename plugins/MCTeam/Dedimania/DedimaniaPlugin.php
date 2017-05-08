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
use ManiaControl\Callbacks\Structures\TrackMania\OnWayPointEventStructure;
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\Commands\CommandListener;
use ManiaControl\Files\AsyncHttpRequest;
use ManiaControl\Logger;
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
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class DedimaniaPlugin implements CallbackListener, CommandListener, TimerListener, Plugin {
	/*
	 * Constants
	 */
	const ID                              = 8;
	const VERSION                         = 0.2;
	const AUTHOR                          = 'MCTeam';
	const NAME                            = 'Dedimania Plugin';
	const MLID_DEDIMANIA                  = 'Dedimania.ManialinkId';
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
	const DEDIMANIA_DEBUG                 = false;

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;
	// TODO: there are several spots where $dedimaniaData is null - fix those (look for !$this->dedimaniaData) -> Tried to fix (Remove this TODO if it works in next release)
	/** @var DedimaniaData $dedimaniaData */
	private $dedimaniaData   = null;
	private $updateManialink = false;
	private $checkpoints     = array();
	private $init            = false;

	private $request = null;

	/**
	 * @see \ManiaControl\Plugins\Plugin::prepare()
	 */
	public static function prepare(ManiaControl $maniaControl) {
		$servers = $maniaControl->getServer()->getAllServers();
		foreach ($servers as $server) {
			$maniaControl->getSettingManager()->initSetting(get_class(), self::SETTING_DEDIMANIA_CODE . $server->login . '$l', '');
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

		// Settings
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_ENABLE, true);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_TITLE, 'Dedimania');
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_POSX, -139);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_POSY, 7);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_WIDTH, 40);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_LINE_HEIGHT, 4);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_LINE_COUNT, 12);

		// Callbacks
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::BEGINMAP, $this, 'handleBeginMap');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::ENDMAP, $this, 'handleMapEnd');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 'handleManialinkPageAnswer');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(PlayerManager::CB_PLAYERCONNECT, $this, 'handlePlayerConnect');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(PlayerManager::CB_PLAYERDISCONNECT, $this, 'handlePlayerDisconnect');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::TM_ONWAYPOINT, $this, 'handleCheckpointCallback');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::TM_ONFINISHLINE, $this, 'handleFinishCallback');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::TM_ONLAPFINISH, $this, 'handleFinishCallback');

		$this->maniaControl->getTimerManager()->registerTimerListening($this, 'updateEverySecond', 1000);
		$this->maniaControl->getTimerManager()->registerTimerListening($this, 'handleEveryHalfMinute', 1000 * 30);
		$this->maniaControl->getTimerManager()->registerTimerListening($this, 'updatePlayerList', 1000 * 60 * 3);

		$this->maniaControl->getCommandManager()->registerCommandListener(array('dedirecs',
		                                                                        'dedirecords'), $this, 'showDediRecordsList', false, 'Shows a list of Dedimania records of the current map.');

		// Open session
		$serverInfo    = $this->maniaControl->getServer()->getInfo();
		$serverVersion = $this->maniaControl->getClient()->getVersion();

		$packMask = $this->maniaControl->getMapManager()->getCurrentMap()->environment;

		$dedimaniaCode = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_DEDIMANIA_CODE . $serverInfo->login . '$l');
		if (!$dedimaniaCode) {
			throw new \Exception("No Dedimania Code Specified, check the settings!");
		}

		//$this->request = AsynchronousFileReader::newRequestTest(self::DEDIMANIA_URL);

		$this->dedimaniaData = new DedimaniaData($serverInfo->login, $dedimaniaCode, $serverInfo->path, $packMask, $serverVersion);

		$this->openDedimaniaSession();
	}

	/**
	 * Opens the Dedimania Session
	 */
	private function openDedimaniaSession() {
		if (self::DEDIMANIA_DEBUG) {
			var_dump("Dedi Debug: DedimaniaData before Connecting");
			var_dump($this->dedimaniaData);
		}

		$content = $this->encode_request(self::DEDIMANIA_OPEN_SESSION, array($this->dedimaniaData->toArray()));

		if (self::DEDIMANIA_DEBUG) {
			var_dump("Dedi Debug: XML-RPC Content on Connecting");
			var_dump($content);
		}


		//$this->maniaControl->fileReader->postDataTest($this->request, self::DEDIMANIA_URL, function ($data, $error) {
		$asyncHttpRequest = new AsyncHttpRequest($this->maniaControl, self::DEDIMANIA_URL);
		$asyncHttpRequest->setCallable(function ($data, $error) {
			Logger::log("Try to connect on Dedimania");

			if (!$data || $error) {
				Logger::logError("Dedimania Error while opening session: '{$error}'");
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
				Logger::log("Dedimania connection successfully established.");
				$this->fetchDedimaniaRecords();
				$this->init = true;
			} else {
				Logger::logError("Error while opening Dedimania Connection");
			}

			if (self::DEDIMANIA_DEBUG) {
				var_dump("Dedi Debug: Connect Method Response");
				var_dump($methodResponse);

				var_dump("Dedi Debug: DedimaniaData after Startup");
				var_dump($this->dedimaniaData);
			}
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
		$content = $this->encode_request(self::DEDIMANIA_GET_RECORDS, $data);
		//var_dump("get recs");
		$asyncHttpRequest = new AsyncHttpRequest($this->maniaControl, self::DEDIMANIA_URL);
		$asyncHttpRequest->setCallable(function ($data, $error) {
			if ($error) {
				Logger::logError('Dedimania Error while fetching records: ' . $error);
			}

			$data = $this->decode($data);

			//Data[0][0] can be false in error case like map has no checkpoints
			if (!is_array($data) || empty($data) || $data[0][0] == false) {
				return;
			}

			//var_dump($data);

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

			if (self::DEDIMANIA_DEBUG) {
				var_dump("Dedimania Records Fetched");
			}

			$this->updateManialink = true;
			$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_DEDIMANIA_UPDATED, $this->dedimaniaData->records);
		});

		$asyncHttpRequest->setContent($content);
		$asyncHttpRequest->setCompression(true);
		$asyncHttpRequest->setTimeout(500);
		$asyncHttpRequest->postData();

		return true;
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
	 * Handle 1 Second Callback
	 */
	public function updateEverySecond() {
		if (!$this->updateManialink) {
			return;
		}
		$this->updateManialink = false;

		if (self::DEDIMANIA_DEBUG) {
			var_dump($this->dedimaniaData);
			var_dump("Dedimania Debug: Update Manialink");
		}

		if ($this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_ENABLE)) {
			$this->sendManialink();
		}
	}

	/**
	 * Builds and Sends the Manialink
	 */
	private function sendManialink() {
		if (!isset($this->dedimaniaData) || !isset($this->dedimaniaData->records)) {
			return null;
		}
		$records = $this->dedimaniaData->records;

		$title        = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_TITLE);
		$posX         = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_POSX);
		$posY         = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_POSY);
		$width        = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_WIDTH);
		$lines        = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_LINE_COUNT);
		$lineHeight   = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_LINE_HEIGHT);
		$labelStyle   = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultLabelStyle();
		$quadStyle    = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultQuadStyle();
		$quadSubstyle = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultQuadSubstyle();


		$manialink = new ManiaLink(self::MLID_DEDIMANIA);
		$frame     = new Frame();
		$manialink->addChild($frame);
		$frame->setPosition($posX, $posY);

		$backgroundQuad = new Quad();
		$frame->addChild($backgroundQuad);
		$backgroundQuad->setVerticalAlign($backgroundQuad::TOP);
		$height = 7. + $lines * $lineHeight;
		$backgroundQuad->setSize($width * 1.05, $height);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);

		$titleLabel = new Label();
		$frame->addChild($titleLabel);
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
			$frame->addChild($recordFrame);
			$recordFrame->setPosition(0, $y);

			/*$backgroundQuad = new Quad();
			$recordFrame->addChild($backgroundQuad);
			$backgroundQuad->setSize($width * 1.04, $lineHeight * 1.4);
			$backgroundQuad->setStyles($quadStyle, $quadSubstyle);*/

			//Rank
			$rankLabel = new Label();
			$recordFrame->addChild($rankLabel);
			$rankLabel->setHorizontalAlign($rankLabel::LEFT);
			$rankLabel->setX($width * -0.47);
			$rankLabel->setSize($width * 0.06, $lineHeight);
			$rankLabel->setTextSize(1);
			$rankLabel->setTextPrefix('$o');
			$rankLabel->setText($record->rank);
			$rankLabel->setTextEmboss(true);

			//Name
			$nameLabel = new Label();
			$recordFrame->addChild($nameLabel);
			$nameLabel->setHorizontalAlign($nameLabel::LEFT);
			$nameLabel->setX($width * -0.4);
			$nameLabel->setSize($width * 0.6, $lineHeight);
			$nameLabel->setTextSize(1);
			$nameLabel->setText($record->nickName);
			$nameLabel->setTextEmboss(true);

			//Time
			$timeLabel = new Label();
			$recordFrame->addChild($timeLabel);
			$timeLabel->setHorizontalAlign($timeLabel::RIGHT);
			$timeLabel->setX($width * 0.47);
			$timeLabel->setSize($width * 0.25, $lineHeight);
			$timeLabel->setTextSize(1);
			$timeLabel->setText(Formatter::formatTime($record->best));
			$timeLabel->setTextEmboss(true);
		}

		$this->maniaControl->getManialinkManager()->sendManialink($manialink);
	}

	/**
	 * Handle 1 Minute Callback
	 */
	public function handleEveryHalfMinute() {
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

		//$this->maniaControl->fileReader->postDataTest($this->request, self::DEDIMANIA_URL, function ($data, $error) {
		$asyncHttpRequest = new AsyncHttpRequest($this->maniaControl, self::DEDIMANIA_URL);
		$asyncHttpRequest->setCallable(function ($data, $error) {
			if ($error) {
				//Reopen session in Timeout case
				$this->openDedimaniaSession();
				//Logger::logError("Dedimania Error while checking session: " . $error);
			}

			$data = $this->decode($data);
			if (!is_array($data) || empty($data)) {
				return;
			}
			//var_dump("SESSION CHECK");
			//var_dump($data);

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

			if (self::DEDIMANIA_DEBUG) {
				var_dump("Dedi Debug: Session Check ResponseData ");
				var_dump($responseData);
			}
		});
		$asyncHttpRequest->setContent($content);
		$asyncHttpRequest->setCompression(true);
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
		$content = $this->encode_request(self::DEDIMANIA_PLAYERCONNECT, $data);

		$asyncHttpRequest = new AsyncHttpRequest($this->maniaControl, self::DEDIMANIA_URL);
		$asyncHttpRequest->setCallable(function ($data, $error) use (&$player) {
			if ($error) {
				Logger::logError("Dedimania Error while player connect: " . $error);
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

			if ($this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_ENABLE)) {
				$this->sendManialink();
			}
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
	public function handlePlayerDisconnect(Player $player) {
		if (!isset($this->dedimaniaData)) {
			return;
		}
		$this->dedimaniaData->removePlayer($player->login);

		// Send Dedimania request
		$data    = array($this->dedimaniaData->sessionId, $player->login, '');
		$content = $this->encode_request(self::DEDIMANIA_PLAYERDISCONNECT, $data);

		$asyncHttpRequest = new AsyncHttpRequest($this->maniaControl, self::DEDIMANIA_URL);
		$asyncHttpRequest->setCallable(function ($data, $error) {
			if ($error) {
				Logger::logError("Dedimania Error while player disconnect: " . $error);
			}

			$data = $this->decode($data);
			if (!is_array($data) || empty($data)) {
				return;
			}

			$methodResponse = $data[0];
			if (xmlrpc_is_fault($methodResponse)) {
				$this->handleXmlRpcFault($methodResponse, self::DEDIMANIA_PLAYERDISCONNECT);
			}
		});

		$asyncHttpRequest->setContent($content);
		$asyncHttpRequest->setCompression(true);
		$asyncHttpRequest->setTimeout(500);
		$asyncHttpRequest->postData();
	}

	/**
	 * Handle Begin Map Callback
	 */
	public function handleBeginMap() {
		unset($this->dedimaniaData->records);
		$this->updateManialink = true;
		$this->fetchDedimaniaRecords(true);
	}

	/**
	 * Handle EndMap Callback
	 */
	public function handleMapEnd() {
		if (!isset($this->dedimaniaData) || !$this->dedimaniaData->records) {
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
		$content = $this->encode_request(self::DEDIMANIA_SET_CHALLENGE_TIMES, $data);

		if (self::DEDIMANIA_DEBUG) {
			var_dump("Dedimania Debug: Submitting Times at End-Map", $content);
		}

		$asyncHttpRequest = new AsyncHttpRequest($this->maniaControl, self::DEDIMANIA_URL);
		$asyncHttpRequest->setCallable(function ($data, $error) {
			if ($error) {
				Logger::logError("Dedimania Error while submitting times: " . $error);
			}

			if (self::DEDIMANIA_DEBUG) {
				var_dump("Dedimania Debug: Submit Data Response");
				var_dump($data);
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
			}

			if (self::DEDIMANIA_DEBUG) {
				var_dump("Dedimania Debug: endMap response");
				var_dump($methodResponse);
				var_dump("Dedimania Data");
				var_dump($this->dedimaniaData);
			}
		});
		$asyncHttpRequest->setContent($content);
		$asyncHttpRequest->setCompression(false);
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
		$content = $this->encode_request(self::DEDIMANIA_UPDATE_SERVER_PLAYERS, $data);

		$asyncHttpRequest = new AsyncHttpRequest($this->maniaControl, self::DEDIMANIA_URL);
		$asyncHttpRequest->setCallable(function ($data, $error) {
			if ($error) {
				Logger::logError("Dedimania Error while update playerlist: " . $error);
			}

			$data = $this->decode($data);
			if (!is_array($data) || empty($data)) {
				return;
			}

			$methodResponse = $data[0];
			if (xmlrpc_is_fault($methodResponse)) {
				$this->handleXmlRpcFault($methodResponse, self::DEDIMANIA_UPDATE_SERVER_PLAYERS);
			}
		});

		$asyncHttpRequest->setContent($content);
		$asyncHttpRequest->setCompression(true);
		$asyncHttpRequest->setTimeout(500);
		$asyncHttpRequest->postData();
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
	 * Handle Checkpoint Callback
	 *
	 * @param OnWayPointEventStructure $callback
	 */
	public function handleCheckpointCallback(OnWayPointEventStructure $structure) {
		if (!$structure->getLapTime()) {
			return;
		}

		$login = $structure->getPlayer()->login;
		if (!isset($this->checkpoints[$login])) {
			$this->checkpoints[$login] = array();
		}
		$this->checkpoints[$login][$structure->getCheckPointInLap()] = $structure->getLapTime();
	}

	/**
	 * Handle Finish Callback
	 *
	 * @param OnWayPointEventStructure $callback
	 */
	public function handleFinishCallback(OnWayPointEventStructure $structure) {
		if (!isset($this->dedimaniaData)) {
			return;
		}

		if ($structure->getRaceTime() <= 0) {
			// Invalid time
			return;
		}

		$map = $this->maniaControl->getMapManager()->getCurrentMap();
		if (!$map) {
			return;
		}

		if ($map->nbCheckpoints < 2) {
			return;
		}

		$player = $structure->getPlayer();

		$oldRecord = $this->getDedimaniaRecord($player->login);
		if ($oldRecord->nullRecord || $oldRecord && $oldRecord->best > $structure->getLapTime()) {
			// Save time
			$newRecord = new RecordData(null);

			$checkPoints = $this->getCheckpoints($player->login);
			$checkPoints = $checkPoints . "," . $structure->getLapTime();

			$newRecord->constructNewRecord($player->login, $player->nickname, $structure->getLapTime(), $checkPoints, true);

			if ($this->insertDedimaniaRecord($newRecord, $oldRecord)) {
				// Get newly saved record
				foreach ($this->dedimaniaData->records as &$record) {
					if ($record->login !== $newRecord->login) {
						continue;
					}
					$newRecord = $record;
					break;
				}

				$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_DEDIMANIA_CHANGED, $newRecord);

				// Announce record
				if ($oldRecord->nullRecord || $newRecord->rank < $oldRecord->rank) {
					// Gained rank
					$improvement = 'gained the';
				} else {
					// Only improved time
					$improvement = 'improved his/her';
				}
				$message = '$390$<$fff' . $player->nickname . '$> ' . $improvement . ' $<$ff0' . $newRecord->rank . '.$> Dedimania Record: $<$fff' . Formatter::formatTime($newRecord->best) . '$>';
				if (!$oldRecord->nullRecord) {
					$message .= ' ($<$ff0' . $oldRecord->rank . '.$> $<$fff-' . Formatter::formatTime(($oldRecord->best - $structure->getLapTime())) . '$>)';
				}
				$this->maniaControl->getChat()->sendInformation($message . '!');

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
		if (!isset($this->dedimaniaData) || !isset($this->dedimaniaData->records)) {
			return new RecordData(null);
		}
		$records = $this->dedimaniaData->records;
		foreach ($records as &$record) {
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
		if (!isset($this->dedimaniaData) || !isset($this->dedimaniaData->records) || $newRecord->nullRecord) {
			return false;
		}

		$insert = false;

		// Get max possible rank
		$maxRank = $this->dedimaniaData->getPlayerMaxRank($newRecord->login);

		// Loop through existing records
		foreach ($this->dedimaniaData->records as $key => &$record) {
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
			$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_DEDIMANIA_UPDATED, $this->dedimaniaData->records);
			return;
		}

		$this->dedimaniaData->sortRecords();

		// Update ranks
		$rank = 1;
		foreach ($this->dedimaniaData->records as &$record) {
			$record->rank = $rank;
			$rank++;
		}
		$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_DEDIMANIA_UPDATED, $this->dedimaniaData->records);
	}

	/**
	 * Update the replay values for the given record
	 *
	 * @param RecordData $record
	 */
	private function setRecordReplays(RecordData &$record) {
		// Set validation replay
		$validationReplay = $this->maniaControl->getServer()->getValidationReplay($record->login);
		if ($validationReplay) {
			$record->vReplay = $validationReplay;
		}

		// Set ghost replay
		if ($record->rank <= 1) {
			$dataDirectory = $this->maniaControl->getServer()->getDirectory()->getGameDataFolder();
			if (!isset($this->dedimaniaData->directoryAccessChecked)) {
				$access = $this->maniaControl->getServer()->checkAccess($dataDirectory);
				if (!$access) {
					trigger_error("No access to the servers data directory. Can't retrieve ghost replays.");
				}
				$this->dedimaniaData->directoryAccessChecked = $access;
			}
			if ($this->dedimaniaData->directoryAccessChecked) {
				$ghostReplay = $this->maniaControl->getServer()->getGhostReplay($record->login);
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
		$player = $this->maniaControl->getPlayerManager()->getPlayer($login);

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
		$width  = $this->maniaControl->getManialinkManager()->getStyleManager()->getListWidgetsWidth();
		$height = $this->maniaControl->getManialinkManager()->getStyleManager()->getListWidgetsHeight();

		// get PlayerList
		$records = $this->dedimaniaData->records;
		if (!$records) {
			$this->maniaControl->getChat()->sendInformation('There are no Dedimania records on this map!');
			return;
		}

		//create manialink
		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);
		$script    = $maniaLink->getScript();
		$paging    = new Paging();
		$script->addFeature($paging);

		// Main frame
		$frame = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultListFrame($script, $paging);
		$maniaLink->addChild($frame);

		// Start offsets
		$posX = -$width / 2;
		$posY = $height / 2;

		// Predefine Description Label
		$descriptionLabel = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultDescriptionLabel();
		$frame->addChild($descriptionLabel);

		// Headline
		$headFrame = new Frame();
		$frame->addChild($headFrame);
		$headFrame->setY($posY - 5);
		$array = array('Rank' => $posX + 5, 'Nickname' => $posX + 18, 'Login' => $posX + 70, 'Time' => $posX + 101);
		$this->maniaControl->getManialinkManager()->labelLine($headFrame, $array);

		$index     = 0;
		$posY      = $height / 2 - 10;
		$pageFrame = null;

		foreach ($records as $listRecord) {
			if ($index % 15 === 0) {
				$pageFrame = new Frame();
				$frame->addChild($pageFrame);
				$posY = $height / 2 - 10;
				$paging->addPageControl($pageFrame);
			}

			$recordFrame = new Frame();
			$pageFrame->addChild($recordFrame);

			if ($index % 2 !== 0) {
				$lineQuad = new Quad_BgsPlayerCard();
				$recordFrame->addChild($lineQuad);
				$lineQuad->setSize($width, 4);
				$lineQuad->setSubStyle($lineQuad::SUBSTYLE_BgPlayerCardBig);
				$lineQuad->setZ(0.001);
			}

			if (strlen($listRecord->nickName) < 2) {
				$listRecord->nickName = $listRecord->login;
			}
			$array = array($listRecord->rank => $posX + 5, '$fff' . $listRecord->nickName => $posX + 18, $listRecord->login => $posX + 70, Formatter::formatTime($listRecord->best) => $posX + 101);
			$this->maniaControl->getManialinkManager()->labelLine($recordFrame, $array);

			$recordFrame->setY($posY);

			$posY -= 4;
			$index++;
		}

		// Render and display xml
		$this->maniaControl->getManialinkManager()->displayWidget($maniaLink, $player, 'DediRecordsList');
	}

	/**
	 * Function to retrieve the dedimania records on the current map
	 *
	 * @return RecordData[]
	 */
	public function getDedimaniaRecords() {
		if ($this->dedimaniaData && $this->dedimaniaData->records) {
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
