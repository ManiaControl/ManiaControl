<?php

namespace MCTeam;

use FML\Controls\Frame;
use FML\Controls\Gauge;
use FML\Controls\Label;
use FML\Controls\Quad;
use FML\ManiaLink;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Callbacks\Callbacks;
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\ColorUtil;
use ManiaControl\ManiaControl;
use ManiaControl\Maps\Map;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;
use ManiaControl\Plugins\Plugin;
use ManiaControl\Settings\SettingManager;

/**
 * ManiaControl Karma Plugin
 *
 * @author    kremsy and steeffeen
 * @copyright ManiaControl Copyright © 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class KarmaPlugin implements CallbackListener, TimerListener, Plugin {
	/*
	 * Constants
	 */
	const ID                      = 2;
	const VERSION                 = 0.1;
	const MLID_KARMA              = 'KarmaPlugin.MLID';
	const TABLE_KARMA             = 'mc_karma';
	const CB_KARMA_CHANGED        = 'KarmaPlugin.Changed';
	const CB_KARMA_MXUPDATED      = 'KarmaPlugin.MXUpdated';
	const SETTING_AVAILABLE_VOTES = 'Available Votes (X-Y: Comma separated)';
	const SETTING_WIDGET_ENABLE   = 'Enable Karma Widget';
	const SETTING_WIDGET_TITLE    = 'Widget-Title';
	const SETTING_WIDGET_POSX     = 'Widget-Position: X';
	const SETTING_WIDGET_POSY     = 'Widget-Position: Y';
	const SETTING_WIDGET_WIDTH    = 'Widget-Size: Width';
	const SETTING_WIDGET_HEIGHT   = 'Widget-Size: Height';
	const SETTING_NEWKARMA        = 'Enable "new karma" (percentage), disable = RASP karma';
	const STAT_PLAYER_MAPVOTES    = 'Voted Maps';

	/*
	 * Constants MX Karma
	 */
	const SETTING_WIDGET_DISPLAY_MX  = 'Display MX-Karma in Widget';
	const SETTING_MX_KARMA_ACTIVATED = 'Activate MX-Karma';
	const SETTING_MX_KARMA_IMPORTING = 'Import old MX-Karmas';
	const MX_IMPORT_TABLE            = 'mc_karma_mximport';
	const MX_KARMA_URL               = 'http://karma.mania-exchange.com/api2/';
	const MX_KARMA_STARTSESSION      = 'startSession';
	const MX_KARMA_ACTIVATESESSION   = 'activateSession';
	const MX_KARMA_SAVEVOTES         = 'saveVotes';
	const MX_KARMA_GETMAPRATING      = 'getMapRating';

	/*
	 * Private Properties
	 */
	/**
	 * @var ManiaControl $maniaControl
	 */
	private $maniaControl = null;
	private $updateManialink = false;
	/**
	 * @var ManiaLink $manialink
	 */
	private $manialink = null;
	private $mxKarma = array();

	/**
	 * @see \ManiaControl\Plugins\Plugin
	 */
	public static function prepare(ManiaControl $maniaControl) {
		$maniaControl->settingManager->initSetting(get_class(), self::SETTING_MX_KARMA_ACTIVATED, true);
		$maniaControl->settingManager->initSetting(get_class(), self::SETTING_MX_KARMA_IMPORTING, true);
		$maniaControl->settingManager->initSetting(get_class(), self::SETTING_WIDGET_DISPLAY_MX, true);
		$servers = $maniaControl->server->getAllServers();
		foreach($servers as $server) {
			$maniaControl->settingManager->initSetting(get_class(), '$l[http://karma.mania-exchange.com/auth/getapikey?server=' . $server->login . ']MX Karma Code for ' . $server->login . '$l', '');
		}
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::load()
	 */
	public function load(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Init database
		$this->initTables();

		// Init settings
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_AVAILABLE_VOTES, '-2,2');
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_WIDGET_ENABLE, true);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_WIDGET_TITLE, 'Map-Karma');
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_WIDGET_POSX, 160 - 27.5);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_WIDGET_POSY, 90 - 10 - 6);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_WIDGET_WIDTH, 25.);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_WIDGET_HEIGHT, 12.);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_NEWKARMA, true);

		// Register for callbacks
		$this->maniaControl->timerManager->registerTimerListening($this, 'handle1Second', 1000);
		$this->maniaControl->callbackManager->registerCallbackListener(Callbacks::BEGINMAP, $this, 'handleBeginMap');
		$this->maniaControl->callbackManager->registerCallbackListener(Callbacks::BEGINMAP, $this, 'importMxKarmaVotes');
		$this->maniaControl->callbackManager->registerCallbackListener(Callbacks::ENDMAP, $this, 'sendMxKarmaVotes');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERCONNECT, $this, 'handlePlayerConnect');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERCHAT, $this, 'handlePlayerChat');
		$this->maniaControl->callbackManager->registerCallbackListener(SettingManager::CB_SETTINGS_CHANGED, $this, 'updateSettings');

		// Define player stats
		$this->maniaControl->statisticManager->defineStatMetaData(self::STAT_PLAYER_MAPVOTES);

		// Register Stat in Simple StatsList
		$this->maniaControl->statisticManager->simpleStatsList->registerStat(self::STAT_PLAYER_MAPVOTES, 100, "VM");

		$this->updateManialink = true;

		// Open MX-Karma Session
		$this->mxKarmaOpenSession();
		$this->mxKarma['startTime'] = time();

		return true;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::unload()
	 */
	public function unload() {
		$this->maniaControl->manialinkManager->hideManialink(self::MLID_KARMA);
		$this->maniaControl->callbackManager->unregisterCallbackListener($this);
		$this->maniaControl->timerManager->unregisterTimerListenings($this);
		unset($this->maniaControl);
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
		return 'Karma Plugin';
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
		return 'steeffeen and kremsy';
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getDescription()
	 */
	public static function getDescription() {
		return 'Plugin offering Karma Voting for Maps.';
	}

	/**
	 * Handle ManiaControl 1 Second callback
	 *
	 * @param $time
	 */
	public function handle1Second($time) {
		if (!$this->updateManialink) {
			return;
		}

		$displayMxKarma = $this->maniaControl->settingManager->getSetting($this, self::SETTING_WIDGET_DISPLAY_MX);

		// Get players
		$players = $this->updateManialink;
		if ($players === true) {
			$players = $this->maniaControl->playerManager->getPlayers();
		}
		$this->updateManialink = false;

		// Get map karma
		$map = $this->maniaControl->mapManager->getCurrentMap();

		// Display the mx Karma if the setting is choosen and the MX session is available
		if ($displayMxKarma && isset($this->mxKarma['session']) && isset($this->mxKarma['voteCount'])) {
			$karma     = $this->mxKarma['modeVoteAverage'] / 100;
			$voteCount = $this->mxKarma['modeVoteCount'];
		} else {
			$karma     = $this->getMapKarma($map);
			$votes     = $this->getMapVotes($map);
			$voteCount = $votes['count'];
		}

		if ($this->maniaControl->settingManager->getSetting($this, self::SETTING_WIDGET_ENABLE)) {
			// Build karma manialink
			$this->buildManialink();

			// Update karma gauge & label
			/**
			 * @var Gauge $karmaGauge
			 */
			$karmaGauge = $this->manialink->karmaGauge;
			/**
			 * @var Label $karmaLabel
			 */
			$karmaLabel = $this->manialink->karmaLabel;
			if (is_numeric($karma) && $voteCount > 0) {
				$karma = floatval($karma);
				$karmaGauge->setRatio($karma + 0.15 - $karma * 0.15);
				$karmaColor = ColorUtil::floatToStatusColor($karma);
				$karmaGauge->setColor($karmaColor . '7');
				$karmaLabel->setText('  ' . round($karma * 100.) . '% (' . $voteCount . ')');
			} else {
				$karmaGauge->setRatio(0.);
				$karmaGauge->setColor('00fb');
				$karmaLabel->setText('-');
			}

			// Loop players
			foreach($players as $login => $player) {
				// Get player vote
				// TODO: show the player his own vote in some way
				// $vote = $this->getPlayerVote($player, $map);
				// $votesFrame = $this->manialink->votesFrame;
				// $votesFrame->removeChildren();

				// Send manialink
				$this->maniaControl->manialinkManager->sendManialink($this->manialink, $login);
			}
		}
	}

	/**
	 * Handle BeginMap ManiaControl callback
	 *
	 * @param Map $map
	 */
	public function handleBeginMap(Map $map) {
		// send Map Karma to MX from previous Map
		if (isset($this->mxKarma['map'])) {
			$votes = array();
			foreach($this->mxKarma['votes'] as $login => $value) {
				$player = $this->maniaControl->playerManager->getPlayer($login);
				array_push($votes, array("login" => $login, "nickname" => $player->rawNickname, "vote" => $value));
			}
			$this->postKarmaVotes($this->mxKarma['map'], $votes);
			unset($this->mxKarma['map']);
		}

		unset($this->mxKarma['votes']);
		$this->mxKarma['startTime'] = time();
		$this->updateManialink      = true;

		// Get Karma votes at begin of map
		$this->getMxKarmaVotes();
	}

	/**
	 * Handle PlayerConnect callback
	 *
	 * @param \ManiaControl\Players\Player $player
	 */
	public function handlePlayerConnect(Player $player) {
		if (!$player) {
			return;
		}
		$this->queryManialinkUpdateFor($player);

		// Get Mx Karma Vote for Player
		$this->getMxKarmaVotes($player);
	}

	/**
	 * Handle PlayerChat callback
	 *
	 * @param array $chatCallback
	 */
	public function handlePlayerChat(array $chatCallback) {
		$login  = $chatCallback[1][1];
		$player = $this->maniaControl->playerManager->getPlayer($login);
		if (!$player) {
			return;
		}
		$message = $chatCallback[1][2];
		if ($chatCallback[1][3]) {
			$message = substr($message, 1);
		}
		if (preg_match('/[^+-]/', $message)) {
			return;
		}
		$countPositive = substr_count($message, '+');
		$countNegative = substr_count($message, '-');
		if ($countPositive <= 0 && $countNegative <= 0) {
			return;
		}
		$vote    = $countPositive - $countNegative;
		$success = $this->handleVote($player, $vote);
		if (!$success) {
			$this->maniaControl->chat->sendError('Error occurred.', $player->login);
			return;
		}
		$this->maniaControl->chat->sendSuccess('Vote updated!', $player->login);
	}

	/**
	 * Handle a vote done by a player
	 *
	 * @param Player $player
	 * @param int    $vote
	 * @return bool
	 */
	private function handleVote(Player $player, $vote) {
		// Check vote
		$votesSetting = $this->maniaControl->settingManager->getSetting($this, self::SETTING_AVAILABLE_VOTES);
		$votes        = explode(',', $votesSetting);
		$voteLow      = intval($votes[0]);
		$voteHigh     = $voteLow + 2;
		if (isset($votes[1])) {
			$voteHigh = intval($votes[1]);
		}
		if ($vote < $voteLow || $vote > $voteHigh) {
			return false;
		}

		// Calculate actual voting
		$vote -= $voteLow;
		$voteHigh -= $voteLow;
		$vote /= $voteHigh;

		// Save vote
		$map = $this->maniaControl->mapManager->getCurrentMap();

		// Update vote in MX karma array
		if ($this->maniaControl->settingManager->getSetting($this, self::SETTING_MX_KARMA_ACTIVATED) && isset($this->mxKarma["session"])) {
			if (!isset($this->mxKarma["votes"][$player->login])) {
				$sum = $this->mxKarma["voteCount"] * $this->mxKarma["voteAverage"] + $vote * 100;
				$this->mxKarma["voteCount"]++;

				$modeSum = $this->mxKarma["modeVoteCount"] * $this->mxKarma["modeVoteAverage"] + $vote * 100;
				$this->mxKarma["modeVoteCount"]++;
			} else {
				$oldVote = $this->mxKarma["votes"][$player->login];
				$sum     = $this->mxKarma["voteCount"] * $this->mxKarma["voteAverage"] - $oldVote + $vote * 100;
				$modeSum = $this->mxKarma["modeVoteCount"] * $this->mxKarma["modeVoteAverage"] - $oldVote + $vote * 100;
			}

			$this->mxKarma["voteAverage"]           = $sum / $this->mxKarma["voteCount"];
			$this->mxKarma["modeVoteAverage"]       = $modeSum / $this->mxKarma["modeVoteCount"];
			$this->mxKarma["votes"][$player->login] = $vote * 100;
		}

		$voted = $this->getPlayerVote($player, $map);
		if (!$voted) {
			$this->maniaControl->statisticManager->incrementStat(self::STAT_PLAYER_MAPVOTES, $player, $this->maniaControl->server->index);
		}

		$success = $this->savePlayerVote($player, $map, $vote);
		if (!$success) {
			return false;
		}
		$this->maniaControl->callbackManager->triggerCallback(self::CB_KARMA_CHANGED);
		$this->updateManialink = true;
		return true;
	}

	/**
	 * Query the player to update the manialink
	 *
	 * @param Player $player
	 */
	private function queryManialinkUpdateFor(Player $player) {
		if ($this->updateManialink === true) {
			return;
		}
		if (!is_array($this->updateManialink)) {
			$this->updateManialink = array();
		}
		$this->updateManialink[$player->login] = $player;
	}

	/**
	 * Create necessary database tables
	 */
	private function initTables() {
		$mysqli = $this->maniaControl->database->mysqli;

		// Create local table
		$query = "CREATE TABLE IF NOT EXISTS `" . self::TABLE_KARMA . "` (
				`index` int(11) NOT NULL AUTO_INCREMENT,
				`mapIndex` int(11) NOT NULL,
				`playerIndex` int(11) NOT NULL,
				`vote` float NOT NULL DEFAULT '-1',
				`changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (`index`),
				UNIQUE KEY `player_map_vote` (`mapIndex`, `playerIndex`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Save players map votes' AUTO_INCREMENT=1;";
		$mysqli->query($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error, E_USER_ERROR);
		}

		// Migrate settings
		$this->maniaControl->database->migrationHelper->transferSettings('KarmaPlugin', $this);

		if (!$this->maniaControl->settingManager->getSetting($this, self::SETTING_MX_KARMA_ACTIVATED)) {
			return;
		}

		// Create mx table
		$query = "CREATE TABLE IF NOT EXISTS `" . self::MX_IMPORT_TABLE . "` (
				`index` int(11) NOT NULL AUTO_INCREMENT,
				`mapIndex` int(11) NOT NULL,
				`mapImported` tinyint(1) NOT NULL,
				`time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (`index`),
				UNIQUE KEY `mapIndex` (`mapIndex`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='MX Karma Import Table' AUTO_INCREMENT=1;";
		$mysqli->query($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error, E_USER_ERROR);
		}
	}

	/**
	 * Save the vote of the player for the map
	 *
	 * @param Player $player
	 * @param Map    $map
	 * @param float  $vote
	 * @return bool
	 */
	private function savePlayerVote(Player $player, Map $map, $vote) {
		$mysqli = $this->maniaControl->database->mysqli;
		$query  = "INSERT INTO `" . self::TABLE_KARMA . "` (
				`mapIndex`,
				`playerIndex`,
				`vote`
				) VALUES (
				{$map->index},
				{$player->index},
				{$vote}
				) ON DUPLICATE KEY UPDATE
				`vote` = VALUES(`vote`);";
		$result = $mysqli->query($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}

		return $result;
	}

	/**
	 * Get the current vote of the player for the map
	 *
	 * @param Player $player
	 * @param Map    $map
	 * @return int
	 */
	public function getPlayerVote(Player $player, Map $map) {
		$mysqli = $this->maniaControl->database->mysqli;
		$query  = "SELECT * FROM `" . self::TABLE_KARMA . "`
				WHERE `playerIndex` = {$player->index}
				AND `mapIndex` = {$map->index}
				AND `vote` >= 0;";
		$result = $mysqli->query($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		if ($result->num_rows <= 0) {
			$result->free();
			return false;
		}
		$item = $result->fetch_object();
		$result->free();
		$vote = $item->vote;
		return floatval($vote);
	}

	/**
	 * Get the current karma of the map
	 *
	 * @param Map $map
	 * @return float | bool
	 */
	public function getMapKarma(Map $map) {
		$mysqli = $this->maniaControl->database->mysqli;
		$query  = "SELECT AVG(`vote`) AS `karma` FROM `" . self::TABLE_KARMA . "`
				WHERE `mapIndex` = {$map->index}
				AND `vote` >= 0;";
		$result = $mysqli->query($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		if ($result->num_rows <= 0) {
			$result->free();
			return false;
		}
		$item = $result->fetch_object();
		$result->free();
		$karma = $item->karma;
		if ($karma === null) {
			return false;
		}
		return floatval($karma);
	}

	/**
	 * Get the current Votes for the Map
	 *
	 * @param Map $map
	 * @return array
	 */
	public function getMapVotes(Map $map) {
		$mysqli = $this->maniaControl->database->mysqli;
		$query  = "SELECT `vote`, COUNT(`vote`) AS `count` FROM `" . self::TABLE_KARMA . "`
				WHERE `mapIndex` = {$map->index}
				AND `vote` >= 0
				GROUP BY `vote`;";
		$result = $mysqli->query($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		$votes = array();
		$count = 0;
		while($vote = $result->fetch_object()) {
			$votes[$vote->vote] = $vote;
			$count += $vote->count;
		}
		$votes['count'] = $count;
		$result->free();
		return $votes;
	}

	/**
	 * Get all players votes
	 *
	 * @param Map $map
	 * @return array
	 */
	public function getMapPlayerVotes(Map $map) {

	}

	/**
	 * Build karma voting manialink if necessary
	 *
	 * @param bool $forceBuild
	 */
	private function buildManialink($forceBuild = false) {
		if (is_object($this->manialink) && !$forceBuild) {
			return;
		}

		$title        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_WIDGET_TITLE);
		$pos_x        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_WIDGET_POSX);
		$pos_y        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_WIDGET_POSY);
		$width        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_WIDGET_WIDTH);
		$height       = $this->maniaControl->settingManager->getSetting($this, self::SETTING_WIDGET_HEIGHT);
		$labelStyle   = $this->maniaControl->manialinkManager->styleManager->getDefaultLabelStyle();
		$quadStyle    = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadStyle();
		$quadSubstyle = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadSubstyle();

		$manialink = new ManiaLink(self::MLID_KARMA);

		$frame = new Frame();
		$manialink->add($frame);
		$frame->setPosition($pos_x, $pos_y);

		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setY($height * 0.15);
		$backgroundQuad->setSize($width, $height);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);

		$titleLabel = new Label();
		$frame->add($titleLabel);
		$titleLabel->setY($height * 0.36);
		$titleLabel->setWidth($width * 0.85);
		$titleLabel->setStyle($labelStyle);
		$titleLabel->setTranslate(true);
		$titleLabel->setTextSize(1);
		$titleLabel->setScale(0.90);
		$titleLabel->setText($title);

		$karmaGauge = new Gauge();
		$frame->add($karmaGauge);
		$karmaGauge->setSize($width * 0.95, $height * 0.92);
		$karmaGauge->setDrawBg(false);
		$manialink->karmaGauge = $karmaGauge;

		$karmaLabel = new Label();
		$frame->add($karmaLabel);
		$karmaLabel->setPosition(0, -0.4, 1);
		$karmaLabel->setSize($width * 0.9, $height * 0.9);
		$karmaLabel->setStyle($labelStyle);
		$karmaLabel->setTextSize(1);
		$manialink->karmaLabel = $karmaLabel;

		$votesFrame = new Frame();
		$frame->add($votesFrame);
		$manialink->votesFrame = $votesFrame;

		$this->manialink = $manialink;
	}

	/**
	 * Update Settings
	 *
	 * @param $class
	 * @param $settingName
	 * @param $value
	 */
	public function updateSettings($class, $settingName, $value) {
		if (!$class = get_class()) {
			return;
		}

		$serverLogin = $this->maniaControl->server->login;
		if ($settingName == '$l[http://karma.mania-exchange.com/auth/getapikey?server=' . $serverLogin . ']MX Karma Code for ' . $serverLogin . '$l') {
			$this->mxKarmaOpenSession();
		}

		if ($settingName == 'Enable Karma Widget' && $value == true) {
			$this->updateManialink = true;
			$this->handle1Second(time());
		} elseif ($settingName == 'Enable Karma Widget' && $value == false) {
			$this->updateManialink = false;
			$ml                    = new ManiaLink(self::MLID_KARMA);
			$mltext                = $ml->render()->saveXML();
			$this->maniaControl->manialinkManager->sendManialink($mltext);
		}
	}

	/**
	 * Open a Mx Karma Session
	 */
	private function mxKarmaOpenSession() {
		if (!$this->maniaControl->settingManager->getSetting($this, self::SETTING_MX_KARMA_ACTIVATED)) {
			return;
		}

		$serverLogin = $this->maniaControl->server->login;
		$mxKarmaCode = $this->maniaControl->settingManager->getSetting($this, '$l[http://karma.mania-exchange.com/auth/getapikey?server=' . $serverLogin . ']MX Karma Code for ' . $serverLogin . '$l');

		if ($mxKarmaCode == '') {
			return;
		}

		$applicationIdentifier = 'ManiaControl v' . ManiaControl::VERSION;
		$testMode              = 'true';

		$query = self::MX_KARMA_URL . self::MX_KARMA_STARTSESSION;
		$query .= '?serverLogin=' . $serverLogin;
		$query .= '&applicationIdentifier=' . urlencode($applicationIdentifier);
		$query .= '&testMode=' . $testMode;

		$this->mxKarma['connectionInProgress'] = true;

		$self = $this;
		$this->maniaControl->fileReader->loadFile($query, function ($data, $error) use (&$self, $mxKarmaCode) {
			if (!$error) {
				$data = json_decode($data);
				if ($data->success) {
					$self->mxKarma['session'] = $data->data;
					$self->activateSession($mxKarmaCode);
				} else {
					$self->maniaControl->log("Error while authenticating on Mania-Exchange Karma");
					// TODO remove temp trigger
					$self->maniaControl->errorHandler->triggerDebugNotice("Error while authenticating on Mania-Exchange Karma " . $data->data->message);
					$self->mxKarma['connectionInProgress'] = false;
				}
			} else {
				$self->maniaControl->log($error);
				// TODO remove temp trigger
				$self->maniaControl->errorHandler->triggerDebugNotice("Error while authenticating on Mania-Exchange Karma " . $error);
				$self->mxKarma['connectionInProgress'] = false;
			}
		}, "application/json", 1000);
	}

	/**
	 * Activates the MX-Karma Session
	 *
	 * @param $mxKarmaCode
	 */
	private function activateSession($mxKarmaCode) {
		$hash = $this->buildActivationHash($this->mxKarma['session']->sessionSeed, $mxKarmaCode);

		$query = self::MX_KARMA_URL . self::MX_KARMA_ACTIVATESESSION;
		$query .= '?sessionKey=' . urlencode($this->mxKarma['session']->sessionKey);
		$query .= '&activationHash=' . urlencode($hash);

		$self = $this;
		$this->maniaControl->fileReader->loadFile($query, function ($data, $error) use (&$self, $query) {
			if (!$error) {
				$data = json_decode($data);
				if ($data->success && $data->data->activated) {
					$self->maniaControl->log("Successfully authenticated on Mania-Exchange Karma");
					$self->mxKarma['connectionInProgress'] = false;

					// Fetch the Mx Karma Votes
					$self->getMxKarmaVotes();
				} else {
					$self->maniaControl->log("Error while authenticating on Mania-Exchange Karma " . $data->data->message);
					// TODO remove temp trigger
					$self->maniaControl->errorHandler->triggerDebugNotice("Error while authenticating on Mania-Exchange Karma " . $data->data->message . " url Query " . $query);
					$self->mxKarma['connectionInProgress'] = false;
				}
			} else {
				// TODO remove temp trigger
				$self->maniaControl->errorHandler->triggerDebugNotice("Error while authenticating on Mania-Exchange Karma " . $error);
				$self->maniaControl->log($error);
				$self->mxKarma['connectionInProgress'] = false;
			}
		}, "application/json", 1000);
	}

	/**
	 * Fetch the mxKarmaVotes for the current map
	 */
	public function getMxKarmaVotes(Player $player = null) {
		if (!$this->maniaControl->settingManager->getSetting($this, self::SETTING_MX_KARMA_ACTIVATED)) {
			return;
		}

		if (!isset($this->mxKarma['session'])) {
			if (!isset($this->mxKarma['connectionInProgress']) || !$this->mxKarma['connectionInProgress']) {
				$this->mxKarmaOpenSession();
			}
			return;
		}

		$map = $this->maniaControl->mapManager->getCurrentMap();

		$properties = array();

		$gameMode = $this->maniaControl->server->getGameMode(true);
		if ($gameMode == 'Script') {
			$scriptName             = $this->maniaControl->client->getScriptName();
			$properties['gamemode'] = $scriptName["CurrentValue"];
		} else {
			$properties['gamemode'] = $gameMode;
		}

		$properties['titleid'] = $this->maniaControl->server->titleId;
		$properties['mapuid']  = $map->uid;

		if (!$player) {
			$properties['getvotesonly'] = false;
			$properties['playerlogins'] = array();
			foreach($this->maniaControl->playerManager->getPlayers() as $plyr) {
				/**
				 * @var Player $player
				 */
				$properties['playerlogins'][] = $plyr->login;
			}
		} else {
			$properties['getvotesonly'] = true;
			$properties['playerlogins'] = array($player->login);
		}

		$content = json_encode($properties);
		$self    = $this;
		$this->maniaControl->fileReader->postData(self::MX_KARMA_URL . self::MX_KARMA_GETMAPRATING . "?sessionKey=" . urlencode($this->mxKarma['session']->sessionKey), function ($data, $error) use (&$self, &$player) {
			if (!$error) {
				$data = json_decode($data);
				if ($data->success) {

					// Fetch averages if its for the whole server
					if (!$player) {
						$self->mxKarma["voteCount"]       = $data->data->votecount;
						$self->mxKarma["voteAverage"]     = $data->data->voteaverage;
						$self->mxKarma["modeVoteCount"]   = $data->data->modevotecount;
						$self->mxKarma["modeVoteAverage"] = $data->data->modevoteaverage;
					}

					foreach($data->data->votes as $votes) {
						$self->mxKarma["votes"][$votes->login] = $votes->vote;
					}

					$self->updateManialink = true;
					$self->maniaControl->callbackManager->triggerCallback($self::CB_KARMA_MXUPDATED, $self->mxKarma);
					$self->maniaControl->log("MX-Karma Votes successfully fetched");
				} else {
					$self->maniaControl->log("Error while fetching votes: " . $data->data->message);
					// TODO remove temp trigger
					$self->maniaControl->errorHandler->triggerDebugNotice("Error while fetching votes: " . $data->data->message . " " . KarmaPlugin::MX_KARMA_URL . KarmaPlugin::MX_KARMA_SAVEVOTES . "?sessionKey=" . urlencode($self->mxKarma['session']->sessionKey));
				}
			} else {
				$self->maniaControl->log($error);
			}
		}, $content, false, 'application/json');
	}

	/**
	 * Import old Karma votes to Mania-Exchange Karma
	 *
	 * @param Map $map
	 */
	public function importMxKarmaVotes(Map $map) {
		if (!$this->maniaControl->settingManager->getSetting($this, self::SETTING_MX_KARMA_ACTIVATED)) {
			return;
		}

		if (!$this->maniaControl->settingManager->getSetting($this, self::SETTING_MX_KARMA_IMPORTING)) {
			return;
		}

		if (!isset($this->mxKarma['session'])) {
			if (!isset($this->mxKarma['connectionInProgress']) || !$this->mxKarma['connectionInProgress']) {
				$this->mxKarmaOpenSession();
			}
			return;
		}

		$mysqli = $this->maniaControl->database->mysqli;
		$query  = "SELECT mapImported FROM `" . self::MX_IMPORT_TABLE . "` WHERE `mapIndex` = {$map->index};";
		$result = $mysqli->query($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return;
		}
		$vote = $result->fetch_object();

		if ($result->field_count == 0 || !$vote) {
			$query   = "SELECT vote, login, nickname FROM `" . self::TABLE_KARMA . "` k LEFT JOIN `" . PlayerManager::TABLE_PLAYERS . "` p ON  (k.playerIndex=p.index) WHERE mapIndex = {$map->index}";
			$result2 = $mysqli->query($query);
			if ($mysqli->error) {
				trigger_error($mysqli->error);
				return;
			}

			$votes = array();
			while($row = $result2->fetch_object()) {
				array_push($votes, array("login" => $row->login, "nickname" => $row->nickname, "vote" => $row->vote * 100));
			}

			$this->postKarmaVotes($map, $votes, true);

			// Flag Map as Imported in database if it is a import
			$query = "INSERT INTO `" . self::MX_IMPORT_TABLE . "` (`mapIndex`,`mapImported`) VALUES ({$map->index},true) ON DUPLICATE KEY UPDATE `mapImported` = true;";
			$mysqli->query($query);
			if ($mysqli->error) {
				trigger_error($mysqli->error);
			}

			$result2->free();
		}
		$result->free_result();

		return;
	}

	/**
	 * Save Mx Karma Votes at Mapend
	 */
	public function sendMxKarmaVotes(Map $map) {
		if (!$this->maniaControl->settingManager->getSetting($this, self::SETTING_MX_KARMA_ACTIVATED)) {
			return;
		}

		if (!isset($this->mxKarma['session'])) {
			if (!isset($this->mxKarma['connectionInProgress']) || !$this->mxKarma['connectionInProgress']) {
				$this->mxKarmaOpenSession();
			}
			return;
		}

		if (!isset($this->mxKarma['votes']) || count($this->mxKarma['votes']) == 0) {
			return;
		}

		$this->mxKarma['map'] = $map;
	}

	/**
	 * Post the Karma votes to MX-Karma
	 *
	 * @param Map   $map
	 * @param array $votes
	 * @param bool  $import
	 */
	private function postKarmaVotes(Map $map, array $votes, $import = false) {
		if (!isset($this->mxKarma['session'])) {
			if (!isset($this->mxKarma['connectionInProgress']) || !$this->mxKarma['connectionInProgress']) {
				$this->mxKarmaOpenSession();
			}
			return;
		}

		$gameMode = $this->maniaControl->server->getGameMode(true);

		if (count($votes) == 0) {
			return;
		}

		$properties = array();
		if ($gameMode == 'Script') {
			$scriptName             = $this->maniaControl->client->getScriptName();
			$properties['gamemode'] = $scriptName["CurrentValue"];
		} else {
			$properties['gamemode'] = $gameMode;
		}

		if ($import) {
			$properties['maptime'] = 0;
		} else {
			$properties['maptime'] = time() - $this->mxKarma['startTime'];
		}

		$properties['votes']     = $votes;
		$properties['titleid']   = $this->maniaControl->server->titleId;
		$properties['mapname']   = $map->rawName;
		$properties['mapuid']    = $map->uid;
		$properties['mapauthor'] = $map->authorLogin;
		$properties['isimport']  = $import;

		$content = json_encode($properties);

		$self = $this;
		$this->maniaControl->fileReader->postData(self::MX_KARMA_URL . self::MX_KARMA_SAVEVOTES . "?sessionKey=" . urlencode($this->mxKarma['session']->sessionKey), function ($data, $error) use (&$self) {
			if (!$error) {
				$data = json_decode($data);
				if ($data->success) {
					$self->maniaControl->log("Votes successfully permitted");
				} else {
					$self->maniaControl->log("Error while updating votes: " . $data->data->message);
					// TODO remove temp trigger
					$self->maniaControl->errorHandler->triggerDebugNotice("Error while updating votes: " . $data->data->message . " " . KarmaPlugin::MX_KARMA_URL . $self::MX_KARMA_SAVEVOTES . "?sessionKey=" . urlencode($self->mxKarma['session']->sessionKey));
				}
			} else {
				$self->maniaControl->log($error);
			}
		}, $content, false, 'application/json');
	}

	/**
	 * Builds a sha512 activation Hash for the MX-Karma
	 *
	 * @param $sessionSeed
	 * @param $mxKey
	 * @return string
	 */
	private function buildActivationHash($sessionSeed, $mxKey) {
		return hash('sha512', $mxKey . $sessionSeed);
	}
}
