<?php
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\Formatter;
use ManiaControl\ManiaControl;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Maps\Map;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;
use ManiaControl\Plugins\Plugin;
use FML\ManiaLink;
use FML\Controls\Control;
use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Controls\Quad;

/**
 * ManiaControl Local Records Plugin
 *
 * @author steeffeen
 */
class LocalRecordsPlugin implements CallbackListener, TimerListener, Plugin {
	/**
	 * Constants
	 */
	const ID = 6;
	const VERSION = 0.1;
	const MLID_RECORDS = 'ml_local_records';
	const TABLE_RECORDS = 'mc_localrecords';
	const SETTING_WIDGET_TITLE = 'Widget Title';
	const SETTING_WIDGET_POSX = 'Widget Position: X';
	const SETTING_WIDGET_POSY = 'Widget Position: Y';
	const SETTING_WIDGET_WIDTH = 'Widget Width';
	const SETTING_WIDGET_LINESCOUNT = 'Widget Displayed Lines Count';
	const SETTING_WIDGET_LINEHEIGHT = 'Widget Line Height';
	const SETTING_NOTIFY_ONLY_DRIVER = 'Notify only the Driver on New Records';
	const SETTING_NOTIFY_BEST_RECORDS = 'Notify Publicly only for the X Best Records';
	
