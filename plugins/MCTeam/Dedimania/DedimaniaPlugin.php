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
use ManiaControl\Callbacks\CallQueueListener;
use ManiaControl\Callbacks\Structures\TrackMania\OnWayPointEventStructure;
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\Commands\CommandListener;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;
use ManiaControl\Plugins\Plugin;
use ManiaControl\Settings\Setting;
use ManiaControl\Settings\SettingManager;
use ManiaControl\Utils\Formatter;
use MCTeam\Common\RecordWidget;

/**
 * ManiaControl Dedimania Plugin
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class DedimaniaPlugin implements CallbackListener, CallQueueListener, CommandListener, TimerListener, Plugin {
	/*
	 * Constants
	 */
	const ID             = 8;
	const VERSION        = 0.62;
	const AUTHOR         = 'MCTeam';
	const NAME           = 'Dedimania Plugin';
	const MLID_DEDIMANIA = 'Dedimania.ManialinkId';

	const SETTING_WIDGET_ENABLE               = 'Enable Dedimania Widget';
	const SETTING_WIDGET_TITLE                = 'Widget Title';
	const SETTING_WIDGET_POSX                 = 'Widget Position: X';
	const SETTING_WIDGET_POSY                 = 'Widget Position: Y';
	const SETTING_WIDGET_WIDTH                = 'Widget Width';
	const SETTING_WIDGET_LINE_COUNT           = 'Widget Displayed Lines Count';
	const SETTING_WIDGET_LINE_HEIGHT          = 'Widget Line Height';
	const SETTING_DEDIMANIA_CODE              = '$l[http://dedimania.net/tm2stats/?do=register]Dedimania Code for ';
	const SETTING_NOTIFICATION_MESSAGE_PREFIX = 'Notification Message Prefix';
	const SETTING_NOTIFY_BEST_RECORDS_PRIVATE = 'Notify Privately only for the X Best Records';
	const SETTING_NOTIFY_BEST_RECORDS_PUBLIC  = 'Notify Publicly only for the X Best Records';
	const SETTING_MAX_RECORDS                 = 'Max Records, only increase if you bought a rank update from Dedimania!';

	const CB_DEDIMANIA_CHANGED        = 'Dedimania.Changed';
	const CB_DEDIMANIA_UPDATED        = 'Dedimania.Updated';
	const ACTION_SHOW_DEDIRECORDSLIST = 'Dedimania.ShowDediRecordsList';

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;

	/** @var \MCTeam\Common\RecordWidget $recordWidget */
	private $recordWidget = null;

	private $checkpoints = array();
	private $allCheckpoints = array();

	/** @var \MCTeam\Dedimania\DedimaniaWebHandler $webHandler */
	private $webHandler = null;

	private $isMultilap = false;
	private $isRounds = false;
	
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
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_NOTIFICATION_MESSAGE_PREFIX, '$390');
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_NOTIFY_BEST_RECORDS_PRIVATE, 30);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_NOTIFY_BEST_RECORDS_PUBLIC, 15);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_MAX_RECORDS, 30);

		// Callbacks
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::BEGINMAP, $this, 'handleBeginMap');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::ENDMAP, $this, 'handleMapEnd');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 'handleManialinkPageAnswer');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(PlayerManager::CB_PLAYERCONNECT, $this, 'handlePlayerConnect');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(PlayerManager::CB_PLAYERDISCONNECT, $this, 'handlePlayerDisconnect');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::TM_ONWAYPOINT, $this, 'handleCheckpointCallback');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::TM_ONFINISHLINE, $this, 'handleFinishCallback');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::TM_ONLAPFINISH, $this, 'handleFinishCallback');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(SettingManager::CB_SETTING_CHANGED, $this, 'handleSettingChanged');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::AFTERLOOP, $this, 'handleAfterLoop');

		$this->maniaControl->getTimerManager()->registerTimerListening($this, 'updateEverySecond', 1000);
		$this->maniaControl->getTimerManager()->registerTimerListening($this, 'handleEveryMinute', 1000 * 60);

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

		if ($this->maniaControl->getMapManager()->getCurrentMap()->getGame() !== 'tm') {
			throw new \Exception("This Plugin is only for Trackmania!");
		}

		$maxRecords = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MAX_RECORDS);

		$dedimaniaData = new DedimaniaData($serverInfo->login, $dedimaniaCode, $serverInfo->path, $packMask, $serverVersion);
		$dedimaniaData->serverMaxRank = $maxRecords;

		//New Version
		$this->webHandler = new DedimaniaWebHandler($this->maniaControl);
		$this->webHandler->setDedimaniaData($dedimaniaData);
		$this->webHandler->openDedimaniaSession(true);

		$this->recordWidget = new RecordWidget($this->maniaControl);
		
		if ($this->maniaControl->getServer()->getGameMode() == 0) {
			$gameMode = $this->maniaControl->getClient()->getScriptName()['CurrentValue'];
			$this->isMultilap = ($gameMode == 'Laps.Script.txt' || $this->maniaControl->getMapManager()->getCurrentMap()->nbLaps > 0);
			$this->isRounds = ($gameMode == 'Rounds.Script.txt');			
		} else {
			$this->isMultilap = ($this->maniaControl->getServer()->getGameMode(true) == 'Laps' || $this->maniaControl->getMapManager()->getCurrentMap()->nbLaps > 0);
			$this->isRounds = ($this->maniaControl->getServer()->getGameMode(true) == 'Rounds');
		}
	}


	/**
	 * Handle 1 Second Callback
	 *
	 * @internal
	 */
	public function updateEverySecond() {
		if (!$this->webHandler->doesManiaLinkNeedUpdate()) {
			return;
		}
		$this->webHandler->maniaLinkUpdated();

		if ($this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_ENABLE)) {
			$this->sendManialink();
		} else {
			$this->maniaControl->getManialinkManager()->hideManialink(self::MLID_DEDIMANIA);
		}
	}

	/**
	 * Builds and Sends the Manialink
	 */
	private function sendManialink() {
		$records = $this->webHandler->getDedimaniaData()->records;

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
		$backgroundQuad->setAction(self::ACTION_SHOW_DEDIRECORDSLIST);

		$titleLabel = new Label();
		$frame->addChild($titleLabel);
		$titleLabel->setPosition(0, $lineHeight * -0.9);
		$titleLabel->setWidth($width);
		$titleLabel->setStyle($labelStyle);
		$titleLabel->setTextSize(2);
		$titleLabel->setText($title);
		$titleLabel->setTranslate(true);

		$recordsFrame = $this->recordWidget->generateRecordsFrame($records, $lines);
		$frame->addChild($recordsFrame);

		$this->maniaControl->getManialinkManager()->sendManialink($manialink);
	}

	/**
	 * Handle 1 Minute Callback
	 *
	 * @internal
	 */
	public function handleEveryMinute() {
		if ($this->webHandler->getDedimaniaData()->sessionId == "") {
			return;
		}
		$this->webHandler->checkDedimaniaSession();
	}

	/**
	 * Handle PlayerConnect callback
	 *
	 * @internal
	 * @param Player $player
	 */
	public function handlePlayerConnect(Player $player) {
		$this->webHandler->handlePlayerConnect($player);
	}

	/**
	 * Handle Player Disconnect Callback
	 *
	 * @internal
	 * @param Player $player
	 */
	public function handlePlayerDisconnect(Player $player) {
		$this->webHandler->handlePlayerDisconnect($player);
	}

	/**
	 * Handle Begin Map Callback
	 *
	 * @internal
	 */
	public function handleBeginMap() {
		$this->checkpoints = null;
		$this->allCheckpoints = null;
		$this->webHandler->getDedimaniaData()->unsetRecords();
		$this->webHandler->maniaLinkUpdateNeeded();
		$this->webHandler->fetchDedimaniaRecords(true);
	}

	/**
	 * Handle EndMap Callback
	 *
	 * @internal
	 */
	public function handleMapEnd() {
		$this->webHandler->submitChallengeTimes();
	}

	/**
	 * Handle Checkpoint Callback
	 *
	 * @internal
	 * @param OnWayPointEventStructure $callback
	 */
	public function handleCheckpointCallback(OnWayPointEventStructure $structure) {
		if (!$structure->getLapTime()) {
			return;
		}

		$login = $structure->getLogin();
		if (!isset($this->checkpoints[$login])) {
			$this->checkpoints[$login] = array();
		}
		if (!isset($this->allCheckpoints[$login])) {
			$this->allCheckpoints[$login] = array();
		}

		$this->checkpoints[$login][$structure->getCheckPointInLap()] = $structure->getLapTime();
		$this->allCheckpoints[$login][$structure->getCheckPointInRace()] = $structure->getRaceTime();
	}

	/**
	 * Handle Finish Callback
	 *
	 * @internal
	 * @param \ManiaControl\Callbacks\Structures\TrackMania\OnWayPointEventStructure $structure
	 */
	public function handleFinishCallback(OnWayPointEventStructure $structure) {
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

		$this->checkpoints[$player->login][$structure->getCheckPointInLap()] = $structure->getLapTime();
		$this->allCheckpoints[$player->login][$structure->getCheckPointInRace()] = $structure->getRaceTime();
		
		$recTime = $structure->getLapTime();
		if ($this->isMultilap && $this->isRounds) {
			$recTime = $structure->getRaceTime();
			if (!$structure->getIsEndRace()) {
				return;
			}
		}

		$oldRecord = $this->getDedimaniaRecord($player->login);
		if ($oldRecord->nullRecord || $oldRecord && $oldRecord->best > $recTime) {
			// Save time
			$newRecord = new RecordData(null);

			$checkPoints = $this->getCheckpoints($player->login);
			$allCPs = $this->getAllCheckpoints($player->login);

			if ($this->isMultilap && $this->isRounds) {
				$newRecord->constructNewRecord($player->login, $player->nickname, $recTime, $allCPs, true, $allCPs);				
			} else {
				$newRecord->constructNewRecord($player->login, $player->nickname, $recTime, $checkPoints, true, $allCPs);				
			}

			if ($this->insertDedimaniaRecord($newRecord, $oldRecord)) {
				// Get newly saved record
				foreach ($this->webHandler->getDedimaniaData()->records as &$record) {
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
					$improvement = 'improved the';
				}

				$notifyName = '';
				$notifyOnlyBestRecordsPrivate = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_NOTIFY_BEST_RECORDS_PRIVATE);
				$notifyOnlyBestRecordsPublic = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_NOTIFY_BEST_RECORDS_PUBLIC);
				if ($newRecord->rank <= $notifyOnlyBestRecordsPublic) {
					$notifyName = $player->nickname;
				} elseif ($newRecord->rank <= $notifyOnlyBestRecordsPrivate) {
					$notifyName = 'You';
				}

				$messagePrefix = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_NOTIFICATION_MESSAGE_PREFIX);
				$message = $messagePrefix . '$<$fff' . $notifyName . '$> ' . $improvement . ' $<$ff0' . $newRecord->rank . '.$> Dedimania Record: $<$fff' . Formatter::formatTime($newRecord->best) . '$>';
				if (!$oldRecord->nullRecord) {
					$message .= ' ($<$ff0' . $oldRecord->rank . '.$> $<$fff-' . Formatter::formatTime(($oldRecord->best - $recTime)) . '$>)';
				}
				$message .= '!';

				if ($newRecord->rank <= $notifyOnlyBestRecordsPublic) {
					$this->maniaControl->getCallQueueManager()->registerListening(
						$this,
						function () use ($message) {
							$this->maniaControl->getChat()->sendInformation($message);
						}
					);
				} elseif ($newRecord->rank <= $notifyOnlyBestRecordsPrivate) {
					$this->maniaControl->getCallQueueManager()->registerListening(
						$this,
						function () use ($message, $player) {
							$this->maniaControl->getChat()->sendInformation($message, $player);
						}
					);
				}

				$this->webHandler->maniaLinkUpdateNeeded();
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
		if (!$this->webHandler->getDedimaniaData()->recordsExisting()) {
			return new RecordData(null);
		}
		$records = $this->webHandler->getDedimaniaData()->records;
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
	 * Get total checkpoint string for dedimania record
	 *
	 * @param string $login
	 * @return string
	 */
	private function getAllCheckpoints($login) {
		if (!$login || !isset($this->allCheckpoints[$login])) {
			return null;
		}
		$string = '';
		$count  = count($this->allCheckpoints[$login]);
		foreach ($this->allCheckpoints[$login] as $index => $check) {
			$string .= strval($check);
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
		$maxRank = $this->webHandler->getDedimaniaData()->getPlayerMaxRank($newRecord->login);

		// Loop through existing records
		foreach ($this->webHandler->getDedimaniaData()->records as $key => &$record) {
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
				$this->webHandler->getDedimaniaData()->deleteRecordByIndex($key);
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
				foreach ($this->webHandler->getDedimaniaData()->records as $key2 => $record2) {
					if ($record2->login !== $oldRecord->login) {
						continue;
					}
					unset($this->webHandler->getDedimaniaData()->records[$key2]);
					break;
				}
			}
			break;
		}

		if (!$insert && count($this->webHandler->getDedimaniaData()->records) < $maxRank) {
			// Records list not full - Append new record
			$insert = true;
		}

		if ($insert) {
			// Insert new record
			array_push($this->webHandler->getDedimaniaData()->records, $newRecord);

			// Update ranks
			$this->updateDedimaniaRecordRanks();

			// Save replays
			foreach ($this->webHandler->getDedimaniaData()->records as &$record) {
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
		if (!$this->webHandler->getDedimaniaData()->recordsExisting()) {
			$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_DEDIMANIA_UPDATED, $this->webHandler->getDedimaniaData()->records);
			return;
		}

		//Sort Records
		$this->webHandler->getDedimaniaData()->sortRecords();

		// Update ranks
		$this->webHandler->getDedimaniaData()->updateRanks();

		$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_DEDIMANIA_UPDATED, $this->webHandler->getDedimaniaData()->records);
	}

	/**
	 * Update the replay values for the given record
	 *
	 * @param RecordData $record
	 */
	private function setRecordReplays(RecordData &$record) {
		// Set validation replay
		//TODO verify why it can be that login is not set
		if (!$record->login) {
			return;
		}

		$validationReplay = $this->maniaControl->getServer()->getValidationReplay($record->login);
		if ($validationReplay) {
			$record->vReplay = $validationReplay;
		} else {
			return;
		}

		// Set ghost replay
		if ($record->rank <= 1) {
			$dataDirectory = $this->maniaControl->getServer()->getDirectory()->getUserDataFolder();
			if (!isset($this->webHandler->getDedimaniaData()->directoryAccessChecked)) {
				$access = $this->maniaControl->getServer()->checkAccess($dataDirectory);
				if (!$access) {
					trigger_error("No access to the servers data directory. Can't retrieve ghost replays.");
				}
				$this->webHandler->getDedimaniaData()->directoryAccessChecked = $access;
			}
			if ($this->webHandler->getDedimaniaData()->directoryAccessChecked) {
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
	 * @internal
	 * @param array $callback
	 */
	public function handleManialinkPageAnswer(array $callback) {
		$actionId = $callback[1][2];
		//TODO use manialinkpageanswerlistener
		$login  = $callback[1][1];
		$player = $this->maniaControl->getPlayerManager()->getPlayer($login);

		if ($actionId === self::ACTION_SHOW_DEDIRECORDSLIST) {
			$this->showDediRecordsList(array(), $player);
		}
	}

	/**
	 * Process Dedimania Webreqests at end of Loop
	 *
	 * @internal
	 */
	public function handleAfterLoop(){
		$this->webHandler->callDedimania();
	}

	/**
	 * Shows a ManiaLink list with the local records.
	 *
	 * @api
	 * @param array  $chat
	 * @param Player $player
	 */
	public function showDediRecordsList(array $chat, Player $player) {
		$width  = $this->maniaControl->getManialinkManager()->getStyleManager()->getListWidgetsWidth();
		$height = $this->maniaControl->getManialinkManager()->getStyleManager()->getListWidgetsHeight();

		// get PlayerList
		$records = $this->webHandler->getDedimaniaData()->records;
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
				$lineQuad->setZ(-0.001);
			}

			if (strlen($listRecord->nickname) < 2) {
				$listRecord->nickname = $listRecord->login;
			}
			$array = array($listRecord->rank => $posX + 5, '$fff' . $listRecord->nickname => $posX + 18, $listRecord->login => $posX + 70, Formatter::formatTime($listRecord->best) => $posX + 101);
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
	 * @api
	 * @return RecordData[]
	 */
	public function getDedimaniaRecords() {
		return $this->webHandler->getDedimaniaData()->records;
	}

	/**
	 *  Update the RecordWidget variables
	 *
	 */
	private function updateRecordWidget() {
		$width      = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_WIDTH);
		$lineHeight = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_LINE_HEIGHT);

		$this->recordWidget->setWidth($width);
		$this->recordWidget->setLineHeight($lineHeight);

		$this->webHandler->maniaLinkUpdateNeeded();
	}

	/**
	 * Handle settings Changed
	 *
	 * @internal
	 * @param \ManiaControl\Settings\Setting $setting
	 */
	public function handleSettingChanged(Setting $setting) {
		if (!$setting->belongsToClass($this)) {
			return;
		}
		$this->updateRecordWidget();
	}


	/**
	 * @see \ManiaControl\Plugins\Plugin::unload()
	 */
	public function unload() {
		$this->maniaControl->getManialinkManager()->hideManialink(self::MLID_DEDIMANIA);
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
}
