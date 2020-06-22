<?php

namespace MCTeam;

use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_BgsPlayerCard;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\ManiaLink;
use FML\Script\Features\Paging;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\Callbacks;
use ManiaControl\Callbacks\CallQueueListener;
use ManiaControl\Callbacks\Structures\TrackMania\OnWayPointEventStructure;
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\Commands\CommandListener;
use ManiaControl\Logger;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\LabelLine;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Maps\Map;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;
use ManiaControl\Plugins\Plugin;
use ManiaControl\Settings\Setting;
use ManiaControl\Settings\SettingManager;
use ManiaControl\Utils\DataUtil;
use ManiaControl\Utils\Formatter;
use MCTeam\Common\RecordWidget;

/**
 * ManiaControl Local Records Plugin
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class LocalRecordsPlugin implements CallbackListener, CallQueueListener, CommandListener, ManialinkPageAnswerListener, TimerListener, Plugin {
	/*
	 * Constants
	 */
	const ID                                  = 7;
	const VERSION                             = 0.82;
	const NAME                                = 'Local Records Plugin';
	const AUTHOR                              = 'MCTeam';
	const MLID_RECORDS                        = 'ml_local_records';
	const TABLE_RECORDS                       = 'mc_localrecords';
	const PERMISSION_DELETE_ANY_RECORD        = 'Permission remove any record';
	const PERMISSION_DELETE_PERSONAL_RECORD   = 'Permission remove personal record';
	const SETTING_MULTILAP_SAVE_SINGLE        = 'Save every Lap as Record in Multilap';
	const SETTING_WIDGET_TITLE                = 'Widget Title';
	const SETTING_WIDGET_POSX                 = 'Widget Position: X';
	const SETTING_WIDGET_POSY                 = 'Widget Position: Y';
	const SETTING_WIDGET_WIDTH                = 'Widget Width';
	const SETTING_WIDGET_LINESCOUNT           = 'Widget Displayed Lines Count';
	const SETTING_WIDGET_LINEHEIGHT           = 'Widget Line Height';
	const SETTING_WIDGET_ENABLE               = 'Enable Local Records Widget';
	const SETTING_NOTIFICATION_MESSAGE_PREFIX = 'Notification Message Prefix';
	const SETTING_NOTIFY_BEST_RECORDS_PRIVATE = 'Notify privately for the X Best Records';
	const SETTING_NOTIFY_BEST_RECORDS_PUBLIC  = 'Notify publicly for the X Best Records';
	const SETTING_ADJUST_OUTER_BORDER         = 'Adjust outer Border to Number of actual Records';
	const SETTING_RECORDS_BEFORE_AFTER        = 'Number of Records displayed before and after a player';
	const CB_LOCALRECORDS_CHANGED             = 'LocalRecords.Changed';
	const ACTION_SHOW_RECORDSLIST             = 'LocalRecords.ShowRecordsList';
	const CSV_SPLITTER                        = ';';


	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;
	/** @var \MCTeam\Common\RecordWidget $recordWidget */
	private $recordWidget    = null;
	private $updateManialink = false;
	private $checkpoints     = array();
	private $scriptName      = 'TimeAttack';

	/**
	 * @see \ManiaControl\Plugins\Plugin::prepare()
	 */
	public static function prepare(ManiaControl $maniaControl) {
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
		return 'Plugin offering tracking of local records and manialinks to display them.';
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::load()
	 * @param \ManiaControl\ManiaControl $maniaControl
	 * @return bool
	 */
	public function load(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->initTables();

		$this->recordWidget = new RecordWidget($this->maniaControl);

		// Settings
		$this->maniaControl->getAuthenticationManager()->definePluginPermissionLevel(
			$this,
			self::PERMISSION_DELETE_ANY_RECORD,
			AuthenticationManager::AUTH_LEVEL_SUPERADMIN,
			AuthenticationManager::AUTH_LEVEL_MODERATOR
		);
		$this->maniaControl->getAuthenticationManager()->definePluginPermissionLevel(
			$this,
			self::PERMISSION_DELETE_PERSONAL_RECORD,
			AuthenticationManager::AUTH_LEVEL_ADMIN,
			AuthenticationManager::AUTH_LEVEL_PLAYER
		);

		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_TITLE, 'Local Records');
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_POSX, -139.);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_POSY, 75);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_WIDTH, 40.);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_LINESCOUNT, 15);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_LINEHEIGHT, 4.);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_ENABLE, true);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_NOTIFICATION_MESSAGE_PREFIX, '$3c0');
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_NOTIFY_BEST_RECORDS_PRIVATE, 100);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_NOTIFY_BEST_RECORDS_PUBLIC, 10);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_ADJUST_OUTER_BORDER, false);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_RECORDS_BEFORE_AFTER, 2);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_MULTILAP_SAVE_SINGLE, false);

		// Callbacks
		$this->maniaControl->getTimerManager()->registerTimerListening($this, 'handle1Second', 1000);
		$this->maniaControl->getTimerManager()->registerTimerListening($this, 'handle1Minute', 60000);

		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::AFTERINIT, $this, 'handleAfterInit');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::BEGINMAP, $this, 'handleMapBegin');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(SettingManager::CB_SETTING_CHANGED, $this, 'handleSettingChanged');

		$this->maniaControl->getCallbackManager()->registerCallbackListener(PlayerManager::CB_PLAYERCONNECT, $this, 'handlePlayerConnect');

		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::TM_ONWAYPOINT, $this, 'handleCheckpointCallback');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::TM_ONFINISHLINE, $this, 'handleFinishCallback');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::TM_ONLAPFINISH, $this, 'handleFinishLapCallback');

		$this->maniaControl->getCommandManager()->registerCommandListener(array('exportrecs', 'exportrecords', 'recexport'), $this, 'exportRecordsList', true, 'Exports the records of a map to a CSV-file.');
		$this->maniaControl->getCommandManager()->registerCommandListener(array('recs', 'records'), $this, 'showRecordsList', false, 'Shows a list of Local Records on the current map.');
		$this->maniaControl->getCommandManager()->registerCommandListener('delrec', $this, 'deletePersonalRecord', false, 'Removes your record from the database.');
		$this->maniaControl->getCommandManager()->registerCommandListener('delrec', $this, 'deleteAnyRecord', true, 'Removes any record from the database.');

		$this->maniaControl->getManialinkManager()->registerManialinkPageAnswerListener(self::ACTION_SHOW_RECORDSLIST, $this, 'handleShowRecordsList');

		$this->updateManialink = true;

		return true;
	}

	/**
	 * Initialize needed database tables
	 */
	private function initTables() {
		$mysqli = $this->maniaControl->getDatabase()->getMysqli();
		$query  = "CREATE TABLE IF NOT EXISTS `" . self::TABLE_RECORDS . "` (
				`index` int(11) NOT NULL AUTO_INCREMENT,
				`mapIndex` int(11) NOT NULL,
				`playerIndex` int(11) NOT NULL,
				`time` int(11) NOT NULL,
				`changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (`index`),
				UNIQUE KEY `player_map_record` (`mapIndex`,`playerIndex`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;";
		$mysqli->query($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error, E_USER_ERROR);
		}

		$mysqli->query("ALTER TABLE `" . self::TABLE_RECORDS . "` ADD `checkpoints` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL");
		if ($mysqli->error) {
			if ($mysqli->errno !== 1060) {
				// Duplicate
				trigger_error($mysqli->error, E_USER_ERROR);
			}
		}
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::unload()
	 */
	public function unload() {
		$this->maniaControl->getManialinkManager()->hideManialink(self::MLID_RECORDS);
	}

	/**
	 * Handle ManiaControl After Init
	 *
	 * @internal
	 */
	public function handleAfterInit() {
		$this->updateManialink = true;
	}

	/**
	 * Handle 1 Second Callback
	 *
	 * @internal
	 */
	public function handle1Second() {
		if (!$this->updateManialink) {
			return;
		}

		$this->updateManialink = false;
		if ($this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_ENABLE)) {
			$this->sendWidgetManiaLink();
		}
	}

	/** Fetch the Current Scriptname every Minute
	 *
	 * @internal
	 */
	public function handle1Minute() {
		$scriptNameResponse = $this->maniaControl->getClient()->getScriptName();
		$this->scriptName   = str_replace('.Script.txt', '', $scriptNameResponse['CurrentValue']);
	}

	/**
	 * Build the local records widget ManiaLink and send it to the players
	 *
	 * @return string
	 */
	private function sendWidgetManiaLink() {
		$map = $this->maniaControl->getMapManager()->getCurrentMap();
		if (!$map) {
			return null;
		}

		$title              = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_TITLE);
		$posX               = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_POSX);
		$posY               = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_POSY);
		$width              = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_WIDTH);
		$lines              = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_LINESCOUNT);
		$lineHeight         = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_LINEHEIGHT);
		$recordsBeforeAfter = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_RECORDS_BEFORE_AFTER);
		$labelStyle         = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultLabelStyle();
		$quadStyle          = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultQuadStyle();
		$quadSubstyle       = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultQuadSubstyle();

		$records = $this->getLocalRecords($map, 1000); //TODO limit setting
		if (!is_array($records)) {
			Logger::logError("Couldn't fetch player records.");
			return null;
		}

		//TODO maybe only store if player is connected
		$playerRecords = array();
		foreach ($records as $index => $record) {
			$playerRecords[$record->playerIndex] = $index;
		}

		$frame = new Frame();
		$frame->setPosition($posX, $posY);

		$backgroundQuad = new Quad();
		$frame->addChild($backgroundQuad);
		$backgroundQuad->setVerticalAlign($backgroundQuad::TOP);
		$adjustOuterBorder = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_ADJUST_OUTER_BORDER);
		$height            = 7. + ($adjustOuterBorder ? count($records) : $lines) * $lineHeight;
		$backgroundQuad->setSize($width * 1.05, $height);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);
		$backgroundQuad->setAction(self::ACTION_SHOW_RECORDSLIST);

		$titleLabel = new Label();
		$frame->addChild($titleLabel);
		$titleLabel->setPosition(0, $lineHeight * -0.9);
		$titleLabel->setWidth($width);
		$titleLabel->setStyle($labelStyle);
		$titleLabel->setTextSize(2);
		$titleLabel->setText($title);
		$titleLabel->setTranslate(true);

		$topRecordsCount                    = $lines - $recordsBeforeAfter * 2 - 1;
		$preGeneratedTopRecordsFrame        = $this->recordWidget->generateRecordsFrame($records, $topRecordsCount);
		$preGeneratedRecordsFrameWithRecord = $this->recordWidget->generateRecordsFrame($records, $lines - 1);

		$players              = $this->maniaControl->getPlayerManager()->getPlayers();
		$playersWithoutRecord = array();

		foreach ($players as $player) {
			$sendManiaLink = true;

			$maniaLink = new ManiaLink(self::MLID_RECORDS);
			$maniaLink->addChild($frame);

			$listFrame = new Frame();
			$maniaLink->addChild($listFrame);
			$listFrame->setPosition($posX, $posY);

			if (isset($playerRecords[$player->index]) && $playerRecords[$player->index] >= $topRecordsCount) {
				$listFrame->addChild($preGeneratedTopRecordsFrame);

				$y           = -8 - $topRecordsCount * $lineHeight;
				$playerIndex = $playerRecords[$player->index];

				//Line separator
				$quad = $this->recordWidget->getLineSeparatorQuad($width);
				$listFrame->addChild($quad);
				$quad->setY($y + $lineHeight / 2);

				//Generate the Records around a player and display below topRecords
				for ($i = $playerIndex - $recordsBeforeAfter; $i <= $playerIndex + $recordsBeforeAfter; $i++) {
					if (array_key_exists($i, $records)) { //If there are no records behind you
						$recordFrame = $this->recordWidget->generateRecordLineFrame($records[$i], $player);
						$recordFrame->setY($y);
						$listFrame->addChild($recordFrame);
						$y -= $lineHeight;
					}
				}

			} else if (isset($playerRecords[$player->index]) && $playerRecords[$player->index] < $topRecordsCount) {
				$playersWithoutRecord[] = $player;
				$sendManiaLink          = false;
			} else {
				if ($record = $this->getLocalRecord($map, $player)) {
					$record->nickname = $player->nickname;
					$recordFrame      = $preGeneratedRecordsFrameWithRecord;
					$listFrame->addChild($recordFrame);

					$y = -8 - ($lines - 1) * $lineHeight;

					//Line separator
					$quad = $this->recordWidget->getLineSeparatorQuad($width);
					$listFrame->addChild($quad);
					$quad->setY($y + $lineHeight / 2);

					$recordFrame = $this->recordWidget->generateRecordLineFrame($record, $player);
					$listFrame->addChild($recordFrame);
					$recordFrame->setY($y);

				} else {
					$playersWithoutRecord[] = $player;
					$sendManiaLink          = false;
				}
			}

			if ($sendManiaLink) {
				$this->maniaControl->getManialinkManager()->sendManialink($maniaLink, $player);
			}
		}

		if ($playersWithoutRecord) {
			$maniaLink = new ManiaLink(self::MLID_RECORDS);
			$maniaLink->addChild($frame);

			$listFrame = $this->recordWidget->generateRecordsFrame($records, $lines);
			$maniaLink->addChild($listFrame);
			$listFrame->setPosition($posX, $posY);

			$this->maniaControl->getManialinkManager()->sendManialink($maniaLink, $playersWithoutRecord);
		}
	}

	/**
	 * Fetch local records for the given map
	 *
	 * @api
	 * @param \ManiaControl\Maps\Map $map
	 * @param int                    $limit
	 * @return array|null
	 */
	public function getLocalRecords(Map $map, $limit = -1) {
		$mysqli = $this->maniaControl->getDatabase()->getMysqli();
		$limit  = ($limit > 0 ? 'LIMIT ' . $limit : '');
		$query  = "SELECT * FROM (
					SELECT recs.*, @rank := @rank + 1 as `rank` FROM `" . self::TABLE_RECORDS . "` recs, (SELECT @rank := 0) ra
					WHERE recs.`mapIndex` = {$map->index}
					ORDER BY recs.`time` ASC
					{$limit}) records
				LEFT JOIN `" . PlayerManager::TABLE_PLAYERS . "` players
				ON records.`playerIndex` = players.`index`;";
		$result = $mysqli->query($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return null;
		}
		$records = array();
		while ($record = $result->fetch_object()) {
			array_push($records, $record);
		}
		$result->free();
		return $records;
	}

	/**
	 * Handle Setting Changed Callback
	 *
	 * @internal
	 * @param Setting $setting
	 */
	public function handleSettingChanged(Setting $setting) {
		if (!$setting->belongsToClass($this)) {
			return;
		}

		switch ($setting->setting) {
			case self::SETTING_WIDGET_ENABLE: {
				if ($setting->value) {
					$this->updateManialink = true;
				} else {
					$this->maniaControl->getManialinkManager()->hideManialink(self::MLID_RECORDS);
				}
				break;
			}
			default:
				$this->updateRecordWidget();
				break;
		}
	}

	/**
	 * Handle Checkpoint Callback
	 *
	 * @internal
	 * @param \ManiaControl\Callbacks\Structures\TrackMania\OnWayPointEventStructure $structure
	 */
	public function handleCheckpointCallback(OnWayPointEventStructure $structure) {
		$playerLogin = $structure->getLogin();
		if (!isset($this->checkpoints[$playerLogin])) {
			$this->checkpoints[$playerLogin] = array();
		}
		$this->checkpoints[$playerLogin][$structure->getCheckPointInLap()] = $structure->getLapTime();
	}


	/**
	 * Handle End of Lap Callback
	 *
	 * @internal
	 * @param \ManiaControl\Callbacks\Structures\TrackMania\OnWayPointEventStructure $structure
	 */
	public function handleFinishLapCallback(OnWayPointEventStructure $structure) {

		$multiLapSaveSingle = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MULTILAP_SAVE_SINGLE);

		if ($this->scriptName != "TimeAttack" && !$multiLapSaveSingle) {
			//Do Nothing on Finishing a Single Lap
		} else {
			//Save on every pass through of a Lap
			$this->saveRecord($structure, $structure->getLapTime());
		}
	}

	/**
	 * Handle Finish Callback
	 *
	 * @internal
	 * @param \ManiaControl\Callbacks\Structures\TrackMania\OnWayPointEventStructure $structure
	 */
	public function handleFinishCallback(OnWayPointEventStructure $structure) {
		$multiLapSaveSingle = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MULTILAP_SAVE_SINGLE);

		if ($this->scriptName != "TimeAttack" && $multiLapSaveSingle) {
			//Save last lap time only
			$this->saveRecord($structure, $structure->getLapTime());
		} else {
			//Save full race time
			$this->saveRecord($structure, $structure->getRaceTime());
		}
	}


	private function saveRecord(OnWayPointEventStructure $structure, $time) {
		if ($time <= 0) {
			// Invalid time
			return;
		}

		$map = $this->maniaControl->getMapManager()->getCurrentMap();

		$player = $structure->getPlayer();

		if (!$player) { //TODO verify why this can happen
			return;
		}

		$checkpointsString                 = $this->getCheckpoints($player->login);
		$this->checkpoints[$player->login] = array();

		$messagePrefix     = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_NOTIFICATION_MESSAGE_PREFIX);
		$notifyPrivatelyAt = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_NOTIFY_BEST_RECORDS_PRIVATE);
		$notifyPubliclyAt  = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_NOTIFY_BEST_RECORDS_PUBLIC);

		// Check old record of the player
		$oldRecord = $this->getLocalRecord($map, $player);
		if ($oldRecord) {
			if ($oldRecord->time < $time) {
				// Not improved
				return;
			} else if ($oldRecord->time == $time) {
				// Same time
				$isPM = ($notifyPubliclyAt < $oldRecord->rank && $oldRecord->rank <= $notifyPrivatelyAt);
				$message = $this->maniaControl->getChat()->formatMessage(
					$messagePrefix . '%s equalized the %s Local Record: %s!',
					($isPM ? 'You' : $player),
					'$ff0' . $oldRecord->rank . '.',
					Formatter::formatTime($oldRecord->time)
				);

				if ($oldRecord->rank <= $notifyPubliclyAt) {
					$this->maniaControl->getCallQueueManager()->registerListening(
						$this,
						function () use ($message) {
							$this->maniaControl->getChat()->sendInformation($message);
						}
					);
				} else if ($oldRecord->rank <= $notifyPrivatelyAt) {
					$this->maniaControl->getCallQueueManager()->registerListening(
						$this,
						function () use ($message, $player) {
							$this->maniaControl->getChat()->sendInformation($message, $player);
						}
					);
				}
				return;
			}
		}

		// Save time
		$mysqli = $this->maniaControl->getDatabase()->getMysqli();
		$query  = "INSERT INTO `" . self::TABLE_RECORDS . "` (
				`mapIndex`,
				`playerIndex`,
				`time`,
				`checkpoints`
				) VALUES (
				{$map->index},
				{$player->index},
				{$time},
				'{$checkpointsString}'
				) ON DUPLICATE KEY UPDATE
				`time` = VALUES(`time`),
				`checkpoints` = VALUES(`checkpoints`);";
		$mysqli->query($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return;
		}
		$this->updateManialink = true;

		// Announce record
		$newRecord    = $this->getLocalRecord($map, $player);
		$improvedRank = ($oldRecord && $newRecord->rank >= $oldRecord->rank);

		$isPM = ($notifyPubliclyAt < $newRecord->rank && $newRecord->rank <= $notifyPrivatelyAt);
		$message = $this->maniaControl->getChat()->formatMessage(
			$messagePrefix . '%s ' . ($improvedRank ? 'improved' : 'gained') . ' the %s Local Record: %s!',
			($isPM ? 'You' : $player),
			'$ff0' . $newRecord->rank . '.',
			Formatter::formatTime($newRecord->time)
		);
		if ($oldRecord) {
			$timeDiff = $oldRecord->time - $newRecord->time;
			$message .= $this->maniaControl->getChat()->formatMessage(
				' (%s%s)',
				($improvedRank ? '$ff0' . $oldRecord->rank . '. ' : ''),
				'-' . Formatter::formatTime($timeDiff)
			);
		}

		if ($newRecord->rank <= $notifyPubliclyAt) {
			$this->maniaControl->getCallQueueManager()->registerListening(
				$this,
				function () use ($message) {
					$this->maniaControl->getChat()->sendInformation($message);
				}
			);
		} else if ($newRecord->rank <= $notifyPrivatelyAt) {
			$this->maniaControl->getCallQueueManager()->registerListening(
				$this,
				function () use ($message, $player) {
					$this->maniaControl->getChat()->sendInformation($message, $player);
				}
			);
		}

		$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_LOCALRECORDS_CHANGED, $newRecord);
	}


	/**
	 * Get current checkpoint string for local record
	 *
	 * @api
	 * @param string $login
	 * @return string
	 */
	public function getCheckpoints($login) {
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
	 * Retrieve the local record for the given map and login
	 *
	 * @api
	 * @param Map    $map
	 * @param Player $player
	 * @return mixed
	 */
	public function getLocalRecord(Map $map, Player $player) {
		$mysqli = $this->maniaControl->getDatabase()->getMysqli();
		$query  = "SELECT records.* FROM (
					SELECT recs.*, @rank := @rank + 1 as `rank` FROM `" . self::TABLE_RECORDS . "` recs, (SELECT @rank := 0) ra
					WHERE recs.`mapIndex` = {$map->index}
					ORDER BY recs.`time` ASC) records
				WHERE records.`playerIndex` = {$player->index};";
		$result = $mysqli->query($query);
		if ($mysqli->error) {
			trigger_error("Couldn't retrieve player record for '{$player->login}'." . $mysqli->error);
			return null;
		}
		$record = $result->fetch_object();
		$result->free();
		return $record;
	}

	/**
	 * Handle Player Connect Callback
	 *
	 * @internal
	 */
	public function handlePlayerConnect() {
		$this->updateManialink = true;
	}

	/**
	 * Handle Begin Map Callback
	 *
	 * @internal
	 */
	public function handleMapBegin() {
		$this->updateManialink = true;
	}


	/**
	 * Handle the ManiaLink answer of the showRecordsList action
	 *
	 * @internal
	 * @param array                        $callback
	 * @param \ManiaControl\Players\Player $player
	 */
	public function handleShowRecordsList(array $callback, Player $player) {
		$this->showRecordsList(array(), $player);
	}

	/**
	 * Exports the Records of a map to a CSV-file
	 *
	 * @api
	 * @param array  $chat
	 * @param Player $player
	 */
	public function exportRecordsList(array $chat, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPluginPermission($this, $player, self::PERMISSION_DELETE_ANY_RECORD)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}

		$commandParts = explode(' ', $chat[1][2]);
		if (count($commandParts) < 2 || strlen($commandParts[1]) == 0) {
			$message = $this->maniaControl->getChat()->formatMessage(
				'Missing CSV-Filename! (Example %s)',
				'//exportrecs locals.csv'
			);
			$this->maniaControl->getChat()->sendUsageInfo($message, $player);
			return;
		}

		$filename = $commandParts[1];
		if (!DataUtil::endsWith($filename, '.csv')) {
			$filename .= '.csv';
		}

		$map = null;
		if (count($commandParts) >= 3) {
			$mapId = (int) $commandParts[2];
			if ($mapId <= 0) {
				$this->maniaControl->getChat()->sendUsageInfo('Map-Id below 1!', $player);
				return;
			}

			$mapIndex = $mapId-1;
			$map = $this->maniaControl->getMapManager()->getMapByIndex($mapIndex);
			if (!$map) {
				$this->maniaControl->getChat()->sendUsageInfo('Map-Id too high!', $player);
				return;
			}
		} else {
			$map = $this->maniaControl->getMapManager()->getCurrentMap();
		}

		$records = $this->getLocalRecords($map);
		$lines = array();
		$header = array('rank', 'login', 'time', 'checkpoints');
		array_push($lines, implode(self::CSV_SPLITTER, $header));

		foreach ($records as $record) {
			$line = array(
				$record->rank,
				$record->login,
				$record->time,
				$record->checkpoints,
			);
			array_push($lines, implode(self::CSV_SPLITTER, $line));
		}

		try {
			file_put_contents($filename, implode(PHP_EOL, $lines));
		} catch (\Exception $e) {
			$this->maniaControl->getChat()->sendException($e, $player);
		}

		$message = $this->maniaControl->getChat()->formatMessage(
			'Exported Local Records of %s to %s!',
			$map,
			$filename
		);
		$this->maniaControl->getChat()->sendSuccess($message, $player);
	}

	/**
	 * Shows a ManiaLink list with the local records.
	 *
	 * @api
	 * @param array  $chat
	 * @param Player $player
	 */
	public function showRecordsList(array $chat, Player $player) {
		$width  = $this->maniaControl->getManialinkManager()->getStyleManager()->getListWidgetsWidth();
		$height = $this->maniaControl->getManialinkManager()->getStyleManager()->getListWidgetsHeight();

		// get PlayerList
		$records = $this->getLocalRecords($this->maniaControl->getMapManager()->getCurrentMap(), 200);

		// create manialink
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

		$labelLine = new LabelLine($headFrame);
		$labelLine->addLabelEntryText('Rank', $posX + 5);
		$labelLine->addLabelEntryText('Nickname', $posX + 18);
		$labelLine->addLabelEntryText('Login', $posX + 70);
		$labelLine->addLabelEntryText('Time', $posX + 101);
		$labelLine->render();

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

			if ($index % 2 === 0) {
				$lineQuad = new Quad_BgsPlayerCard();
				$recordFrame->addChild($lineQuad);
				$lineQuad->setSize($width, 4);
				$lineQuad->setSubStyle($lineQuad::SUBSTYLE_BgPlayerCardBig);
				$lineQuad->setZ(-0.001);
			}

			if ($listRecord->login === $player->login) {
				$currentQuad = new Quad_Icons64x64_1();
				$recordFrame->addChild($currentQuad);
				$currentQuad->setX($posX + 3.5);
				$currentQuad->setSize(4, 4);
				$currentQuad->setSubStyle($currentQuad::SUBSTYLE_ArrowBlue);
			}

			if (strlen($listRecord->nickname) < 2) {
				$listRecord->nickname = $listRecord->login;
			}

			$labelLine = new LabelLine($recordFrame);
			$labelLine->addLabelEntryText($listRecord->rank, $posX + 5, 13);
			$labelLine->addLabelEntryText('$fff' . $listRecord->nickname, $posX + 18, 52);
			$labelLine->addLabelEntryText($listRecord->login, $posX + 70, 31);
			$labelLine->addLabelEntryText(Formatter::formatTime($listRecord->time), $posX + 101, $width / 2 - ($posX + 110));
			$labelLine->render();

			$recordFrame->setY($posY);

			$posY -= 4;
			$index++;
		}

		// Render and display xml
		$this->maniaControl->getManialinkManager()->displayWidget($maniaLink, $player, 'LocalRecords');
	}

	/**
	 * Delete any record
	 *
	 * @internal
	 * @param array  $chat
	 * @param Player $player
	 */
	public function deleteAnyRecord(array $chat, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPluginPermission($this, $player, self::PERMISSION_DELETE_ANY_RECORD)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}

		$commandParts = explode(' ', $chat[1][2]);
		if (count($commandParts) < 2 || strlen($commandParts[1]) == 0) {
			$message = $this->maniaControl->getChat()->formatMessage(
				'Missing Record ID! (Example: %s)',
				'//delrec 3'
			);
			$this->maniaControl->getChat()->sendUsageInfo($message, $player);
			return;
		}

		$recordRank   = (int) $commandParts[1];
		$currentMap = $this->maniaControl->getMapManager()->getCurrentMap();
		$records    = $this->getLocalRecords($currentMap);
		if ($recordRank <= 0 || count($records) < $recordRank) {
			$message = $this->maniaControl->getChat()->formatMessage(
				'Cannot remove record no. %s, does not exist!',
				$recordRank
			);
			$this->maniaControl->getChat()->sendError($message, $player);
			return;
		}

		assert($recordRank == $records[$recordRank-1]->rank);
		$playerIndex = $records[$recordRank-1]->playerIndex;
		$recordPlayer = $this->maniaControl->getPlayerManager()->getPlayerByIndex($playerIndex);
		if (!$recordPlayer) {
			// should never happen, but you never know
			$message = $this->maniaControl->getChat()->formatMessage(
				'Cannot remove record no. %s, player does not exist!',
				$recordRank
			);
			$this->maniaControl->getChat()->sendError($message, $player);
			return;
		}

		$mysqli = $this->maniaControl->getDatabase()->getMysqli();
		$query  = "DELETE FROM `" . self::TABLE_RECORDS . "`
				WHERE `mapIndex` = {$currentMap->index}
				AND `playerIndex` = {$playerIndex};";
		$mysqli->query($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return;
		}

		$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_LOCALRECORDS_CHANGED, null);
		$message = $this->maniaControl->getChat()->formatMessage(
			'Record no. %s by %s has been removed!',
			$recordRank,
			$recordPlayer
		);
		$this->maniaControl->getChat()->sendSuccess($message);
	}

	/**
	 * Delete the personal record
	 *
	 * @internal
	 * @param array  $chat
	 * @param Player $player
	 */
	public function deletePersonalRecord(array $chat, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPluginPermission($this, $player, self::PERMISSION_DELETE_PERSONAL_RECORD)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}

		$currentMap = $this->maniaControl->getMapManager()->getCurrentMap();
		$records    = $this->getLocalRecords($currentMap);
		$hasRecord  = true;
		foreach ($records as $record) {
			if ($record->login === $player->login) {
				$hasRecord = true;
				break;
			}
		}

		if (!$hasRecord) {
			$this->maniaControl->getChat()->sendError('You have no personal record to remove!', $player);
			return;
		}

		$mysqli = $this->maniaControl->getDatabase()->getMysqli();
		$query  = "DELETE FROM `" . self::TABLE_RECORDS . "`
				WHERE `mapIndex` = {$currentMap->index}
				AND `playerIndex` = {$player->index};";
		$mysqli->query($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return;
		}

		$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_LOCALRECORDS_CHANGED, null);
		$message = $this->maniaControl->getChat()->formatMessage(
			'%s removed his personal record!',
			$player
		);
		$this->maniaControl->getChat()->sendSuccess($message);
	}

	/**
	 *  Update the RecordWidget variables
	 */
	private function updateRecordWidget() {
		$width      = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_WIDTH);
		$lineHeight = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_LINEHEIGHT);

		$this->recordWidget->setWidth($width);
		$this->recordWidget->setLineHeight($lineHeight);
		$this->updateManialink = true;
	}
}