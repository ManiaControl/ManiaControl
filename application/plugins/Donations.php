<?php
use FML\Controls\Control;
use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Controls\Labels\Label_Button;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_Icons128x128_1;
use FML\ManiaLink;
use FML\Script\Script;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Bills\BillManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Commands\CommandListener;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;
use ManiaControl\Plugins\Plugin;

/**
 * Donation plugin
 *
 * @author steeffeen & kremsy
 */
class DonationPlugin implements CallbackListener, CommandListener, Plugin {
	/**
	 * Constants
	 */
	const ID                              = 3;
	const VERSION                         = 0.1;
	const SETTING_ANNOUNCE_SERVERDONATION = 'Enable Server-Donation Announcements';
	const STAT_PLAYER_DONATIONS           = 'Donated Planets';
	const ACTION_DONATE_VALUE             = 'Donate.DonateValue';

	// DonateWidget Properties
	const MLID_DONATE_WIDGET              = 'DonationPlugin.DonateWidget';
	const SETTING_DONATE_WIDGET_ACTIVATED = 'Donate-Widget Activated';
	const SETTING_DONATE_WIDGET_POSX      = 'Donate-Widget-Position: X';
	const SETTING_DONATE_WIDGET_POSY      = 'Donate-Widget-Position: Y';
	const SETTING_DONATE_WIDGET_WIDTH     = 'Donate-Widget-Size: Width';
	const SETTING_DONATE_WIDGET_HEIGHT    = 'Donate-Widget-Size: Height';
	const SETTING_DONATION_VALUES         = 'Donation Values';
	const SETTING_MIN_AMOUNT_SHOWN        = 'Minimum Donation amount to get shown';

	/**
	 * Private properties
	 */
	/**
	 * @var maniaControl $maniaControl
	 */
	private $maniaControl = null;

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

		// Register for commands
		$this->maniaControl->commandManager->registerCommandListener('donate', $this, 'command_Donate');
		$this->maniaControl->commandManager->registerCommandListener('pay', $this, 'command_Pay', true);
		$this->maniaControl->commandManager->registerCommandListener('planets', $this, 'command_GetPlanets', true);