	/**
	 * Private properties
	 */
	/**
	 *
	 * @var maniaControl $maniaControl
	 */
	private $maniaControl = null;
	private $updateManialink = false;

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
	 *
	 * @see \ManiaControl\Plugins\Plugin::load()
	 */
	public function load(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->initTables();
		
		// Init settings
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_WIDGET_TITLE, 'Local Records');
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_WIDGET_POSX, -139.);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_WIDGET_POSY, 65.);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_WIDGET_WIDTH, 40.);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_WIDGET_LINESCOUNT, 25);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_WIDGET_LINEHEIGHT, 4.);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_NOTIFY_ONLY_DRIVER, false);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_NOTIFY_BEST_RECORDS, -1);
		
		// Register for callbacks
		$this->maniaControl->timerManager->registerTimerListening($this, 'handle1Second', 1000);
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_ONINIT, $this, 'handleOnInit');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_BEGINMAP, $this, 'handleMapBegin');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_CLIENTUPDATED, $this, 
				'handleClientUpdated');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_TM_PLAYERFINISH, $this, 
				'handlePlayerFinish');
		
		return true;
	}

	/**
	 *
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
		$query = "CREATE TABLE IF NOT EXISTS `" . self::TABLE_RECORDS . "` (
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
	}

	/**
	 *
	 * @see \ManiaControl\Plugins\Plugin::getId()
	 */
	public static function getId() {
		return self::ID;
	}

	/**
	 *
	 * @see \ManiaControl\Plugins\Plugin::getName()
	 */
	public static function getName() {
		return 'Local Records Plugin';
	}

	/**
	 *
	 * @see \ManiaControl\Plugins\Plugin::getVersion()
	 */
	public static function getVersion() {
		return self::VERSION;
	}

	/**
	 *
	 * @see \ManiaControl\Plugins\Plugin::getAuthor()
	 */
	public static function getAuthor() {
		return 'steeffeen';
	}

	/**
	 *
	 * @see \ManiaControl\Plugins\Plugin::getDescription()
	 */
	public static function getDescription() {
		return 'Plugin offering tracking of local records and manialinks to display them.';
	}

	/**
	 * Handle ManiaControl init
	 *
	 * @param array $callback
	 */
	public function handleOnInit(array $callback) {
		$this->updateManialink = true;
	}

	/**
	 * Handle 1Second callback
	 *
	 * @param $time
	 */
	public function handle1Second($time) {
		if (!$this->updateManialink) return;
		$this->updateManialink = false;
		$manialink = $this->buildManialink();
		$this->sendManialink($manialink);
	}

	/**
	 * Handle PlayerConnect callback
	 *
	 * @param array $callback
	 */
	public function handlePlayerConnect(array $callback) {
		$this->updateManialink = true;
	}

	/**
	 * Handle BeginMap callback
	 *
	 * @param array $callback
	 */
	public function handleMapBegin(array $callback) {
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
		
		$login = $data[1];
		$player = $this->maniaControl->playerManager->getPlayer($login);
		if (!$player) {
			// Invalid player
			return;
		}
		
		$time = $data[2];
		$map = $this->maniaControl->mapManager->getCurrentMap();
		
		// Check old record of the player
		$oldRecord = $this->getLocalRecord($map, $player);
		if ($oldRecord) {
			if ($oldRecord->time < $time) {
				// Not improved
				return;
			}
			if ($oldRecord->time == $time) {
				// Same time
				$message = '$<' . $player->nickname . '$> equalized her/his $<$o' . $oldRecord->rank . '.$> Local Record: ' .
						 Formatter::formatTime($oldRecord->time);
				$this->maniaControl->chat->sendInformation($message);
				return;
			}
		}
		
		// Save time
		$mysqli = $this->maniaControl->database->mysqli;
		$query = "INSERT INTO `" . self::TABLE_RECORDS . "` (
				`mapIndex`,
				`playerIndex`,
				`time`
				) VALUES (
				{$map->index},
				{$player->index},
				{$time}
				) ON DUPLICATE KEY UPDATE
				`time` = VALUES(`time`);";
		$mysqli->query($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return;
		}
		$this->updateManialink = true;
		
		// Announce record
		$newRecord = $this->getLocalRecord($map, $player);
		$notifyOnlyDriver = $this->maniaControl->settingManager->getSetting($this, self::SETTING_NOTIFY_ONLY_DRIVER);
		$notifyOnlyBestRecords = $this->maniaControl->settingManager->getSetting($this, self::SETTING_NOTIFY_BEST_RECORDS);
		if ($notifyOnlyDriver || $notifyOnlyBestRecords > 0 && $newRecord->rank > $notifyOnlyBestRecords) {
			$improvement = ((!$oldRecord || $newRecord->rank < $oldRecord->rank) ? 'gained the' : 'improved Your');
			$message = 'You ' . $improvement . ' $<$o' . $newRecord->rank . '.$> Local Record: ' .
					 Formatter::formatTime($newRecord->time);
			$this->maniaControl->chat->sendInformation($message, $player->login);
		}
		else {
			$improvement = ((!$oldRecord || $newRecord->rank < $oldRecord->rank) ? 'gained the' : 'improved the');
			$message = '$<' . $player->nickname . '$> ' . $improvement . ' $<$o' . $newRecord->rank . '.$> Local Record: ' .
					 Formatter::formatTime($newRecord->time);
			$this->maniaControl->chat->sendInformation($message);
		}
	}

	/**
	 * Send manialink to clients
	 *
	 * @param string $manialink
	 * @param string $login
	 */
	private function sendManialink($manialink, $login = null) {
		if ($login) {
			if (!$this->maniaControl->client->query('SendDisplayManialinkPageToLogin', $login, $manialink, 0, false)) {
				trigger_error("Couldn't send manialink to player '{$login}'. " . $this->maniaControl->getClientErrorText());
			}
			return;
		}
		if (!$this->maniaControl->client->query('SendDisplayManialinkPage', $manialink, 0, false)) {
			trigger_error("Couldn't send manialink to players. " . $this->maniaControl->getClientErrorText());
		}
	}

	/**
	 * Handle ClientUpdated callback
	 *
	 * @param array $callback
	 */
	public function handleClientUpdated(array $callback) {
		$this->updateManialink = true;
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
		
		$title = $this->maniaControl->settingManager->getSetting($this, self::SETTING_WIDGET_TITLE);
		$pos_x = $this->maniaControl->settingManager->getSetting($this, self::SETTING_WIDGET_POSX);
		$pos_y = $this->maniaControl->settingManager->getSetting($this, self::SETTING_WIDGET_POSY);
		$width = $this->maniaControl->settingManager->getSetting($this, self::SETTING_WIDGET_WIDTH);
		$lines = $this->maniaControl->settingManager->getSetting($this, self::SETTING_WIDGET_LINESCOUNT);
		$lineHeight = $this->maniaControl->settingManager->getSetting($this, self::SETTING_WIDGET_LINEHEIGHT);
		$labelStyle = $this->maniaControl->manialinkManager->styleManager->getDefaultLabelStyle();
		$quadStyle = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadStyle();
		$quadSubstyle = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadSubstyle();
		
		$records = $this->getLocalRecords($map);
		if (!is_array($records)) {
			trigger_error("Couldn't fetch player records.");
			return null;
		}
		
		$manialink = new ManiaLink(self::MLID_RECORDS);
		$frame = new Frame();
		$manialink->add($frame);
		$frame->setPosition($pos_x, $pos_y);
		
		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setVAlign(Control::TOP);
		$backgroundQuad->setSize($width * 1.05, 7. + $lines * $lineHeight);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);
		
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
			$y = -8. - $index * $lineHeight;
			
			$recordFrame = new Frame();
			$frame->add($recordFrame);
			$recordFrame->setPosition(0, $y);
			
			$backgroundQuad = new Quad();
			$recordFrame->add($backgroundQuad);
			$backgroundQuad->setSize($width * 1.03, $lineHeight * 1.32);
			$backgroundQuad->setStyles($quadStyle, $quadSubstyle);
			
			$rankLabel = new Label();
			$recordFrame->add($rankLabel);
			$rankLabel->setHAlign(Control::LEFT);
			$rankLabel->setX($width * -0.47);
			$rankLabel->setSize($width * 0.06, $lineHeight);
			$rankLabel->setTextSize(1);
			$rankLabel->setTextPrefix('$o');
			$rankLabel->setText($record->rank);
			
			$nameLabel = new Label();
			$recordFrame->add($nameLabel);
			$nameLabel->setHAlign(Control::LEFT);
			$nameLabel->setX($width * -0.4);
			$nameLabel->setSize($width * 0.6, $lineHeight);
			$nameLabel->setTextSize(1);
			$nameLabel->setText($record->nickname);
			
			$timeLabel = new Label();
			$recordFrame->add($timeLabel);
			$timeLabel->setHAlign(Control::RIGHT);
			$timeLabel->setX($width * 0.47);
			$timeLabel->setSize($width * 0.25, $lineHeight);
			$timeLabel->setTextSize(1);
			$timeLabel->setText(Formatter::formatTime($record->time));
		}
		
		return $manialink->render()->saveXML();
	}

	/**
	 * Fetch local records for the given map
	 *
	 * @param Map $map
	 * @param int $limit
	 * @return array
	 */
	private function getLocalRecords(Map $map, $limit = -1) {
		$mysqli = $this->maniaControl->database->mysqli;
		$limit = ($limit > 0 ? "LIMIT " . $limit : "");
		$query = "SELECT * FROM (
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
	 * Retrieve the local record for the given map and login
	 *
	 * @param Map $map
	 * @param Player $player
	 * @return mixed
	 */
	private function getLocalRecord(Map $map, Player $player) {
		$mysqli = $this->maniaControl->database->mysqli;
		$query = "SELECT records.* FROM (
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

