<?php

namespace MCTeam;

use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_BgsPlayerCard;
use FML\ManiaLink;
use FML\Script\Features\Paging;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Callbacks\Callbacks;
use ManiaControl\Callbacks\Models\RecordCallback;
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\Commands\CommandListener;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Maps\Map;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;
use ManiaControl\Plugins\Plugin;
use ManiaControl\Settings\Setting;
use ManiaControl\Settings\SettingManager;
use ManiaControl\Utils\Formatter;

/**
 * ManiaControl Local Records Plugin
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class LocalRecordsPlugin implements CallbackListener, CommandListener, TimerListener, Plugin {
	/*
	 * Constants
	 */
	const ID                          = 7;
	const VERSION                     = 0.2;
	const NAME                        = 'Local Records Plugin';
	const AUTHOR                      = 'MCTeam';
	const MLID_RECORDS                = 'ml_local_records';
	const TABLE_RECORDS               = 'mc_localrecords';
	const SETTING_WIDGET_TITLE        = 'Widget Title';
	const SETTING_WIDGET_POSX         = 'Widget Position: X';
	const SETTING_WIDGET_POSY         = 'Widget Position: Y';
	const SETTING_WIDGET_WIDTH        = 'Widget Width';
	const SETTING_WIDGET_LINESCOUNT   = 'Widget Displayed Lines Count';
	const SETTING_WIDGET_LINEHEIGHT   = 'Widget Line Height';
	const SETTING_WIDGET_ENABLE       = 'Enable Local Records Widget';
	const SETTING_NOTIFY_ONLY_DRIVER  = 'Notify only the Driver on New Records';
	const SETTING_NOTIFY_BEST_RECORDS = 'Notify Publicly only for the X Best Records';
	const SETTING_ADJUST_OUTER_BORDER = 'Adjust outer Border to Number of actual Records';
	const CB_LOCALRECORDS_CHANGED     = 'LocalRecords.Changed';
	const ACTION_SHOW_RECORDSLIST     = 'LocalRecords.ShowRecordsList';

	/*
	 * Private Properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;
	private $updateManialink = false;
	private $checkpoints = array();

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
	 */
	public function load(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->initTables();

		// Init settings
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_WIDGET_TITLE, 'Local Records');
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_WIDGET_POSX, -139.);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_WIDGET_POSY, 75);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_WIDGET_WIDTH, 40.);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_WIDGET_LINESCOUNT, 15);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_WIDGET_LINEHEIGHT, 4.);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_WIDGET_ENABLE, true);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_NOTIFY_ONLY_DRIVER, false);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_NOTIFY_BEST_RECORDS, -1);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_ADJUST_OUTER_BORDER, false);

		// Register for callbacks
		$this->maniaControl->timerManager->registerTimerListening($this, 'handle1Second', 1000);

		$this->maniaControl->callbackManager->registerCallbackListener(Callbacks::AFTERINIT, $this, 'handleAfterInit');
		$this->maniaControl->callbackManager->registerCallbackListener(Callbacks::BEGINMAP, $this, 'handleMapBegin');
		$this->maniaControl->callbackManager->registerCallbackListener(SettingManager::CB_SETTING_CHANGED, $this, 'handleSettingChanged');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 'handleManialinkPageAnswer');

		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERCONNECT, $this, 'handlePlayerConnect');
		$this->maniaControl->callbackManager->registerCallbackListener(RecordCallback::CHECKPOINT, $this, 'handleCheckpointCallback');
		$this->maniaControl->callbackManager->registerCallbackListener(RecordCallback::LAPFINISH, $this, 'handleLapFinishCallback');
		$this->maniaControl->callbackManager->registerCallbackListener(RecordCallback::FINISH, $this, 'handleFinishCallback');

		$this->maniaControl->commandManager->registerCommandListener(array('recs', 'records'), $this, 'showRecordsList', false, 'Shows a list of Local Records on the current map.');
		$this->maniaControl->commandManager->registerCommandListener('delrec', $this, 'deleteRecord', true, 'Removes a record from the database.');

		$this->updateManialink = true;

		return true;
	}

	/**
	 * Initialize needed database tables
	 */
	private function initTables() {
		$mysqli = $this->maniaControl->database->mysqli;
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
			if (!strstr($mysqli->error, 'Duplicate')) {
				trigger_error($mysqli->error, E_USER_ERROR);
			}
		}
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::unload()
	 */
	public function unload() {
		$this->maniaControl->manialinkManager->hideManialink(self::MLID_RECORDS);
	}

	/**
	 * Handle ManiaControl After Init
	 */
	public function handleAfterInit() {
		$this->updateManialink = true;
	}

	/**
	 * Handle 1 Second Callback
	 */
	public function handle1Second() {
		if (!$this->updateManialink) {
			return;
		}

		$this->updateManialink = false;
		if ($this->maniaControl->settingManager->getSettingValue($this, self::SETTING_WIDGET_ENABLE)) {
			$manialink = $this->buildManialink();
			$this->maniaControl->manialinkManager->sendManialink($manialink);
		}
	}

	/**
	 * Build the local records manialink
	 *
	 * @return string
	 */
	private function buildManialink() {
		$map = $this->maniaControl->mapManager->getCurrentMap();
		if (!$map) {
			return null;
		}

		$title        = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_WIDGET_TITLE);
		$posX         = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_WIDGET_POSX);
		$posY         = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_WIDGET_POSY);
		$width        = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_WIDGET_WIDTH);
		$lines        = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_WIDGET_LINESCOUNT);
		$lineHeight   = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_WIDGET_LINEHEIGHT);
		$labelStyle   = $this->maniaControl->manialinkManager->styleManager->getDefaultLabelStyle();
		$quadStyle    = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadStyle();
		$quadSubstyle = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadSubstyle();

		$records = $this->getLocalRecords($map);
		if (!is_array($records)) {
			trigger_error("Couldn't fetch player records.");
			return null;
		}

		$manialink = new ManiaLink(self::MLID_RECORDS);
		$frame     = new Frame();
		$manialink->add($frame);
		$frame->setPosition($posX, $posY);

		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setVAlign($backgroundQuad::TOP);
		$adjustOuterBorder = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_ADJUST_OUTER_BORDER);
		$height            = 7. + ($adjustOuterBorder ? count($records) : $lines) * $lineHeight;
		$backgroundQuad->setSize($width * 1.05, $height);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);
		$backgroundQuad->setAction(self::ACTION_SHOW_RECORDSLIST);

		$titleLabel = new Label();
		$frame->add($titleLabel);
		$titleLabel->setPosition(0, $lineHeight * -0.9);
		$titleLabel->setWidth($width);
		$titleLabel->setStyle($labelStyle);
		$titleLabel->setTextSize(2);
		$titleLabel->setText($title);
		$titleLabel->setTranslate(true);

		// Times
		foreach ($records as $index => $record) {
			if ($index >= $lines) {
				break;
			}

			$y = -8. - $index * $lineHeight;

			$recordFrame = new Frame();
			$frame->add($recordFrame);
			$recordFrame->setPosition(0, $y);

			/*
			 * $backgroundQuad = new Quad(); $recordFrame->add($backgroundQuad); $backgroundQuad->setSize($width * 1.04, $lineHeight * 1.4); $backgroundQuad->setStyles($quadStyle, $quadSubstyle);
			 */

			$rankLabel = new Label();
			$recordFrame->add($rankLabel);
			$rankLabel->setHAlign($rankLabel::LEFT);
			$rankLabel->setX($width * -0.47);
			$rankLabel->setSize($width * 0.06, $lineHeight);
			$rankLabel->setTextSize(1);
			$rankLabel->setTextPrefix('$o');
			$rankLabel->setText($record->rank);
			$rankLabel->setTextEmboss(true);

			$nameLabel = new Label();
			$recordFrame->add($nameLabel);
			$nameLabel->setHAlign($nameLabel::LEFT);
			$nameLabel->setX($width * -0.4);
			$nameLabel->setSize($width * 0.6, $lineHeight);
			$nameLabel->setTextSize(1);
			$nameLabel->setText($record->nickname);
			$nameLabel->setTextEmboss(true);

			$timeLabel = new Label();
			$recordFrame->add($timeLabel);
			$timeLabel->setHAlign($timeLabel::RIGHT);
			$timeLabel->setX($width * 0.47);
			$timeLabel->setSize($width * 0.25, $lineHeight);
			$timeLabel->setTextSize(1);
			$timeLabel->setText(Formatter::formatTime($record->time));
			$timeLabel->setTextEmboss(true);
		}

		return $manialink;
	}

	/**
	 * Fetch local records for the given map
	 *
	 * @param Map $map
	 * @param int $limit
	 * @return array
	 */
	public function getLocalRecords(Map $map, $limit = -1) {
		$mysqli = $this->maniaControl->database->mysqli;
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
	 * @param Setting $setting
	 */
	public function handleSettingChanged(Setting $setting) {
		if (!$setting->belongsToClass($this)) {
			return;
		}

		switch ($setting->setting) {
			case self::SETTING_WIDGET_ENABLE:
			{
				if ($setting->value) {
					$this->updateManialink = true;
				} else {
					$this->maniaControl->manialinkManager->hideManialink(self::MLID_RECORDS);
				}
				break;
			}
		}
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

		$checkpointsString                   = $this->getCheckpoints($callback->player->login);
		$this->checkpoints[$callback->login] = array();

		// Check old record of the player
		$oldRecord = $this->getLocalRecord($map, $callback->player);
		if ($oldRecord) {
			if ($oldRecord->time < $callback->time) {
				// Not improved
				return;
			}
			if ($oldRecord->time == $callback->time) {
				// Same time
				// TODO: respect notify-settings
				$message = '$<$fff' . $callback->player->nickname . '$> equalized his/her $<$ff0' . $oldRecord->rank . '.$> Local Record: $<$fff' . Formatter::formatTime($oldRecord->time) . '$>!';
				$this->maniaControl->chat->sendInformation('$3c0' . $message);
				return;
			}
		}

		// Save time
		$mysqli = $this->maniaControl->database->mysqli;
		$query  = "INSERT INTO `" . self::TABLE_RECORDS . "` (
				`mapIndex`,
				`playerIndex`,
				`time`,
				`checkpoints`
				) VALUES (
				{$map->index},
				{$callback->player->index},
				{$callback->time},
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
		$newRecord    = $this->getLocalRecord($map, $callback->player);
		$improvedRank = (!$oldRecord || $newRecord->rank < $oldRecord->rank);

		$notifyOnlyDriver      = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_NOTIFY_ONLY_DRIVER);
		$notifyOnlyBestRecords = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_NOTIFY_BEST_RECORDS);

		$message = '$3c0';
		if ($notifyOnlyDriver) {
			$message .= 'You';
		} else {
			$message .= '$<$fff' . $callback->player->nickname . '$>';
		}
		$message .= ' ' . ($improvedRank ? 'gained' : 'improved') . ' the';
		$message .= ' $<$ff0' . $newRecord->rank . '.$> Local Record:';
		$message .= ' $<$fff' . Formatter::formatTime($newRecord->time) . '$>!';
		if ($oldRecord) {
			$message .= ' (';
			if ($improvedRank) {
				$message .= '$<$ff0' . $oldRecord->rank . '.$> ';
			}
			$timeDiff = $oldRecord->time - $newRecord->time;
			$message .= '$<$fff-' . Formatter::formatTime($timeDiff) . '$>)';
		}

		if ($notifyOnlyDriver) {
			$this->maniaControl->chat->sendInformation($message, $callback->player);
		} else if (!$notifyOnlyBestRecords || $newRecord->rank <= $notifyOnlyBestRecords) {
			$this->maniaControl->chat->sendInformation($message);
		}

		$this->maniaControl->callbackManager->triggerCallback(self::CB_LOCALRECORDS_CHANGED, $newRecord);
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
	 * Retrieve the local record for the given map and login
	 *
	 * @param Map    $map
	 * @param Player $player
	 * @return mixed
	 */
	private function getLocalRecord(Map $map, Player $player) {
		$mysqli = $this->maniaControl->database->mysqli;
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
	 */
	public function handlePlayerConnect() {
		$this->updateManialink = true;
	}

	/**
	 * Handle Begin Map Callback
	 */
	public function handleMapBegin() {
		$this->updateManialink = true;
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

		if ($actionId === self::ACTION_SHOW_RECORDSLIST) {
			$this->showRecordsList(array(), $player);
		}
	}

	/**
	 * Shows a ManiaLink list with the local records.
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function showRecordsList(array $chat, Player $player) {
		$width  = $this->maniaControl->manialinkManager->styleManager->getListWidgetsWidth();
		$height = $this->maniaControl->manialinkManager->styleManager->getListWidgetsHeight();

		// get PlayerList
		$records = $this->getLocalRecords($this->maniaControl->mapManager->getCurrentMap());

		// create manialink
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
		$array = array('Rank' => $posX + 5, 'Nickname' => $posX + 18, 'Login' => $posX + 70, 'Time' => $posX + 101);
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

			if (strlen($listRecord->nickname) < 2) {
				$listRecord->nickname = $listRecord->login;
			}
			$array = array($listRecord->rank => $posX + 5, '$fff' . $listRecord->nickname => $posX + 18, $listRecord->login => $posX + 70, Formatter::formatTime($listRecord->time) => $posX + 101);
			$this->maniaControl->manialinkManager->labelLine($recordFrame, $array);

			$recordFrame->setY($posY);

			$posY -= 4;
			$index++;
		}

		// Render and display xml
		$this->maniaControl->manialinkManager->displayWidget($maniaLink, $player, 'PlayerList');
	}

	/**
	 * Delete a Player's record
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function deleteRecord(array $chat, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_MASTERADMIN)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}

		$commandParts = explode(' ', $chat[1][2]);
		if (count($commandParts) < 2) {
			$this->maniaControl->chat->sendUsageInfo('Missing Record ID! (Example: //delrec 3)', $player);
			return;
		}

		$recordId   = (int)$commandParts[1];
		$currentMap = $this->maniaControl->mapManager->getCurrentMap();
		$records    = $this->getLocalRecords($currentMap);
		if (count($records) < $recordId) {
			$this->maniaControl->chat->sendError('Cannot remove record $<$fff' . $recordId . '$>!', $player);
			return;
		}

		$mysqli = $this->maniaControl->database->mysqli;
		$query  = "DELETE FROM `" . self::TABLE_RECORDS . "`
				WHERE `mapIndex` = {$currentMap->index}
				AND `playerIndex` = {$player->index};";
		$mysqli->query($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return;
		}

		$this->maniaControl->callbackManager->triggerCallback(self::CB_LOCALRECORDS_CHANGED, null);
		$this->maniaControl->chat->sendInformation('Record no. $<$fff' . $recordId . '$> has been removed!');
	}
}