		// Register for callbacks
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERCONNECT, $this, 'handlePlayerConnect');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 'handleManialinkPageAnswer');

		// Define player stats
		$this->maniaControl->statisticManager->defineStatMetaData(self::STAT_PLAYER_DONATIONS);

		$this->maniaControl->settingManager->initSetting($this, self::SETTING_DONATE_WIDGET_ACTIVATED, true);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_DONATE_WIDGET_POSX, 156.);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_DONATE_WIDGET_POSY, -31.4);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_DONATE_WIDGET_WIDTH, 6);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_DONATE_WIDGET_HEIGHT, 6);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_DONATION_VALUES, "20,50,100,500,1000,2000");
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_MIN_AMOUNT_SHOWN, 100);

		// Register Stat in Simple StatsList
		$this->maniaControl->statisticManager->simpleStatsList->registerStat(self::STAT_PLAYER_DONATIONS, 90, "DP", 15);

		$this->displayWidget();
		return true;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::unload()
	 */
	public function unload() {
		$emptyManialink = new ManiaLink(self::MLID_DONATE_WIDGET);
		$this->maniaControl->manialinkManager->sendManialink($emptyManialink);

		$this->maniaControl->callbackManager->unregisterCallbackListener($this);
		$this->maniaControl->commandManager->unregisterCommandListener($this);
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
		return 'Donations Plugin';
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
		return 'Plugin offering commands like /donate, /pay and /planets and a donation widget.';
	}

	/**
	 * Handle ManiaControl OnStartup
	 *
	 * @param array $callback
	 */
	public function displayWidget() {
		if ($this->maniaControl->settingManager->getSetting($this, self::SETTING_DONATE_WIDGET_ACTIVATED)) {
			$this->displayDonateWidget();
		}
	}

	/**
	 * Handle ManialinkPageAnswer Callback
	 *
	 * @param array $callback
	 */
	public function handleManialinkPageAnswer(array $callback) {
		$actionId    = $callback[1][2];
		$boolSetting = (strpos($actionId, self::ACTION_DONATE_VALUE) === 0);
		if (!$boolSetting) {
			return;
		}
		$login       = $callback[1][1];
		$player      = $this->maniaControl->playerManager->getPlayer($login);
		$actionArray = explode(".", $callback[1][2]);
		$this->handleDonation($player, intval($actionArray[2]));
	}

	/**
	 * Handle PlayerConnect callback
	 *
	 * @param Player $player
	 */
	public function handlePlayerConnect(Player $player) {
		// Display Map Widget
		if ($this->maniaControl->settingManager->getSetting($this, self::SETTING_DONATE_WIDGET_ACTIVATED)) {
			$this->displayDonateWidget($player->login);
		}
	}

	/**
	 * Displays the Donate Widget
	 *
	 * @param bool $login
	 */
	public function displayDonateWidget($login = false) {
		$posX              = $this->maniaControl->settingManager->getSetting($this, self::SETTING_DONATE_WIDGET_POSX);
		$posY              = $this->maniaControl->settingManager->getSetting($this, self::SETTING_DONATE_WIDGET_POSY);
		$width             = $this->maniaControl->settingManager->getSetting($this, self::SETTING_DONATE_WIDGET_WIDTH);
		$height            = $this->maniaControl->settingManager->getSetting($this, self::SETTING_DONATE_WIDGET_HEIGHT);
		$values            = $this->maniaControl->settingManager->getSetting($this, self::SETTING_DONATION_VALUES);
		$shootManiaOffset  = $this->maniaControl->manialinkManager->styleManager->getDefaultIconOffsetSM();
		$quadStyle         = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadStyle();
		$quadSubstyle      = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadSubstyle();
		$itemMarginFactorX = 1.3;
		$itemMarginFactorY = 1.2;

		// Get Title Id
		$titleId     = $this->maniaControl->server->titleId;
		$titlePrefix = strtoupper(substr($titleId, 0, 2));

		//If game is shootmania lower the icons position by 20
		if ($titlePrefix == 'SM') {
			$posY -= $shootManiaOffset;
		}

		$itemSize = $width;

		$maniaLink = new ManiaLink(self::MLID_DONATE_WIDGET);
		$script    = $maniaLink->getScript();

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
		$itemQuad = new Quad_Icons128x128_1();
		$itemQuad->setSubStyle($itemQuad::SUBSTYLE_Coppers);
		$itemQuad->setSize($itemSize, $itemSize);
		$iconFrame->add($itemQuad);

		$valueArray = explode(",", $values);

		// Values Menu
		$popoutFrame = new Frame();
		$maniaLink->add($popoutFrame);
		$popoutFrame->setPosition($posX - $itemSize * 0.5, $posY);
		$popoutFrame->setHAlign(Control::RIGHT);
		$popoutFrame->setSize(4 * $itemSize * $itemMarginFactorX, $itemSize * $itemMarginFactorY);
		$popoutFrame->setVisible(false);

		$quad = new Quad();
		$popoutFrame->add($quad);
		$quad->setHAlign(Control::RIGHT);
		$quad->setStyles($quadStyle, $quadSubstyle);
		$quad->setSize(strlen($values) * 2 + count($valueArray) * 1, $itemSize * $itemMarginFactorY);

		$popoutFrame->add($quad);

		$script->addToggle($itemQuad, $popoutFrame);

		// Description Label
		$descriptionFrame = new Frame();
		$maniaLink->add($descriptionFrame);
		$descriptionFrame->setPosition($posX - 50, $posY - 5);
		$descriptionFrame->setHAlign(Control::RIGHT);

		$descriptionLabel = new Label();
		$descriptionFrame->add($descriptionLabel);
		$descriptionLabel->setAlign(Control::LEFT, Control::TOP);
		$descriptionLabel->setSize(40, 4);
		$descriptionLabel->setTextSize(2);
		$descriptionLabel->setVisible(true);
		$descriptionLabel->setTextColor("0F0");

		// Add items
		$x = -2;
		foreach(array_reverse($valueArray) as $value) {
			$label = new Label_Button();
			$popoutFrame->add($label);
			$label->setX($x);
			$label->setHAlign(Control::RIGHT);
			$label->setText('$s$FFF' . $value . '$09FP');
			$label->setTextSize(1.2);
			$label->setAction(self::ACTION_DONATE_VALUE . "." . $value);
			$label->setStyle(Label_Text::STYLE_TextCardSmall);
			$script->addTooltip($label, $descriptionLabel, array(Script::OPTION_TOOLTIP_TEXT => "Donate " . $value . " Planets"));

			$x -= strlen($value) * 2 + 1.7;
		}

		// Send manialink
		$this->maniaControl->manialinkManager->sendManialink($maniaLink, $login);
	}

	/**
	 * Handle /donate command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 * @return bool
	 */
	public function command_Donate(array $chatCallback, Player $player) {
		$text   = $chatCallback[1][2];
		$params = explode(' ', $text);
		if (count($params) < 2) {
			$this->sendDonateUsageExample($player);
			return false;
		}
		$amount = (int)$params[1];
		if (!$amount || $amount <= 0) {
			$this->sendDonateUsageExample($player);
			return false;
		}
		if (count($params) >= 3) {
			$receiver       = $params[2];
			$receiverPlayer = $this->maniaControl->playerManager->getPlayer($receiver);
			$receiverName   = ($receiverPlayer ? $receiverPlayer->nickname : $receiver);
		} else {
			$receiver     = '';
			$receiverName = $this->maniaControl->client->getServerName();
		}

		return $this->handleDonation($player, $amount, $receiver, $receiverName);
	}

	/**
	 * Handles a Player Donate
	 *
	 * @param Player $player
	 * @param        $value
	 */
	private function handleDonation(Player $player, $amount, $receiver = '', $receiverName = false) {

		if (!$receiverName) {
			$serverName = $this->maniaControl->client->getServerName();
			$message    = 'Donate ' . $amount . ' Planets to $<' . $serverName . '$>?';
		} else {
			$message = 'Donate ' . $amount . ' Planets to $<' . $receiverName . '$>?';
		}

		//Send and Handle the Bill
		$this->maniaControl->billManager->sendBill(function ($data, $status) use (&$player, $amount, $receiver) {
			switch($status) {
				case BillManager::DONATED_TO_SERVER:
					if ($this->maniaControl->settingManager->getSetting($this, self::SETTING_ANNOUNCE_SERVERDONATION, true) && $amount >= $this->maniaControl->settingManager->getSetting($this, self::SETTING_MIN_AMOUNT_SHOWN, true)) {
						$login   = null;
						$message = '$<' . $player->nickname . '$> donated ' . $amount . ' Planets! Thanks.';
					} else {
						$message = 'Donation successful! Thanks.';
					}
					$this->maniaControl->chat->sendSuccess($message, $player->login);
					$this->maniaControl->statisticManager->insertStat(self::STAT_PLAYER_DONATIONS, $player, $this->maniaControl->server->index, $amount);
					break;
				case BillManager::DONATED_TO_RECEIVER:
					$message = "Successfully donated {$amount} to '{$receiver}'!";
					$this->maniaControl->chat->sendSuccess($message, $player->login);
					break;
				case BillManager::PLAYER_REFUSED_DONATION:
					$message = 'Transaction cancelled.';
					$this->maniaControl->chat->sendError($message, $player->login);
					break;
				case BillManager::ERROR_WHILE_TRANSACTION:
					$message = $data;
					$this->maniaControl->chat->sendError($message, $player->login);
					break;
			}
		}, $player, $amount, $message);

		return true;
	}

	/**
	 * Handle //pay command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 * @return bool
	 */
	public function command_Pay(array $chatCallback, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_SUPERADMIN)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return false;
		}
		$text   = $chatCallback[1][2];
		$params = explode(' ', $text);
		if (count($params) < 2) {
			$this->sendPayUsageExample($player);
			return false;
		}
		$amount = (int)$params[1];
		if (!$amount || $amount <= 0) {
			$this->sendPayUsageExample($player);
			return false;
		}
		if (count($params) >= 3) {
			$receiver = $params[2];
		} else {
			$receiver = $player->login;
		}
		$message = 'Payout from $<' . $this->maniaControl->client->getServerName() . '$>.';

		$this->maniaControl->billManager->sendPlanets(function ($data, $status) use (&$player, $amount, $receiver) {
			switch($status) {
				case BillManager::PAYED_FROM_SERVER:
					$message = "Successfully payed out {$amount} to '{$receiver}'!";
					$this->maniaControl->chat->sendSuccess($message, $player->login);
					break;
				case BillManager::PLAYER_REFUSED_DONATION:
					$message = 'Transaction cancelled.';
					$this->maniaControl->chat->sendError($message, $player->login);
					break;
				case BillManager::ERROR_WHILE_TRANSACTION:
					$message = $data;
					$this->maniaControl->chat->sendError($message, $player->login);
					break;
			}
		}, $receiver, $amount, $message);

		return true;
	}

	/**
	 * Handle //getplanets command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 * @return bool
	 */
	public function command_GetPlanets(array $chatCallback, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_ADMIN)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return false;
		}
		$planets = $this->maniaControl->client->getServerPlanets();
		$message = "This Server has {$planets} Planets!";
		return $this->maniaControl->chat->sendInformation($message, $player->login);
	}

	/**
	 * Send an usage example for /donate to the player
	 *
	 * @param Player $player
	 * @return boolean
	 */
	private function sendDonateUsageExample(Player $player) {
		$message = "Usage Example: '/donate 100'";
		return $this->maniaControl->chat->sendChat($message, $player->login);
	}

	/**
	 * Send an usage example for /pay to the player
	 *
	 * @param Player $player
	 * @return boolean
	 */
	private function sendPayUsageExample(Player $player) {
		$message = "Usage Example: '/pay 100 login'";
		return $this->maniaControl->chat->sendChat($message, $player->login);
	}
}
