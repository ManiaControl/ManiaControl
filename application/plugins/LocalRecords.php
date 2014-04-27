<?php
use FML\Controls\Control;
use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_BgsPlayerCard;
use FML\ManiaLink;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\Commands\CommandListener;
use ManiaControl\Settings\SettingManager;
use ManiaControl\Formatter;
use ManiaControl\ManiaControl;
use ManiaControl\Maps\Map;
use ManiaControl\Maps\MapManager;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;
use ManiaControl\Plugins\Plugin;

/**
 * ManiaControl Local Records Plugin
 *
 * @author steeffeen
 * @copyright ManiaControl Copyright © 2014 ManiaControl Team
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class LocalRecordsPlugin implements CallbackListener, CommandListener, TimerListener, Plugin {
	/**
	 * Constants
	 */
	const ID                          = 7;
	const VERSION                     = 0.1;
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
	/**
	 * @var maniaControl $maniaControl
	 */
	private $maniaControl = null;
	private $updateManialink = false;
	private $checkpoints = array();

	/**
	 * Prepares the Plugin
	 *
	 * @param ManiaControl $maniaControl
	 * @return mixed
	 */
	public static function prepare(ManiaControl $maniaControl) {
		//do nothing
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
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_AFTERINIT, $this, 'handleAfterInit');
		$this->maniaControl->callbackManager->registerCallbackListener(MapManager::CB_BEGINMAP, $this, 'handleMapBegin');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_TM_PLAYERFINISH, $this, 'handlePlayerFinish');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERCONNECT, $this, 'handlePlayerConnect');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_TM_PLAYERCHECKPOINT, $this, 'handlePlayerCheckpoint');
        $this->maniaControl->callbackManager->registerCallbackListener(SettingManager::CB_SETTINGS_CHANGED, $this, 'handleSettingsChanged');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 'handleManialinkPageAnswer');
		$this->maniaControl->commandManager->registerCommandListener('records', $this, 'showRecordsList');
		$this->maniaControl->commandManager->registerCommandListener('delrec', $this, 'deleteRecord', true);

		return true;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::unload()
	 */
	public function unload() {
		$this->maniaControl->callbackManager->unregisterCallbackListener($this);
		$this->maniaControl->timerManager->unregisterTimerListenings($this);
		unset($this->maniaControl);
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
			if(!strstr($mysqli->error, 'Duplicate')) {
				trigger_error($mysqli->error, E_USER_ERROR);
			}
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
		return 'Local Records Plugin';
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
		return 'steeffeen';
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getDescription()
	 */
	public static function getDescription() {
		return 'Plugin offering tracking of local records and manialinks to display them.';
	}

	/**
	 * Handle ManiaControl After Init
	 */
	public function handleAfterInit() {
		$this->updateManialink = true;
	}

	/**
	 * Handle 1Second callback
	 *
	 * @param $time
	 */
	public function handle1Second($time) {
        if (!$this->updateManialink) {
            return;
        }

        $this->updateManialink = false;
		if($this->maniaControl->settingManager->getSetting($this, self::SETTING_WIDGET_ENABLE)) {
			$manialink             = $this->buildManialink();
			$this->maniaControl->manialinkManager->sendManialink($manialink);
		}
    }


    public function handleSettingsChanged($class, $settingName, $value) {
        if (!$class = get_class()) {
            return;
        }
        if ($settingName == 'Enable Local Records Widget' && $value == true) {
            $this->updateManialink = true;
        } elseif ($settingName == 'Enable Local Records Widget' && $value == false) {
            $ml = new ManiaLink(self::MLID_RECORDS);
            $mltext = $ml->render()->saveXML();
            $this->maniaControl->manialinkManager->sendManialink($mltext);
        }
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
	 * Handle PlayerConnect callback
	 *
	 * @param Player $player
	 */
	public function handlePlayerConnect(Player $player) {
		$this->updateManialink = true;
	}

	/**
	 * Handle BeginMap callback
	 *
	 * @param Map $map
	 */
	public function handleMapBegin(Map $map) {
		$this->updateManialink = true;
	}

	/**
	 * Handle PlayerFinish callback
	 *
	 * @param array $callback
	 */
	public function handlePlayerFinish(array $callback) {
		$data = $callback[1];
		if ($data[0] <= 0 || $data[2] <= 0) {
			// Invalid player or time
			return;
		}

		$login  = $data[1];
		$player = $this->maniaControl->playerManager->getPlayer($login);
		if (!$player) {
			// Invalid player
			return;
		}

		$time = $data[2];
		$map  = $this->maniaControl->mapManager->getCurrentMap();

		// Check old record of the player
		$oldRecord = $this->getLocalRecord($map, $player);
		if ($oldRecord) {
			if ($oldRecord->time < $time) {
				// Not improved
				return;
			}
			if ($oldRecord->time == $time) {
				// Same time
				$message = '$<' . $player->nickname . '$> equalized her/his $<$o' . $oldRecord->rank . '.$> Local Record: ' . Formatter::formatTime($oldRecord->time);
				$this->maniaControl->chat->sendInformation($message);
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
				{$player->index},
				{$time},
				'{$this->getCheckpoints($player->login)}'
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
		$newRecord             = $this->getLocalRecord($map, $player);
		$this->maniaControl->callbackManager->triggerCallback(self::CB_LOCALRECORDS_CHANGED, $newRecord);

		$notifyOnlyDriver      = $this->maniaControl->settingManager->getSetting($this, self::SETTING_NOTIFY_ONLY_DRIVER);
		$notifyOnlyBestRecords = $this->maniaControl->settingManager->getSetting($this, self::SETTING_NOTIFY_BEST_RECORDS);
		if ($notifyOnlyDriver || $notifyOnlyBestRecords > 0 && $newRecord->rank > $notifyOnlyBestRecords) {
			$improvement = ((!$oldRecord || $newRecord->rank < $oldRecord->rank) ? 'gained the' : 'improved your');
			$message     = 'You ' . $improvement . ' $<$o' . $newRecord->rank . '.$> Local Record: ' . Formatter::formatTime($newRecord->time);
			if($oldRecord) $oldRank = ($improvement == 'improved your') ? '' : $oldRecord->rank.'. ';
			if($oldRecord) $message .= ' ('.$oldRank.'-'.Formatter::formatTime(($oldRecord->time-$newRecord->time)).')';
			$this->maniaControl->chat->sendInformation($message, $player->login);
		} else {
			$improvement = ((!$oldRecord || $newRecord->rank < $oldRecord->rank) ? 'gained the' : 'improved the');
			$message     = '$<' . $player->nickname . '$> ' . $improvement . ' $<$o' . $newRecord->rank . '.$> Local Record: ' . Formatter::formatTime($newRecord->time);
			if($oldRecord) $oldRank = ($improvement == 'improved the') ? '' : $oldRecord->rank.'. ';
			if($oldRecord) $message .= ' ('.$oldRank.'-'.Formatter::formatTime(($oldRecord->time-$newRecord->time)).')';
			$this->maniaControl->chat->sendInformation($message);
		}
	}

	/**
	 * Handle PlayerManialinkPageAnswer callback
	 *
	 * @param array $callback
	 */
	public function handleManialinkPageAnswer(array $callback) {
		$actionId    = $callback[1][2];

		$login  = $callback[1][1];
		$player = $this->maniaControl->playerManager->getPlayer($login);

		if($actionId == self::ACTION_SHOW_RECORDSLIST) {
			$this->showRecordsList(array(), $player);
		}
	}

	public function deleteRecord(array $chat, Player $player) {
		if(!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_MASTERADMIN)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}

		$chatCommand = explode(' ', $chat[1][2]);
		$recordId = (int)$chatCommand[1];
		if(is_integer($recordId)) {
			$currentMap = $this->maniaControl->mapManager->getCurrentMap();
			$records = $this->getLocalRecords($currentMap);
			if(count($records) < $recordId) {
				$this->maniaControl->chat->sendError('Cannot remove record $<$fff'.$recordId.'$>!', $player);
				return;
			}

			$mysqli = $this->maniaControl->database->mysqli;
			$removeRecord = $records[$recordId-1];
			$query  = "DELETE FROM `" . self::TABLE_RECORDS . "` WHERE `mapIndex` = ".$currentMap->index." AND `playerIndex` = ".$player->index."";
			$result = $mysqli->query($query);
			if ($mysqli->error) {
				trigger_error($mysqli->error);
				return null;
			}

			$this->maniaControl->callbackManager->triggerCallback(self::CB_LOCALRECORDS_CHANGED, null);
			$this->maniaControl->chat->sendInformation('Record no. $<$fff'.$recordId.'$> has been removed!');
		} else {
			$this->maniaControl->chat->sendError('Cannot remove record $<$fff'.$recordId.'$>, because it\'s not an integer!', $player);
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

		$pagesId = '';
		if (count($records) > 15) {
			$pagesId = 'RecordsListPages';
		}

		//create manialink
		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);
		$script    = $maniaLink->getScript();

		// Main frame
		$frame = $this->maniaControl->manialinkManager->styleManager->getDefaultListFrame($script, $pagesId);
		$maniaLink->add($frame);

		// Start offsets
		$x = -$width / 2;
		$y = $height / 2;

		// Predefine Description Label
		$descriptionLabel = $this->maniaControl->manialinkManager->styleManager->getDefaultDescriptionLabel();
		$frame->add($descriptionLabel);

		// Headline
		$headFrame = new Frame();
		$frame->add($headFrame);
		$headFrame->setY($y - 5);
		$array = array("Rank" => $x + 5, "Nickname" => $x + 18, "Login" => $x + 70, "Time" => $x + 101);
		$this->maniaControl->manialinkManager->labelLine($headFrame, $array);

		$i          = 0;
		$y          = $height / 2 - 10;
		$pageFrames = array();
		foreach($records as $listRecord) {
			if (!isset($pageFrame)) {
				$pageFrame = new Frame();
				$frame->add($pageFrame);
				if (!empty($pageFrames)) {
					$pageFrame->setVisible(false);
				}
				array_push($pageFrames, $pageFrame);
				$y = $height / 2 - 10;
				$script->addPage($pageFrame, count($pageFrames), $pagesId);
			}

			$recordFrame = new Frame();
			$pageFrame->add($recordFrame);

			if ($i % 2 != 0) {
				$lineQuad = new Quad_BgsPlayerCard();
				$recordFrame->add($lineQuad);
				$lineQuad->setSize($width, 4);
				$lineQuad->setSubStyle($lineQuad::SUBSTYLE_BgPlayerCardBig);
				$lineQuad->setZ(0.001);
			}

			if(strlen($listRecord->nickname) < 2) $listRecord->nickname = $listRecord->login;
			$array = array($listRecord->rank => $x + 5, '$fff'.$listRecord->nickname => $x + 18, $listRecord->login => $x + 70, Formatter::formatTime($listRecord->time) => $x + 101);
			$this->maniaControl->manialinkManager->labelLine($recordFrame, $array);

			$recordFrame->setY($y);

			$y -= 4;
			$i++;
			if ($i % 15 == 0) {
				unset($pageFrame);
			}
		}

		// Render and display xml
		$this->maniaControl->manialinkManager->displayWidget($maniaLink, $player, 'PlayerList');
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
	 * Build the local records manialink
	 *
	 * @return string
	 */
	private function buildManialink() {
		$map = $this->maniaControl->mapManager->getCurrentMap();
		if (!$map) {
			return null;
		}

		$title        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_WIDGET_TITLE);
		$pos_x        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_WIDGET_POSX);
		$pos_y        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_WIDGET_POSY);
		$width        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_WIDGET_WIDTH);
		$lines        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_WIDGET_LINESCOUNT);
		$lineHeight   = $this->maniaControl->settingManager->getSetting($this, self::SETTING_WIDGET_LINEHEIGHT);
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
		$frame->setPosition($pos_x, $pos_y);

		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setVAlign(Control::TOP);
		$adjustOuterBorder = $this->maniaControl->settingManager->getSetting($this, self::SETTING_ADJUST_OUTER_BORDER);
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
		foreach($records as $index => $record) {
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

			$rankLabel = new Label();
			$recordFrame->add($rankLabel);
			$rankLabel->setHAlign(Control::LEFT);
			$rankLabel->setX($width * -0.47);
			$rankLabel->setSize($width * 0.06, $lineHeight);
			$rankLabel->setTextSize(1);
			$rankLabel->setTextPrefix('$o');
			$rankLabel->setText($record->rank);
			$rankLabel->setTextEmboss(true);

			$nameLabel = new Label();
			$recordFrame->add($nameLabel);
			$nameLabel->setHAlign(Control::LEFT);
			$nameLabel->setX($width * -0.4);
			$nameLabel->setSize($width * 0.6, $lineHeight);
			$nameLabel->setTextSize(1);
			$nameLabel->setText($record->nickname);
			$nameLabel->setTextEmboss(true);

			$timeLabel = new Label();
			$recordFrame->add($timeLabel);
			$timeLabel->setHAlign(Control::RIGHT);
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
		$limit  = ($limit > 0 ? "LIMIT " . $limit : "");
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
		while($record = $result->fetch_object()) {
			array_push($records, $record);
		}
		$result->free();
		return $records;
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
}

