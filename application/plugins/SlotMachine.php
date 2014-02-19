<?php

use FML\Controls\Control;
use FML\Controls\Frame;
use FML\Controls\Labels\Label_Button;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quad;
use FML\ManiaLink;
use ManiaControl\Admin\ActionsMenu;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;
use ManiaControl\Plugins\Plugin;
use Maniaplanet\DedicatedServer\Xmlrpc\Exception;

class SlotMachinePlugin implements Plugin, CallbackListener, ManialinkPageAnswerListener, TimerListener {
	/**
	 * Constants
	 */
	const PLUGIN_ID             = 50;
	const PLUGIN_VERSION        = 0.1;
	const PLUGIN_NAME           = 'SlotMachine';
	const PLUGIN_AUTHOR         = 'kremsy';
	const SLOT_MAIN_ML          = 'SlotMachine.ManiaLinkId';
	const SLOT_ICON_ML          = 'SlotMachine.IconId';
	const TABLE_SLOTMACHINE     = 'mc_slotmachine';
	const TABLE_SLOTMACHINEWINS = 'mc_slotmachinewins';
	const ACTION_PLAY           = 'SlotmachinePlugin.Play';
	const ACTION_DEPOSIT        = 'SlotmachinePlugin.Deposit';
	const ACTION_CASHOUT        = 'SlotmachinePlugin.Cashout';
	const ACTION_TOGGLE         = 'SlotmachinePlugin.Toggle';
	const COST_OF_PLAY          = 3;
	const DEPOSIT_AMOUNT        = 250;
	const STAT_SLOT_PLAY        = 'Slotmachine Plays';
	const STAT_SLOT_WON         = 'Slotmachine Won';
	const MAX_CLICKS_PER_SEC    = 5;

	//Icons
	const ICON_LINK           = 'http://pictures.esc-clan.net/mpaseco/slot/';
	const ICON_LEMON          = 'symbols/lemon.png';
	const ICON_ORANGE         = 'symbols/orange.png';
	const ICON_MELONE         = 'symbols/melone.png';
	const ICON_PLUM           = 'symbols/plum.png';
	const ICON_CHERRY         = 'symbols/cherry.png';
	const ICON_BAR            = 'symbols/bar.png';
	const ICON_BLANK          = 'symbols/blank1.png';
	const ICON_BUTTON_BLUE_50 = 'buttons/news/blue3.png';
	const ICON_BUTTON_BLUE_51 = 'buttons/gbook/blue.png';
	const ICON_BUTTON_BLUE_52 = 'buttons/download/blue.png';
	const ICON_BUTTON_TITLE   = 'buttons/title/titel4.png';

	/**
	 * Private properties
	 */
	/** @var maniaControl $maniaControl * */
	private $maniaControl = null;
	private $playerSettings = array();
	private $symbolArrayA = array();
	private $symbolArrayB = array();
	private $symbolArrayC = array();
	private $bills = array();

	/**
	 * Prepares the Plugin
	 *
	 * @param ManiaControl $maniaControl
	 * @return mixed
	 */
	public static function prepare(ManiaControl $maniaControl) {
		$maniaControl->manialinkManager->iconManager->addIcon(self::ICON_CHERRY, self::ICON_LINK);
		$maniaControl->manialinkManager->iconManager->addIcon(self::ICON_MELONE, self::ICON_LINK);
		$maniaControl->manialinkManager->iconManager->addIcon(self::ICON_ORANGE, self::ICON_LINK);
		$maniaControl->manialinkManager->iconManager->addIcon(self::ICON_PLUM, self::ICON_LINK);
		$maniaControl->manialinkManager->iconManager->addIcon(self::ICON_LEMON, self::ICON_LINK);
		$maniaControl->manialinkManager->iconManager->addIcon(self::ICON_BAR, self::ICON_LINK);
		$maniaControl->manialinkManager->iconManager->addIcon(self::ICON_BLANK, self::ICON_LINK);
		$maniaControl->manialinkManager->iconManager->addIcon(self::ICON_BUTTON_BLUE_50, self::ICON_LINK);
		$maniaControl->manialinkManager->iconManager->addIcon(self::ICON_BUTTON_BLUE_51, self::ICON_LINK);
		$maniaControl->manialinkManager->iconManager->addIcon(self::ICON_BUTTON_BLUE_52, self::ICON_LINK);
		$maniaControl->manialinkManager->iconManager->addIcon(self::ICON_BUTTON_TITLE, self::ICON_LINK);
	}

