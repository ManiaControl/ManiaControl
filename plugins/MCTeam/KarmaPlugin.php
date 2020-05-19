<?php

namespace MCTeam;

use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Controls\Quad;
use FML\ManiaLink;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Callbacks\Callbacks;
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\Files\AsyncHttpRequest;
use ManiaControl\Logger;
use ManiaControl\ManiaControl;
use ManiaControl\Maps\Map;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;
use ManiaControl\Plugins\Plugin;
use ManiaControl\Plugins\PluginMenu;
use ManiaControl\Settings\Setting;
use ManiaControl\Settings\SettingManager;
use ManiaControl\Utils\ColorUtil;

/**
 * ManiaControl Karma Plugin
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class KarmaPlugin implements CallbackListener, TimerListener, Plugin {
	/*
	 * Constants
	 */
	const ID                           = 2;
	const VERSION                      = 0.2;
	const NAME                         = 'Karma Plugin';
	const AUTHOR                       = 'MCTeam';
	const MLID_KARMA                   = 'KarmaPlugin.MLID';
	const TABLE_KARMA                  = 'mc_karma';
	const CB_KARMA_CHANGED             = 'KarmaPlugin.Changed';
	const CB_KARMA_MXUPDATED           = 'KarmaPlugin.MXUpdated';
	const DEFAULT_LOCAL_RECORDS_PLUGIN = 'MCTeam\LocalRecordsPlugin';
	const SETTING_ALLOW_ON_LOCAL       = 'Player can only vote when having a local record';
	const SETTING_AVAILABLE_VOTES      = 'Available Votes (X-Y: Comma separated)';
	const SETTING_WIDGET_ENABLE        = 'Enable Karma Widget';
	const SETTING_WIDGET_TITLE         = 'Widget-Title';
	const SETTING_WIDGET_POSX          = 'Widget-Position: X';
	const SETTING_WIDGET_POSY          = 'Widget-Position: Y';
	const SETTING_WIDGET_WIDTH         = 'Widget-Size: Width';
	const SETTING_WIDGET_HEIGHT        = 'Widget-Size: Height';
	const SETTING_NEWKARMA             = 'Enable "new karma" (percentage), disable = RASP karma';
	const STAT_PLAYER_MAPVOTES         = 'Voted Maps';

	/*
	 * Constants MX Karma
	 */
	const SETTING_WIDGET_DISPLAY_MX  = 'Display MX-Karma in Widget';
	const SETTING_MX_KARMA_ACTIVATED = 'Activate MX-Karma';
	const SETTING_MX_KARMA_IMPORTING = 'Import old MX-Karma';
	const MX_IMPORT_TABLE            = 'mc_karma_mximport';
	const MX_KARMA_URL               = 'https://karma.mania-exchange.com/api2/';
	const MX_KARMA_START_SESSION     = 'startSession';
	const MX_KARMA_ACTIVATE_SESSION  = 'activateSession';
	const MX_KARMA_SAVE_VOTES        = 'saveVotes';
	const MX_KARMA_GET_MAP_RATING    = 'getMapRating';

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl    = null;
	private $updateManialink = false;
	// TODO: use some sort of model class instead of various array keys that you can't remember
	private $mxKarma = array();

	/**
	 * @see \ManiaControl\Plugins\Plugin::prepare()
	 */
	public static function prepare(ManiaControl $maniaControl) {
		$thisClass = get_class();
		$maniaControl->getSettingManager()->initSetting($thisClass, self::SETTING_MX_KARMA_ACTIVATED, true);
		$maniaControl->getSettingManager()->initSetting($thisClass, self::SETTING_MX_KARMA_IMPORTING, true);
		$maniaControl->getSettingManager()->initSetting($thisClass, self::SETTING_WIDGET_DISPLAY_MX, true);
		$servers = $maniaControl->getServer()->getAllServers();
		foreach ($servers as $server) {
			$settingName = self::buildKarmaSettingName($server->login);
			$maniaControl->getSettingManager()->initSetting($thisClass, $settingName, '');
		}
	}

	/**
	 * Build the Karma Setting Name for the given Server Login
	 *
	 * @param string $serverLogin
	 * @return string
	 */
	private static function buildKarmaSettingName($serverLogin) {
		return '$l[https://karma.mania-exchange.com/auth/getapikey?server=' . $serverLogin . ']MX Karma Code for ' . $serverLogin . '$l';
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
		return 'Plugin offering Karma Voting for Maps.';
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::load()
	 */
	public function load(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Init database
		$this->initTables();

		// Settings
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_ALLOW_ON_LOCAL, false);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_AVAILABLE_VOTES, '-2,2');
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_ENABLE, true);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_TITLE, 'Map-Karma');
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_POSX, 160 - 27.5);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_POSY, 90 - 9 - 5);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_WIDTH, 25.);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_HEIGHT, 10.);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_NEWKARMA, true);

		// Callbacks
		$this->maniaControl->getTimerManager()->registerTimerListening($this, 'handle1Second', 1000);
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::BEGINMAP, $this, 'handleBeginMap');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::BEGINMAP, $this, 'importMxKarmaVotes');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::ENDMAP, $this, 'sendMxKarmaVotes');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(PlayerManager::CB_PLAYERCONNECT, $this, 'handlePlayerConnect');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(CallbackManager::CB_MP_PLAYERCHAT, $this, 'handlePlayerChat');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(SettingManager::CB_SETTING_CHANGED, $this, 'updateSettings');

		// Define player stats
		$this->maniaControl->getStatisticManager()->defineStatMetaData(self::STAT_PLAYER_MAPVOTES);

		// Register Stat in Simple StatsList
		$this->maniaControl->getStatisticManager()->getSimpleStatsList()->registerStat(self::STAT_PLAYER_MAPVOTES, 100, "VM");

		$this->updateManialink = true;

		// Open MX-Karma Session
		$this->mxKarmaOpenSession();
		$this->mxKarma['startTime'] = time();

		//Check if Karma Code got specified, and inform admin that it would be good to specify one
		$serverLogin      = $this->maniaControl->getServer()->login;
		$karmaSettingName = self::buildKarmaSettingName($serverLogin);
		$mxKarmaCode      = $this->maniaControl->getSettingManager()->getSettingValue($this, $karmaSettingName);

		if (!$mxKarmaCode) {
			$permission = $this->maniaControl->getSettingManager()->getSettingValue($this->maniaControl->getAuthenticationManager(), PluginMenu::SETTING_PERMISSION_CHANGE_PLUGIN_SETTINGS);
			$this->maniaControl->getChat()->sendErrorToAdmins("Please specify a Mania-Exchange Karma Key in the Karma-Plugin settings!", $permission);
		}

		return true;
	}

	/**
	 * Create necessary database tables
	 */
	private function initTables() {
		$mysqli = $this->maniaControl->getDatabase()->getMysqli();

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
	 * Open a Mx Karma Session
	 */
	private function mxKarmaOpenSession() {
		if (!$this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MX_KARMA_ACTIVATED)) {
			return;
		}

		$serverLogin      = $this->maniaControl->getServer()->login;
		$karmaSettingName = self::buildKarmaSettingName($serverLogin);
		$mxKarmaCode      = $this->maniaControl->getSettingManager()->getSettingValue($this, $karmaSettingName);

		if (!$mxKarmaCode) {
			return;
		}

		$appIdentifier = 'ManiaControl v' . ManiaControl::VERSION;
		$testMode      = 'true';

		$query = self::MX_KARMA_URL . self::MX_KARMA_START_SESSION;
		$query .= '?serverLogin=' . $serverLogin;
		$query .= '&applicationIdentifier=' . urlencode($appIdentifier);
		$query .= '&testMode=' . $testMode;

		$this->mxKarma['connectionInProgress'] = true;

		$asyncHttpRequest = new AsyncHttpRequest($this->maniaControl, $query);
		$asyncHttpRequest->setContentType(AsyncHttpRequest::CONTENT_TYPE_JSON);
		$asyncHttpRequest->setCallable(function ($json, $error) use ($mxKarmaCode) {
			$this->mxKarma['connectionInProgress'] = false;
			if ($error) {
				$this->maniaControl->getErrorHandler()->triggerDebugNotice('mx karma error: ' . $error);
				return;
			}
			$data = json_decode($json);
			if (!$data) {
				$this->maniaControl->getErrorHandler()->triggerDebugNotice('auth error' . $json);
				return;
			}
			if ($data->success) {
				$this->mxKarma['session'] = $data->data;
				$this->activateSession($mxKarmaCode);
			} else {
				Logger::logError("Error while authenticating on Mania-Exchange Karma");

				if ($data->data->message == "invalid server") {
					Logger::log("You need to get a Karma Key from MX with registering your server");
				} else {
					// TODO remove temp trigger
					$this->maniaControl->getErrorHandler()->triggerDebugNotice('auth error ' . json_encode($data->data->message));
				}

				$this->mxKarma['connectionInProgress'] = false;
			}
		});

		$asyncHttpRequest->getData(1000);
	}

	/**
	 * Activates the MX-Karma Session
	 *
	 * @param string $mxKarmaCode
	 */
	private function activateSession($mxKarmaCode) {
		$hash = $this->buildActivationHash($this->mxKarma['session']->sessionSeed, $mxKarmaCode);

		$query = self::MX_KARMA_URL . self::MX_KARMA_ACTIVATE_SESSION;
		$query .= '?sessionKey=' . urlencode($this->mxKarma['session']->sessionKey);
		$query .= '&activationHash=' . urlencode($hash);

		$asyncHttpRequest = new AsyncHttpRequest($this->maniaControl, $query);
		$asyncHttpRequest->setContentType(AsyncHttpRequest::CONTENT_TYPE_JSON);
		$asyncHttpRequest->setCallable(function ($json, $error) use ($query) {
			$this->mxKarma['connectionInProgress'] = false;
			if ($error) {
				$this->maniaControl->getErrorHandler()->triggerDebugNotice('mx karma error' . $error);
				return;
			}
			$data = json_decode($json);
			if (!$data) {
				$this->maniaControl->getErrorHandler()->triggerDebugNotice('parse error' . $json);
				return;
			}
			if ($data->success && $data->data->activated) {
				Logger::log('Successfully authenticated on Mania-Exchange Karma');

				// Fetch the Mx Karma Votes
				$this->getMxKarmaVotes();
			} else {
				if ($data->data->message === 'invalid hash') {
					$permission = $this->maniaControl->getSettingManager()->getSettingValue($this->maniaControl->getAuthenticationManager(), PluginMenu::SETTING_PERMISSION_CHANGE_PLUGIN_SETTINGS);
					$this->maniaControl->getChat()->sendErrorToAdmins("Invalid Mania-Exchange Karma code in Karma Plugin specified!", $permission);
				} else {
					$this->maniaControl->getErrorHandler()->triggerDebugNotice('auth error' . $data->data->message . $query);
				}
				Logger::logError("Error while activating Mania-Exchange Karma Session: " . $data->data->message);
				unset($this->mxKarma['session']);
			}
		});

		$asyncHttpRequest->getData(1000);
	}

	/**
	 * Builds a sha512 activation Hash for the MX-Karma
	 *
	 * @param string $sessionSeed
	 * @param string $mxKey
	 * @return string
	 */
	private function buildActivationHash($sessionSeed, $mxKey) {
		return hash('sha512', $mxKey . $sessionSeed);
	}

	/**
	 * Fetch the mxKarmaVotes for the current map
	 */
	public function getMxKarmaVotes(Player $player = null) {
		if (!$this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MX_KARMA_ACTIVATED)) {
			return;
		}

		if (!isset($this->mxKarma['session'])) {
			if (!isset($this->mxKarma['connectionInProgress']) || !$this->mxKarma['connectionInProgress']) {
				$this->mxKarmaOpenSession();
			}
			return;
		}

		$map = $this->maniaControl->getMapManager()->getCurrentMap();

		$properties = array();

		$gameMode = $this->maniaControl->getServer()->getGameMode(true);
		if ($gameMode === 'Script') {
			$scriptName             = $this->maniaControl->getClient()->getScriptName();
			$properties['gamemode'] = $scriptName['CurrentValue'];
		} else {
			$properties['gamemode'] = $gameMode;
		}

		$properties['titleid'] = $this->maniaControl->getServer()->titleId;
		$properties['mapuid']  = $map->uid;

		if (!$player) {
			$properties['getvotesonly'] = false;
			$properties['playerlogins'] = array();
			foreach ($this->maniaControl->getPlayerManager()->getPlayers() as $loopPlayer) {
				$properties['playerlogins'][] = $loopPlayer->login;
			}
		} else {
			$properties['getvotesonly'] = true;
			$properties['playerlogins'] = array($player->login);
		}

		$content = json_encode($properties);

		$url = self::MX_KARMA_URL . self::MX_KARMA_GET_MAP_RATING . '?sessionKey=' . urlencode($this->mxKarma['session']->sessionKey);

		$asyncHttpRequest = new AsyncHttpRequest($this->maniaControl, $url);
		$asyncHttpRequest->setContent($content);
		$asyncHttpRequest->setContentType($asyncHttpRequest::CONTENT_TYPE_JSON);

		$asyncHttpRequest->setCallable(function ($json, $error) use (
			&$player
		) {
			if ($error) {
				$this->maniaControl->getErrorHandler()->triggerDebugNotice('mx karma error' . $error);
				return;
			}
			$data = json_decode($json);
			if (!$data) {
				$this->maniaControl->getErrorHandler()->triggerDebugNotice('parse error' . $json);
				return;
			}

			if ($data->success) {
				// Fetch averages if it's for the whole server
				if (!$player) {
					$this->mxKarma['voteCount']       = $data->data->votecount;
					$this->mxKarma['voteAverage']     = $data->data->voteaverage;
					$this->mxKarma['modeVoteCount']   = $data->data->modevotecount;
					$this->mxKarma['modeVoteAverage'] = $data->data->modevoteaverage;
				}

				foreach ($data->data->votes as $votes) {
					$this->mxKarma['votes'][$votes->login] = $votes->vote;
				}

				$this->updateManialink = true;
				$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_KARMA_MXUPDATED, $this->mxKarma);
				Logger::logInfo('MX-Karma Votes successfully fetched!');
			} else {
				// Problem occurred
				Logger::logError('Error while fetching votes: ' . $data->data->message);
				if ($data->data->message === 'invalid session') {
					unset($this->mxKarma['session']);
				} else {
					$this->maniaControl->getErrorHandler()->triggerDebugNotice('fetch error ' . json_encode($data->data->message . self::MX_KARMA_GET_MAP_RATING));
				}
			}
		});

		$asyncHttpRequest->postData();
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::unload()
	 */
	public function unload() {
		$this->maniaControl->getManialinkManager()->hideManialink(self::MLID_KARMA);
	}

	/**
	 * Handle Begin Map Callback
	 */
	public function handleBeginMap() {
		// send Map Karma to MX from previous Map
		if (isset($this->mxKarma['map'])) {
			$votes = array();
			foreach ($this->mxKarma['votes'] as $login => $value) {
				$player = $this->maniaControl->getPlayerManager()->getPlayer($login);
				array_push($votes, array('login' => $login, 'nickname' => $player->rawNickname, 'vote' => $value));
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

		$gameMode = $this->maniaControl->getServer()->getGameMode(true);

		if (empty($votes)) {
			return;
		}

		$properties = array();
		if ($gameMode === 'Script') {
			$scriptName             = $this->maniaControl->getClient()->getScriptName();
			$properties['gamemode'] = $scriptName['CurrentValue'];
		} else {
			$properties['gamemode'] = $gameMode;
		}

		if ($import) {
			$properties['maptime'] = 0;
		} else {
			$properties['maptime'] = time() - $this->mxKarma['startTime'];
		}

		$properties['votes']     = $votes;
		$properties['titleid']   = $this->maniaControl->getServer()->titleId;
		$properties['mapname']   = $map->rawName;
		$properties['mapuid']    = $map->uid;
		$properties['mapauthor'] = $map->authorLogin;
		$properties['isimport']  = $import;

		$content = json_encode($properties);

		$url = self::MX_KARMA_URL . self::MX_KARMA_SAVE_VOTES . "?sessionKey=" . urlencode($this->mxKarma['session']->sessionKey);

		$asyncRequest = new AsyncHttpRequest($this->maniaControl, $url);
		$asyncRequest->setContent($content);
		$asyncRequest->setContentType($asyncRequest::CONTENT_TYPE_JSON);
		$asyncRequest->setCallable(function ($json, $error) {
			if ($error) {
				$this->maniaControl->getErrorHandler()->triggerDebugNotice('mx karma error ' . $error);
				return;
			}
			$data = json_decode($json);
			if (!$data) {
				$this->maniaControl->getErrorHandler()->triggerDebugNotice('parse error ' . $json);
				return;
			}
			if ($data->success) {
				Logger::logInfo('Votes successfully submitted!');
			} else {
				// Problem occurred
				Logger::logError("Error while updating votes: '{$data->data->message}'");
				if ($data->data->message === "invalid session") {
					unset($this->mxKarma['session']);
				} else {
					$this->maniaControl->getErrorHandler()->triggerDebugNotice('saving error ' . json_encode($data->data->message . self::MX_KARMA_SAVE_VOTES));
				}
			}
		});

		$asyncRequest->postData();
	}

	/**
	 * Handle PlayerConnect callback
	 *
	 * @param Player $player
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
	 * Handle PlayerChat callback
	 *
	 * @param array $chatCallback
	 */
	public function handlePlayerChat(array $chatCallback) {
		$login  = $chatCallback[1][1];
		$player = $this->maniaControl->getPlayerManager()->getPlayer($login);
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
		// we have a vote-message
		if ($this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_ALLOW_ON_LOCAL)) {
			$localRecordPlugin = $this->maniaControl->getPluginManager()->getPlugin(self::DEFAULT_LOCAL_RECORDS_PLUGIN);
			if (!$localRecordPlugin) {
				return;
			}

			$currentMap = $this->maniaControl->getMapManager()->getCurrentMap();
			$localRecord = $localRecordPlugin->getLocalRecord($currentMap, $player);
			if ($localRecord === null) {
				$this->maniaControl->getChat()->sendError('You need to have a local record on this map before voting.', $player->login);
				return;
			}
		}

		$vote    = $countPositive - $countNegative;
		$success = $this->handleVote($player, $vote);
		if (!$success) {
			$this->maniaControl->getChat()->sendError('Error occurred.', $player->login);
			return;
		}
		$this->maniaControl->getChat()->sendSuccess('Vote updated!', $player->login);
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
		$votesSetting = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_AVAILABLE_VOTES);
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
		$vote     -= $voteLow;
		$voteHigh -= $voteLow;
		$vote     /= $voteHigh;

		// Save vote
		$map = $this->maniaControl->getMapManager()->getCurrentMap();

		// Update vote in MX karma array
		if ($this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MX_KARMA_ACTIVATED)
		    && isset($this->mxKarma['session'])
		) {
			if (!isset($this->mxKarma['votes'][$player->login])) {
				if (!isset($this->mxKarma['voteCount'])) {
					$this->mxKarma['voteCount'] = 0;
				}
				if (!isset($this->mxKarma['voteAverage'])) {
					$this->mxKarma['voteAverage'] = 0.;
				}
				$sum = $this->mxKarma['voteCount'] * $this->mxKarma['voteAverage'] + $vote * 100;
				$this->mxKarma['voteCount']++;

				if (!isset($this->mxKarma['modeVoteCount'])) {
					$this->mxKarma['modeVoteCount'] = 0;
				}
				if (!isset($this->mxKarma['modeVoteAverage'])) {
					$this->mxKarma['modeVoteAverage'] = 0;
				}
				$modeSum = $this->mxKarma['modeVoteCount'] * $this->mxKarma['modeVoteAverage'] + $vote * 100;
				$this->mxKarma['modeVoteCount']++;
			} else {
				$oldVote = $this->mxKarma['votes'][$player->login];
				$sum     = $this->mxKarma['voteCount'] * $this->mxKarma['voteAverage'] - $oldVote + $vote * 100;
				$modeSum = $this->mxKarma['modeVoteCount'] * $this->mxKarma['modeVoteAverage'] - $oldVote + $vote * 100;
			}
			//FIXME, how can here ever be division by zero?, a voting just happened before, and a vote of a player is set
			//edit problem is if someone votes on one server (on a map which has no votes yet, joins another server than where same map is running and votes again)
			$this->mxKarma['voteAverage']           = $sum / $this->mxKarma['voteCount'];
			$this->mxKarma['modeVoteAverage']       = $modeSum / $this->mxKarma['modeVoteCount'];
			$this->mxKarma['votes'][$player->login] = $vote * 100;
		}

		$voted = $this->getPlayerVote($player, $map);
		if (!$voted) {
			$this->maniaControl->getStatisticManager()->incrementStat(self::STAT_PLAYER_MAPVOTES, $player, $this->maniaControl->getServer()->index);
		}

		$success = $this->savePlayerVote($player, $map, $vote);
		if (!$success) {
			return false;
		}
		$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_KARMA_CHANGED);
		$this->updateManialink = true;
		return true;
	}

	/**
	 * Get the current vote of the player for the map
	 *
	 * @param Player $player
	 * @param Map    $map
	 * @return int
	 */
	public function getPlayerVote(Player $player, Map $map) {
		$mysqli = $this->maniaControl->getDatabase()->getMysqli();
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
	 * Save the vote of the player for the map
	 *
	 * @param Player $player
	 * @param Map    $map
	 * @param float  $vote
	 * @return bool
	 */
	private function savePlayerVote(Player $player, Map $map, $vote) {
		$mysqli = $this->maniaControl->getDatabase()->getMysqli();
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
	 * Get all players votes
	 *
	 * @param Map $map
	 * @return array|bool
	 */
	public function getMapPlayerVotes(Map $map) {
		$mysqli = $this->maniaControl->getDatabase()->getMysqli();
		$query  = "SELECT * FROM `" . self::TABLE_KARMA . "`
				WHERE `mapIndex` = {$map->index}
				AND `vote` >= 0;";
		$result = $mysqli->query($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}

		$votes = array();
		while ($vote = $result->fetch_object()) {
			$player    = $this->maniaControl->getPlayerManager()->getPlayerByIndex($vote->playerIndex);
			$karma     = $vote->vote;
			$voteArray = array('player' => $player, 'karma' => $karma);
			array_push($votes, $voteArray);
		}

		usort($votes, function ($paramA, $paramB) {
			return $paramA['karma'] - $paramB['karma'];
		});
		$votes = array_reverse($votes);

		return $votes;
	}

	/**
	 * Update Setting
	 *
	 * @param Setting $setting
	 */
	public function updateSettings(Setting $setting) {
		if (!$setting->belongsToClass($this)) {
			return;
		}

		$this->updateManialink = true;
		$serverLogin      = $this->maniaControl->getServer()->login;
		$karmaSettingName = self::buildKarmaSettingName($serverLogin);

		switch ($setting->setting) {
			case $karmaSettingName: {
				$this->mxKarmaOpenSession();
				break;
			}
			case self::SETTING_WIDGET_ENABLE: {
				if ($setting->value) {
					$this->handle1Second();
				} else {
					$this->maniaControl->getManialinkManager()->hideManialink(self::MLID_KARMA);
				}
				break;
			}
		}
	}

	/**
	 * Handle ManiaControl 1 Second Callback
	 */
	public function handle1Second() {
		if (!$this->updateManialink) {
			return;
		}

		$displayMxKarma = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_DISPLAY_MX);

		// Get map karma
		$map = $this->maniaControl->getMapManager()->getCurrentMap();

		// Display the mx Karma if the setting is chosen and the MX session is available
		if ($displayMxKarma && isset($this->mxKarma['session']) && isset($this->mxKarma['voteCount'])) {
			$map->mx->ratingVoteAverage = $this->mxKarma['modeVoteAverage'];
			$map->mx->ratingVoteCount   = $this->mxKarma['modeVoteCount'];
		}

		if ($this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_ENABLE)) {
			// Build karma manialink
			$this->buildManialink($map, $this->updateManialink);
		}

		$this->updateManialink = false;
	}

	/**
	 * Get the current karma of the map
	 *
	 * @param Map $map
	 * @return float | bool
	 */
	public function getMapKarma(Map $map) {
		$mysqli = $this->maniaControl->getDatabase()->getMysqli();
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
	 * @return array|bool
	 */
	public function getMapVotes(Map $map) {
		$mysqli = $this->maniaControl->getDatabase()->getMysqli();
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
		while ($vote = $result->fetch_object()) {
			$votes[$vote->vote] = $vote;
			$count              += $vote->count;
		}
		$votes['count'] = $count;
		$result->free();
		return $votes;
	}

	/**
	 * Build Karma Voting Manialink if necessary
	 *
	 * @param Map $map
	 * @param bool $forceBuild
	 */
	private function buildManialink(Map $map = null, $forceBuild = false) {
		if (!$forceBuild) {
			return;
		}

		$title        = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_TITLE);
		$posX         = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_POSX);
		$posY         = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_POSY);
		$width        = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_WIDTH);
		$height       = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_HEIGHT);
		$labelStyle   = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultLabelStyle();
		$quadStyle    = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultQuadStyle();
		$quadSubstyle = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultQuadSubstyle();

		$manialink = new ManiaLink(self::MLID_KARMA);

		$frame = new Frame();
		$manialink->addChild($frame);
		$frame->setPosition($posX, $posY);

		$backgroundQuad = new Quad();
		$frame->addChild($backgroundQuad);
		$backgroundQuad->setSize($width, $height);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);

		$titleLabel = new Label();
		$frame->addChild($titleLabel);
		$titleLabel->setScale(0.9);
		$titleLabel->setStyle($labelStyle);
		$titleLabel->setText($title);
		$titleLabel->setTextSize(1);
		$titleLabel->setTranslate(true);
		$titleLabel->setWidth(0.85*$width);
		$titleLabel->setY(0.2*$height);

		if ($map === null) {
			$map = $this->maniaControl->getMapManager()->getCurrentMap();
		}
		
		$karmaGauge = $this->maniaControl->getManialinkManager()->getElementBuilder()->buildKarmaGauge(
			$map,
			0.95*$width,
			0.95*$height
		);
		if ($karmaGauge) {
			$frame->addChild($karmaGauge);
			$karmaGauge->setY(-0.15*$height);
		}

		$this->maniaControl->getManialinkManager()->sendManialink($manialink);
	}

	/**
	 * Import old Karma votes to Mania-Exchange Karma
	 *
	 * @param Map $map
	 */
	public function importMxKarmaVotes(Map $map) {
		if (!$this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MX_KARMA_ACTIVATED)) {
			return;
		}

		if (!$this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MX_KARMA_IMPORTING)) {
			return;
		}

		if (!isset($this->mxKarma['session'])) {
			if (!isset($this->mxKarma['connectionInProgress']) || !$this->mxKarma['connectionInProgress']) {
				$this->mxKarmaOpenSession();
			}
			return;
		}

		$mysqli = $this->maniaControl->getDatabase()->getMysqli();
		$query  = "SELECT `mapImported` FROM `" . self::MX_IMPORT_TABLE . "`
				WHERE `mapIndex` = {$map->index};";
		$result = $mysqli->query($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return;
		}
		$vote = $result->fetch_object();

		if (!$result->field_count || !$vote) {
			$query   = "SELECT `vote`, `login`, `nickname`
					FROM `" . self::TABLE_KARMA . "` k
					LEFT JOIN `" . PlayerManager::TABLE_PLAYERS . "` p
					ON k.playerIndex = p.index
					WHERE `mapIndex` = {$map->index};";
			$result2 = $mysqli->query($query);
			if ($mysqli->error) {
				trigger_error($mysqli->error);
				return;
			}

			$votes = array();
			while ($row = $result2->fetch_object()) {
				array_push($votes, array('login' => $row->login, 'nickname' => $row->nickname, 'vote' => $row->vote * 100));
			}

			$this->postKarmaVotes($map, $votes, true);

			// Flag Map as Imported in database if it is a import
			$query = "INSERT INTO `" . self::MX_IMPORT_TABLE . "` (
					`mapIndex`,
					`mapImported`
					) VALUES (
					{$map->index},
					1
					) ON DUPLICATE KEY UPDATE
					`mapImported` = 1;";
			$mysqli->query($query);
			if ($mysqli->error) {
				trigger_error($mysqli->error);
			}

			$result2->free();
		}
		$result->free();

		return;
	}

	/**
	 * Save Mx Karma Votes at MapEnd
	 */
	public function sendMxKarmaVotes(Map $map) {
		if (!$this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MX_KARMA_ACTIVATED)) {
			return;
		}

		if (!isset($this->mxKarma['session'])) {
			if (!isset($this->mxKarma['connectionInProgress']) || !$this->mxKarma['connectionInProgress']) {
				$this->mxKarmaOpenSession();
			}
			return;
		}

		if (!isset($this->mxKarma['votes']) || empty($this->mxKarma['votes'])) {
			return;
		}

		$this->mxKarma['map'] = $map;
	}
}