	/**
	 * Load the plugin
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 * @return bool
	 */
	public function load(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		$this->maniaControl->timerManager->registerTimerListening($this, 'onEverySecond', 1000);
		$this->maniaControl->timerManager->registerTimerListening($this, 'updateDatabaseEveryMinute', 1000 * 60);

		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_BILLUPDATED, $this, 'handleBillUpdated');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERCONNECT, $this, 'handlePlayerConnect');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERDISCONNECT, $this, 'handlePlayerDisconnect');

		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_PLAY, $this, 'actionPlay');
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_DEPOSIT, $this, 'actionDeposit');
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_CASHOUT, $this, 'actionCashout');
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_TOGGLE, $this, 'actionToggle');

		// Define player stats
		$this->maniaControl->statisticManager->defineStatMetaData(self::STAT_SLOT_PLAY);
		$this->maniaControl->statisticManager->defineStatMetaData(self::STAT_SLOT_WON);

		$this->initializeArray();

		$this->checkDatabase();
		$this->displayIcon();

		foreach($this->maniaControl->playerManager->getPlayers() as $player) {
			$this->playerSettings[$player->index] = array("Balance" => $this->getBalance($player->index), "Won" => 0, "Spent" => 0, "Plays" => 0, "ClicksLastSecond" => 0, "Visible" => 0);
		}
	}

	public function actionToggle(array $chatCallback, Player $player) {
		$pic1 = $this->maniaControl->manialinkManager->iconManager->getIcon(self::ICON_BAR);
		$pic2 = $this->maniaControl->manialinkManager->iconManager->getIcon(self::ICON_MELONE);
		$pic3 = $this->maniaControl->manialinkManager->iconManager->getIcon(self::ICON_PLUM);

		if (isset($this->playerSettings[$player->index]) && $this->playerSettings[$player->index]['Visible'] == 1) {
			$this->playerSettings[$player->index]["Visible"] = 0;
			$this->maniaControl->manialinkManager->closeWidget($player, self::SLOT_MAIN_ML);
		} else {
			if (!isset($this->playerSettings[$player->index])) {
				$this->playerSettings[$player->index] = array("Balance" => $this->getBalance($player->index), "Won" => 0, "Spent" => 0, "Plays" => 0, "ClicksLastSecond" => 0, "Visible" => 1);
			} else {
				$this->playerSettings[$player->index]["Visible"] = 1;
			}
			$this->insertPlayerIntoDatabase($player->index);
			$this->showSlotMachine($player, $pic1, $pic2, $pic3);
		}
	}

	/**
	 *Handle on Every Second
	 */
	public function onEverySecond() {
		if (isset($this->playerSettings)) {
			foreach($this->playerSettings as $key => $player) {
				$this->playerSettings[$key]["ClicksLastSecond"] = 0; //Set Klick count to 0 on every player
			}
		}
	}

	/**
	 * Handle Player connect
	 *
	 * @param Player $player
	 */
	public function handlePlayerConnect(Player $player) {
		$this->displayIcon($player->login);
		//Initialize Player
		$this->playerSettings[$player->index] = array("Balance" => $this->getBalance($player->index), "Won" => 0, "Spent" => 0, "Plays" => 0, "ClicksLastSecond" => 0, "Visible" => 0);
	}

	/**
	 * Handle Player disconnect
	 *
	 * @param Player $player
	 */
	public function handlePlayerDisconnect(Player $player) {
		$this->updateDatabaseEveryMinute();
		unset($this->playerSettings[$player->index]);
	}


	public function actionPlay(array $chatCallback, Player $player) {
		if ($this->playerSettings[$player->index]["Balance"] >= self::COST_OF_PLAY) {
			if ($this->playerSettings[$player->index]["ClicksLastSecond"] > self::MAX_CLICKS_PER_SEC) {
				return;
			}

			$randA = rand(0, 63);
			$randB = rand(0, 63);
			$randC = rand(0, 63);

			$symbolA = $this->getSymbolNumber(1, $randA);
			$symbolB = $this->getSymbolNumber(2, $randB);
			$symbolC = $this->getSymbolNumber(3, $randC);

			$picA = $this->getIconLink($symbolA);
			$picB = $this->getIconLink($symbolB);
			$picC = $this->getIconLink($symbolC);

			$won = $this->checkWin($symbolA, $symbolB, $symbolC);

			$this->playerSettings[$player->index]["Balance"] += $won - self::COST_OF_PLAY;
			$this->playerSettings[$player->index]["Won"] += $won;
			$this->playerSettings[$player->index]["Spent"] += self::COST_OF_PLAY;
			$this->playerSettings[$player->index]["Plays"]++;
			$this->playerSettings[$player->index]["ClicksLastSecond"]++;

			$this->showSlotMachine($player, $picA, $picB, $picC, $won);

			if ($won >= self::COST_OF_PLAY * 20) {
				$mysqli = $this->maniaControl->database->mysqli;
				$query  = 'INSERT INTO ' . self::TABLE_SLOTMACHINEWINS . '
             		 (PlayerIndex,Won) VALUES(' . $player->index . ',' . $won . ')';
				$mysqli->query($query);

				$message = '$FF0Player $FFF$<' . $player->nickname . '$> $FF0just won $FFF' . $won . ' $FF0Planets in the slotmachine!';
				$this->maniaControl->chat->sendChat($message);
			}
		} else {
			$arr = array();
			$this->actionDeposit($arr, $player);
		}

		$this->updateDatabaseEveryMinute();
	}

	/**
	 * Play the slotmachine
	 *
	 * @param Player $player
	 * @param        $pic1
	 * @param        $pic2
	 * @param        $pic3
	 * @param int    $lastWin
	 */
	private function showSlotMachine(Player $player, $pic1, $pic2, $pic3, $lastWin = 0, $showSelf = true) {

		/*$pos_x        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MAP_WIDGET_POSX);
		$pos_y        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MAP_WIDGET_POSY);
		$width        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MAP_WIDGET_WIDTH);
		$height       = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MAP_WIDGET_HEIGHT);*/
		$posX   = 160 - 16;
		$posY   = 3.6;
		$width  = 32;
		$height = 50;

		$quadStyle    = $this->maniaControl->manialinkManager->styleManager->getDefaultMainWindowStyle();
		$quadSubstyle = $this->maniaControl->manialinkManager->styleManager->getDefaultMainWindowSubStyle();


		//$script    = new Script();
		//$maniaLink->setScript($script);

		// mainframe
		$frame = new Frame();

		$frame->setSize($width, $height);
		$frame->setPosition($posX, $posY);

		if ($showSelf) {
			$maniaLink = new ManiaLink(self::SLOT_MAIN_ML);
			$maniaLink->add($frame);
		}

		// Background Quad
		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setSize($width, $height);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);

		$headBgQuad = new Quad();
		$headBgQuad->setY($height / 2 - 2);
		$frame->add($headBgQuad);
		$headBgQuad->setSize($width, 4);
		$headBgQuad->setStyles($quadStyle, $quadSubstyle);

		$headQuad = new Quad();
		$headQuad->setY($height / 2 - 2);
		$headQuad->setZ(-0.3);
		$frame->add($headQuad);
		$headQuad->setImage($this->maniaControl->manialinkManager->iconManager->getIcon(self::ICON_BUTTON_TITLE));
		$headQuad->setSize($width, 4);

		//Balance Label
		$balance = $this->playerSettings[$player->index]["Balance"];
		$label   = new Label_Text();
		$frame->add($label);
		$label->setText("Balance: " . $balance);
		$label->setTextColor("FFF");
		$label->setX(-$width / 2 + 3);
		$label->setY($height / 2 - 7);
		$label->setHAlign(Control::LEFT);
		$label->setTextSize(1.1);

		//Icons
		$quad = new Quad();
		$frame->add($quad);
		$quad->setPosition(-$width / 2 + 6, $height / 2 - 15, 2);
		$quad->setSize(12, 10);
		$quad->setImage($pic1);

		$quad = clone $quad;
		$frame->add($quad);
		$quad->setX(0);
		$quad->setImage($pic2);

		$quad = clone $quad;
		$frame->add($quad);
		$quad->setX($width / 2 - 6);
		$quad->setImage($pic3);

		//Won Label
		if ($lastWin > 0) {
			$label = new Label_Text();
			$frame->add($label);
			$label->setSize(20, 3);
			$label->setTextSize(1);
			$label->setPosition(-$width / 2 + 3, $height / 2 - 22, 2);
			$label->setText("Won Planets: " . $lastWin);
			$label->setTextColor("FFF");
			$label->setHAlign(Control::LEFT);
		}

		//Buttons
		$quad = new Quad();
		$frame->add($quad);
		$quad->setY($height / 2 - 28);
		$quad->setZ(-0.3);
		$quad->setImage($this->maniaControl->manialinkManager->iconManager->getIcon(self::ICON_BUTTON_BLUE_50));
		$quad->setSize($width - 5, 7);
		$quad->setAction(self::ACTION_PLAY);

		$quad = clone $quad;
		$frame->add($quad);
		$quad->setY($height / 2 - 36);
		$quad->setImage($this->maniaControl->manialinkManager->iconManager->getIcon(self::ICON_BUTTON_BLUE_51));
		$quad->setAction(self::ACTION_DEPOSIT);

		$quad = clone $quad;
		$frame->add($quad);
		$quad->setY($height / 2 - 44);
		$quad->setImage($this->maniaControl->manialinkManager->iconManager->getIcon(self::ICON_BUTTON_BLUE_52));
		$quad->setAction(self::ACTION_CASHOUT);

		$label = new Label_Button();
		$frame->add($label);
		$label->setStyle("TextButtonBig");
		$label->setText("$0f9Play");
		$label->setY($height / 2 - 29);
		$label->setZ(-0.3);
		$label->setSize($width - 5, 7);
		$label->setAction(self::ACTION_PLAY);
		$label->setTextSize(1);

		$label = clone $label;
		$frame->add($label);
		$label->setY($height / 2 - 37);
		$label->setText("$0f9Deposit");
		$label->setAction(self::ACTION_DEPOSIT);

		$label = clone $label;
		$frame->add($label);
		$label->setY($height / 2 - 44.4);
		$label->setText("$0F9Cash Out");
		$label->setAction(self::ACTION_CASHOUT);

		// Send manialink
		if ($showSelf) {
			$manialinkText = $maniaLink->render()->saveXML();
			$this->maniaControl->manialinkManager->sendManialink($manialinkText, $player->login);
		} else {
			return $frame;
		}
	}

	/**
	 * Displays the Icon
	 *
	 * @param bool $login
	 */
	public function displayIcon($login = false) {
		$posX              = $this->maniaControl->settingManager->getSetting($this->maniaControl->actionsMenu, ActionsMenu::SETTING_MENU_POSX);
		$posY              = $this->maniaControl->settingManager->getSetting($this->maniaControl->actionsMenu, ActionsMenu::SETTING_MENU_POSY);
		$width             = $this->maniaControl->settingManager->getSetting($this->maniaControl->actionsMenu, ActionsMenu::SETTING_MENU_ITEMSIZE);
		$height            = $this->maniaControl->settingManager->getSetting($this->maniaControl->actionsMenu, ActionsMenu::SETTING_MENU_ITEMSIZE);
		$shootManiaOffset  = $this->maniaControl->manialinkManager->styleManager->getDefaultIconOffsetSM();
		$quadStyle         = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadStyle();
		$quadSubstyle      = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadSubstyle();
		$itemMarginFactorX = 1.3;
		$itemMarginFactorY = 1.2;
		$posY += $width * $itemMarginFactorY;

		// Get Title Id
		$titleId     = $this->maniaControl->server->titleId;
		$titlePrefix = strtoupper(substr($titleId, 0, 2));

		//If game is shootmania lower the icons position by 20
		if ($titlePrefix == 'SM') {
			$posY -= $shootManiaOffset;
		}

		$itemSize = $width;

		$maniaLink = new ManiaLink(self::SLOT_ICON_ML);

		// Donate Menu Icon Frame
		$frame = new Frame();
		$maniaLink->add($frame);
		$frame->setPosition($posX, $posY);

		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setSize($width * $itemMarginFactorX, $height * $itemMarginFactorY);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);

		$iconFrame = new Frame();
		$frame->add($iconFrame);

		$iconFrame->setSize($itemSize, $itemSize);
		$itemQuad = new Quad();
		$itemQuad->setImage($this->maniaControl->manialinkManager->iconManager->getIcon(self::ICON_CHERRY));
		$itemQuad->setSize($itemSize, $itemSize);
		$iconFrame->add($itemQuad);

		$itemQuad->setAction(self::ACTION_TOGGLE);


		// Send manialink
		$manialinkText = $maniaLink->render()->saveXML();
		$this->maniaControl->manialinkManager->sendManialink($manialinkText, $login);
	}

	/**
	 * Handles a deposit
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function actionDeposit(array $chatCallback, Player $player) {
		$this->playerSettings[$player->index]["ClicksLastSecond"]++;
		if ($this->playerSettings[$player->index]["ClicksLastSecond"] > 1) {
			return;
		}

		$planets = self::DEPOSIT_AMOUNT;
		$message = '$F0FDeposit $FFF' . $planets . ' Planets $F0Fin the slot-machine.';

		$billId = $this->maniaControl->client->sendBill($player->login, (int)$planets, $message, '');

		$this->bills[$billId] = array($player, $planets);
	}

	/**
	 * Handles a deposit
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function actionCashout(array $chatCallback, Player $player) {
		$this->playerSettings[$player->index]["ClicksLastSecond"]++;
		if ($this->playerSettings[$player->index]["ClicksLastSecond"] > 1) {
			return;
		}

		$balance = $this->playerSettings[$player->index]["Balance"];

		if ($balance == 0) {
			return;
		}

		$message = '$09c' . $balance . '$zPlanets have been re-credited to your ManiaPlanet account! Thank you for playing Slots! Your $i$s$08fparagon$n$000$m.$fffTeam';

		try {
			$billId = $this->maniaControl->client->pay($player->login, (int)$balance, $message);
		} catch(Exception $e) {
			// TODO: handle errors like 'too few server planets' - throw other like connection errors
			return;
		}


		$this->bills[$billId] = array($player, -$balance);
	}


	/**
	 * Update the database player values every minute
	 *
	 * @return bool
	 */
	public function updateDatabaseEveryMinute() {
		$mysqli = $this->maniaControl->database->mysqli;

		// Save map dataS
		$query     = "UPDATE `" . self::TABLE_SLOTMACHINE . "`
				SET `Balance` = ?, `Spent` = `Spent` + ?, `Won` = `Won` + ?, `Plays` = `Plays` + ?
				WHERE `PlayerIndex` = ?;";
		$statement = $mysqli->prepare($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}

		foreach($this->playerSettings as $index => $player) {
			$statement->bind_param('ssssi', $player["Balance"], $player["Spent"], $player["Won"], $player["Plays"], $index);
			$statement->execute();
			if ($statement->error) {
				trigger_error($statement->error);
			}

			$slotPlayer = $this->maniaControl->playerManager->getPlayerByIndex($index);
			$this->maniaControl->statisticManager->insertStat(self::STAT_SLOT_PLAY, $slotPlayer, $this->maniaControl->server->index, $player["Plays"]);
			$this->maniaControl->statisticManager->insertStat(self::STAT_SLOT_WON, $slotPlayer, $this->maniaControl->server->index, $player["Won"]);

			$this->playerSettings[$index]["Spent"] = 0;
			$this->playerSettings[$index]["Won"]   = 0;
			$this->playerSettings[$index]["Plays"] = 0;
		}


		$statement->close();

		return true;
	}

	/**
	 * Handle The bills
	 *
	 * @param $billCallback
	 */
	public function handleBillUpdated($billCallback) {
		$bill   = $billCallback[1];
		$billid = $bill[0];


		// check for known bill ID
		if (array_key_exists($billid, $this->bills)) {
			// get bill info
			$player  = $this->bills[$billid][0];
			$planets = $this->bills[$billid][1];

			$picA = $this->maniaControl->manialinkManager->iconManager->getIcon(self::ICON_BAR);
			$picB = $this->maniaControl->manialinkManager->iconManager->getIcon(self::ICON_MELONE);
			$picC = $this->maniaControl->manialinkManager->iconManager->getIcon(self::ICON_PLUM);

			// check bill state
			switch($bill[1]) {
				case 4: // Payed (Paid)
					if ($planets > 0) { //Deposit  pos Planets Value
						/* DEPOSIT BALANCE */

						$this->playerSettings[$player->index]["Balance"] += $planets;

						$message = '$FF0You successfully deposited $FFF' . $planets . ' $FF0planets in your slots account! Have fun!';
						$this->maniaControl->chat->sendChat($message, $player->login);

						$this->showSlotMachine($player, $picA, $picB, $picC);
						$this->updateDatabaseEveryMinute();
					} else { //Withdrawl  neg Planets Value

						$this->playerSettings[$player->index]["Balance"] = 0;

						$message = '$FFF' . abs($planets) . ' $FF0Planets have been re-credited to your Maniaplanet account!';
						$this->maniaControl->chat->sendChat($message, $player->login);

						$this->updateDatabaseEveryMinute();

						$this->showSlotMachine($player, $picA, $picB, $picC);
					}
					unset($this->bills[$billid]);
					break;
				case 5: // Refused
					$message = '$FF0> $f00$iTransaction refused!';
					$this->maniaControl->chat->sendChat($message, $player->login);
					unset($this->bills[$billid]);
					break;
				case 6: // Error
					$message = '$FF0> $f00$iTransaction failed: $FFF$i ' . $bill[2];
					$this->maniaControl->chat->sendChat($message, $player->login);
					unset($this->bills[$billid]);
					break;
				default: // CreatingTransaction/Issued/ValidatingPay(e)ment
					break;
			}

		}
	}


	/**
	 * Gets the Current Slotmachine Balance of a player
	 *
	 * @param $playerIndex
	 * @return int
	 */
	private function getBalance($playerIndex) {
		$mysqli = $this->maniaControl->database->mysqli;

		/* Get Player Balance */
		$query = 'SELECT Balance FROM ' . self::TABLE_SLOTMACHINE . ' WHERE PlayerIndex=' . $playerIndex;
		$res   = $mysqli->query($query);

		if ($mysqli->affected_rows > 0) {
			$row     = $res->fetch_object();
			$balance = $row->Balance;
		} else {
			return 0;
		}
		$res->free_result();

		return $balance;
	}

	/**
	 * Inserts a Player into the database if he is not existing
	 *
	 * @param $playerIndex
	 */
	private function insertPlayerIntoDatabase($playerIndex) {
		$mysqli = $this->maniaControl->database->mysqli;
		$query  = 'INSERT INTO ' . self::TABLE_SLOTMACHINE . ' (PlayerIndex) VALUES (' . $playerIndex . ')';
		$mysqli->query($query);
	}

	/**
	 * Creates the Database tables
	 */
	private function checkDatabase() {
		$mysqli = $this->maniaControl->database->mysqli;

		$query = "CREATE TABLE IF NOT EXISTS `" . self::TABLE_SLOTMACHINE . "` (
		          `PlayerIndex` MEDIUMINT(9) NOT NULL DEFAULT 0,
              		`Balance`  MEDIUMINT(9) NOT NULL DEFAULT 0,
              		`Spent`  MEDIUMINT(9) NOT NULL DEFAULT 0,
              		`Won`  MEDIUMINT(9) NOT NULL DEFAULT 0,
              		`Plays`  MEDIUMINT(9) NOT NULL DEFAULT 0,
		           UNIQUE KEY `PlayerIndex` (`PlayerIndex`)
		         ) ENGINE=MyISAM";
		$mysqli->query($query);

		$query = "CREATE TABLE IF NOT EXISTS `" . self::TABLE_SLOTMACHINEWINS . "` (
		          `PlayerIndex` mediumint(9) NOT NULL default 0,
              `Won`  mediumint(9) NOT NULL default 0,
              `time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
		           KEY `PlayerIndex` (`PlayerIndex`)
		         ) ENGINE=MyISAM";
		$mysqli->query($query);
	}

	/**
	 * Gets the SymbolNumber
	 *
	 * @param $arr
	 * @param $rand
	 * @return null
	 */
	private function getSymbolNumber($arr, $rand) {
		switch($arr) {
			case 1:
				return $this->symbolArrayA[$rand];
			case 2:
				return $this->symbolArrayB[$rand];
			case 3:
				return $this->symbolArrayC[$rand];
			default:
				return null;
		}
	}

	/**
	 * Initializes the Symbol Arrays
	 */
	private function initializeArray() {
		$this->symbolArrayA = array(0, 0, 0, 0, //4
			1, 1, 1, 1, 1, //5
			2, 2, 2, 2, 2, 2, //6
			3, 3, 3, 3, 3, 3, //6
			4, 4, 4, 4, 4, 4, 4, //7
			5, 5, 5, 5, 5, 5, 5, 5, //8
			6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6 //28
		);
		$this->symbolArrayB = array( //0, 0, 0, //3
			0, 0, 1, 1, 1, 1, //4
			2, 2, 2, 2, //4
			3, 3, 3, 3, 3, //5
			4, 4, 4, 4, 4, //5
			5, 5, 5, 5, 5, 5, //6
			//6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6 //37
			6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6);
		$this->symbolArrayC = array(0, //1
			1, 1, //2
			2, 2, 2, //3
			3, 3, 3, 3, //4
			4, 4, 4, 4, 4, 4, //6
			5, 5, 5, 5, 5, 5, //6
			6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6 //42
		);
	}

	/**
	 * Gets the Icon link
	 *
	 * @param $symbolNumber
	 * @return string
	 */
	private function getIconLink($symbolNumber) {
		switch($symbolNumber) {
			case 0:
				$iconName = self::ICON_BAR;
				break;
			case 1:
				$iconName = self::ICON_CHERRY;
				break;
			case 2:
				$iconName = self::ICON_PLUM;
				break;
			case 3:
				$iconName = self::ICON_MELONE;
				break;
			case 4:
				$iconName = self::ICON_ORANGE;
				break;
			case 5:
				$iconName = self::ICON_LEMON;
				break;
			default:
				$iconName = self::ICON_BLANK;
		}
		return $this->maniaControl->manialinkManager->iconManager->getIcon($iconName);
	}

	/**
	 * Checks for a win
	 *
	 * @param     $symbol1
	 * @param     $symbol2
	 * @param     $symbol3
	 * @param int $buyin
	 * @return int
	 */
	private function checkWin($symbol1, $symbol2, $symbol3, $buyin = 3) {
		$prize = 0;

		if ($symbol1 == 5 && $symbol2 == 5 && $symbol3 == 5) {
			$prize = $buyin * 25;
		} else if ($symbol1 == 4 && $symbol2 == 4 && $symbol3 == 4) {
			$prize = $buyin * 50;
		} else if ($symbol1 == 3 && $symbol2 == 3 && $symbol3 == 3) {
			$prize = $buyin * 100;
		} else if ($symbol1 == 2 && $symbol2 == 2 && $symbol3 == 2) {
			$prize = $buyin * 200;
		} else if ($symbol1 == 1 && $symbol2 == 1 && $symbol3 == 1) {
			$prize = $buyin * 1000;
		} else if ($symbol1 == 0 && $symbol2 == 0 && $symbol3 == 0) {
			$prize = $buyin * 5000;
		} else if ($symbol1 == 1 && $symbol2 == 1 || $symbol1 == 1 && $symbol3 == 1 || $symbol2 == 1 && $symbol3 == 1) {
			$prize = $buyin * 10;
		} else if ($symbol1 == 1 || $symbol2 == 1 || $symbol3 == 1) {
			$prize = $buyin * 2;
		}
		return $prize;
	}

	/**
	 * Unload the plugin and its resources
	 */
	public function unload() {
		$emptyManialink = new ManiaLink(self::SLOT_MAIN_ML);
		$manialinkText  = $emptyManialink->render()->saveXML();
		$this->maniaControl->manialinkManager->sendManialink($manialinkText);

		$emptyManialink = new ManiaLink(self::SLOT_ICON_ML);
		$manialinkText  = $emptyManialink->render()->saveXML();
		$this->maniaControl->manialinkManager->sendManialink($manialinkText);

		$this->maniaControl->callbackManager->unregisterCallbackListener($this);
		$this->maniaControl->manialinkManager->unregisterManialinkPageAnswerListener($this);
		$this->maniaControl->timerManager->unregisterTimerListenings($this);
		unset($this->maniaControl);
	}

	/**
	 * Get plugin id
	 *
	 * @return int
	 */
	public static function getId() {
		return self::PLUGIN_ID;
	}

	/**
	 * Get Plugin Name
	 *
	 * @return string
	 */
	public static function getName() {
		return self::PLUGIN_NAME;
	}

	/**
	 * Get Plugin Version
	 *
	 * @return float,,
	 */
	public static function getVersion() {
		return self::PLUGIN_VERSION;
	}

	/**
	 * Get Plugin Author
	 *
	 * @return string
	 */
	public static function getAuthor() {
		return self::PLUGIN_AUTHOR;
	}

	/**
	 * Get Plugin Description
	 *
	 * @return string
	 */
	public static function getDescription() {
		return null;
	}
}