<?php

namespace MCTeam;

use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_BgRaceScore2;
use FML\Controls\Quads\Quad_BgsPlayerCard;
use FML\ManiaLink;
use FML\Script\Features\Paging;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Bills\BillManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Commands\CommandListener;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;
use ManiaControl\Plugins\Plugin;

/**
 * ManiaControl Donation Plugin
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class DonationPlugin implements CallbackListener, CommandListener, Plugin {
	/*
	 * Constants
	 */
	const ID                               = 3;
	const VERSION                          = 0.1;
	const AUTHOR                           = 'MCTeam';
	const NAME                             = 'Donation Plugin';
	const SETTING_ANNOUNCE_SERVER_DONATION = 'Enable Server-Donation Announcements';
	const STAT_PLAYER_DONATIONS            = 'Donated Planets';
	const ACTION_DONATE_VALUE              = 'Donate.DonateValue';

	// DonateWidget Properties
	const MLID_DONATE_WIDGET              = 'DonationPlugin.DonateWidget';
	const SETTING_DONATE_WIDGET_ACTIVATED = 'Donate-Widget Activated';
	const SETTING_DONATE_WIDGET_POSX      = 'Donate-Widget-Position: X';
	const SETTING_DONATE_WIDGET_POSY      = 'Donate-Widget-Position: Y';
	const SETTING_DONATE_WIDGET_WIDTH     = 'Donate-Widget-Size: Width';
	const SETTING_DONATE_WIDGET_HEIGHT    = 'Donate-Widget-Size: Height';
	const SETTING_DONATION_VALUES         = 'Donation Values';
	const SETTING_MIN_AMOUNT_SHOWN        = 'Minimum Donation amount to get shown';

	/*
	 * Private Properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;

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
		return 'Plugin offering Commands like /donate, /pay and /planets and a Donation Widget.';
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::load()
	 */
	public function load(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Register for commands
		$this->maniaControl->commandManager->registerCommandListener('donate', $this, 'command_Donate', false, 'Donate some planets to the server.');
		$this->maniaControl->commandManager->registerCommandListener('pay', $this, 'command_Pay', true, 'Pays planets from the server to a player.');
		$this->maniaControl->commandManager->registerCommandListener(array('getplanets', 'planets'), $this, 'command_GetPlanets', true, 'Checks the planets-balance of the server.');
		$this->maniaControl->commandManager->registerCommandListener('topdons', $this, 'command_TopDons', false, 'Provides an overview of who donated the most planets.');

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
	 * Display the widget
	 */
	public function displayWidget() {
		if ($this->maniaControl->settingManager->getSettingValue($this, self::SETTING_DONATE_WIDGET_ACTIVATED)) {
			$this->displayDonateWidget();
		}
	}

	/**
	 * Display the Donation Widget
	 *
	 * @param string $login
	 */
	public function displayDonateWidget($login = null) {
		$posX              = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_DONATE_WIDGET_POSX);
		$posY              = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_DONATE_WIDGET_POSY);
		$width             = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_DONATE_WIDGET_WIDTH);
		$height            = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_DONATE_WIDGET_HEIGHT);
		$values            = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_DONATION_VALUES);
		$shootManiaOffset  = $this->maniaControl->manialinkManager->styleManager->getDefaultIconOffsetSM();
		$quadStyle         = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadStyle();
		$quadSubstyle      = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadSubstyle();
		$itemMarginFactorX = 1.3;
		$itemMarginFactorY = 1.2;

		//If game is shootmania lower the icons position by 20
		if ($this->maniaControl->mapManager->getCurrentMap()
		                                   ->getGame() === 'sm'
		) {
			$posY -= $shootManiaOffset;
		}

		$itemSize = $width;

		$maniaLink = new ManiaLink(self::MLID_DONATE_WIDGET);

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
		$itemQuad = new Quad_BgRaceScore2();
		$itemQuad->setSubStyle($itemQuad::SUBSTYLE_Points);
		$itemQuad->setSize($itemSize, $itemSize);
		$iconFrame->add($itemQuad);

		$valueArray = explode(',', $values);

		// Values Menu
		$popoutFrame = new Frame();
		$maniaLink->add($popoutFrame);
		$popoutFrame->setPosition($posX - $itemSize * 0.5, $posY);
		$popoutFrame->setHAlign($popoutFrame::RIGHT);
		$popoutFrame->setSize(4 * $itemSize * $itemMarginFactorX, $itemSize * $itemMarginFactorY);
		$popoutFrame->setVisible(false);

		$quad = new Quad();
		$popoutFrame->add($quad);
		$quad->setHAlign($quad::RIGHT);
		$quad->setStyles($quadStyle, $quadSubstyle);
		$quad->setSize(strlen($values) * 2 + count($valueArray) * 1, $itemSize * $itemMarginFactorY);

		$popoutFrame->add($quad);
		$itemQuad->addToggleFeature($popoutFrame);

		// Description Label
		$descriptionFrame = new Frame();
		$maniaLink->add($descriptionFrame);
		$descriptionFrame->setPosition($posX - 50, $posY - 5);
		$descriptionFrame->setHAlign($descriptionFrame::RIGHT);

		$descriptionLabel = new Label();
		$descriptionFrame->add($descriptionLabel);
		$descriptionLabel->setAlign($descriptionLabel::LEFT, $descriptionLabel::TOP);
		$descriptionLabel->setSize(40, 4);
		$descriptionLabel->setTextSize(2);
		$descriptionLabel->setVisible(true);
		$descriptionLabel->setTextColor('0f0');

		// Add items
		$posX = -2;
		foreach (array_reverse($valueArray) as $value) {
			$label = new Label_Text();
			$popoutFrame->add($label);
			$label->setX($posX);
			$label->setHAlign($label::RIGHT);
			$label->setText('$s$FFF' . $value . '$09FP');
			$label->setTextSize(1.2);
			$label->setAction(self::ACTION_DONATE_VALUE . "." . $value);
			$label->setStyle($label::STYLE_TextCardSmall);
			$description = "Donate {$value} Planets";
			$label->addTooltipLabelFeature($descriptionLabel, $description);

			$posX -= strlen($value) * 2 + 1.7;
		}

		// Send manialink
		$this->maniaControl->manialinkManager->sendManialink($maniaLink, $login);
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::unload()
	 */
	public function unload() {
		$this->maniaControl->manialinkManager->hideManialink(self::MLID_DONATE_WIDGET);
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
	 * Handle a Player Donation
	 *
	 * @param Player $player
	 * @param int    $amount
	 * @param string $receiver
	 * @param string $receiverName
	 */
	private function handleDonation(Player $player, $amount, $receiver = '', $receiverName = null) {
		if ($amount > 1000000) {
			// Prevent too huge donation amounts that would cause xmlrpc parsing errors
			$message = "You can only donate 1.000.000 Planets at a time!";
			$this->maniaControl->chat->sendError($message, $player);
			return;
		}

		if (!$receiverName) {
			$serverName = $this->maniaControl->client->getServerName();
			$message    = 'Donate ' . $amount . ' Planets to $<' . $serverName . '$>?';
		} else {
			$message = 'Donate ' . $amount . ' Planets to $<' . $receiverName . '$>?';
		}

		//Send and Handle the Bill
		$this->maniaControl->billManager->sendBill(function ($data, $status) use (&$player, $amount, $receiver) {
			switch ($status) {
				case BillManager::DONATED_TO_SERVER:
					if ($this->maniaControl->settingManager->getSettingValue($this, DonationPlugin::SETTING_ANNOUNCE_SERVER_DONATION, true) && $amount >= $this->maniaControl->settingManager->getSettingValue($this, DonationPlugin::SETTING_MIN_AMOUNT_SHOWN, true)) {
						$login   = null;
						$message = '$<' . $player->nickname . '$> donated ' . $amount . ' Planets! Thanks.';
					} else {
						$login   = $player->login;
						$message = 'Donation successful! Thanks.';
					}
					$this->maniaControl->chat->sendSuccess($message, $login);
					$this->maniaControl->statisticManager->insertStat(DonationPlugin::STAT_PLAYER_DONATIONS, $player, $this->maniaControl->server->index, $amount);
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
	}

	/**
	 * Handle PlayerConnect callback
	 *
	 * @param Player $player
	 */
	public function handlePlayerConnect(Player $player) {
		// Display Map Widget
		if ($this->maniaControl->settingManager->getSettingValue($this, self::SETTING_DONATE_WIDGET_ACTIVATED)) {
			$this->displayDonateWidget($player->login);
		}
	}

	/**
	 * Handle /donate command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_Donate(array $chatCallback, Player $player) {
		$text   = $chatCallback[1][2];
		$params = explode(' ', $text);
		if (count($params) < 2) {
			$this->sendDonateUsageExample($player);
			return;
		}
		$amount = (int)$params[1];
		if (!$amount || $amount <= 0) {
			$this->sendDonateUsageExample($player);
			return;
		}
		if (count($params) >= 3) {
			$receiver       = $params[2];
			$receiverPlayer = $this->maniaControl->playerManager->getPlayer($receiver);
			$receiverName   = ($receiverPlayer ? $receiverPlayer->nickname : $receiver);
		} else {
			$receiver     = '';
			$receiverName = $this->maniaControl->client->getServerName();
		}

		$this->handleDonation($player, $amount, $receiver, $receiverName);
	}

	/**
	 * Send an usage example for /donate to the player
	 *
	 * @param Player $player
	 */
	private function sendDonateUsageExample(Player $player) {
		$message = "Usage Example: '/donate 100'";
		$this->maniaControl->chat->sendChat($message, $player->login);
	}

	/**
	 * Handle //pay command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_Pay(array $chatCallback, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_SUPERADMIN)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		$text   = $chatCallback[1][2];
		$params = explode(' ', $text);
		if (count($params) < 2) {
			$this->sendPayUsageExample($player);
			return;
		}
		$amount = (int)$params[1];
		if (!$amount || $amount <= 0) {
			$this->sendPayUsageExample($player);
			return;
		}
		if (count($params) >= 3) {
			$receiver = $params[2];
		} else {
			$receiver = $player->login;
		}
		$message = 'Payout from $<' . $this->maniaControl->client->getServerName() . '$>.';

		$this->maniaControl->billManager->sendPlanets(function ($data, $status) use (&$player, $amount, $receiver) {
			switch ($status) {
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
	}

	/**
	 * Send an usage example for /pay to the player
	 *
	 * @param Player $player
	 */
	private function sendPayUsageExample(Player $player) {
		$message = "Usage Example: '//pay 100 login'";
		$this->maniaControl->chat->sendChat($message, $player->login);
	}

	/**
	 * Handle //getplanets command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_GetPlanets(array $chatCallback, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_ADMIN)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		$planets = $this->maniaControl->client->getServerPlanets();
		$message = "This Server has {$planets} Planets!";
		$this->maniaControl->chat->sendInformation($message, $player->login);
	}

	/**
	 * Handles the /topdons command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_TopDons(array $chatCallback, Player $player) {
		$this->showTopDonsList($player);
	}

	/**
	 * Provide an Overview ManiaLink with Donators
	 *
	 * @param Player $player
	 */
	private function showTopDonsList(Player $player) {
		$stats = $this->maniaControl->statisticManager->getStatsRanking(self::STAT_PLAYER_DONATIONS);

		$width  = $this->maniaControl->manialinkManager->styleManager->getListWidgetsWidth();
		$height = $this->maniaControl->manialinkManager->styleManager->getListWidgetsHeight();

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

		//Predefine description Label
		$descriptionLabel = $this->maniaControl->manialinkManager->styleManager->getDefaultDescriptionLabel();
		$frame->add($descriptionLabel);

		// Headline
		$headFrame = new Frame();
		$frame->add($headFrame);
		$headFrame->setY($posY - 5);
		$array = array('$oId' => $posX + 5, '$oNickname' => $posX + 18, '$oDonated planets' => $posX + 70);
		$this->maniaControl->manialinkManager->labelLine($headFrame, $array);

		$index     = 1;
		$posY      = $posY - 10;
		$pageFrame = null;

		foreach ($stats as $playerIndex => $donations) {
			if ($index % 15 === 1) {
				$pageFrame = new Frame();
				$frame->add($pageFrame);
				$posY = $height / 2 - 10;
				$paging->addPage($pageFrame);
			}

			$playerFrame = new Frame();
			$pageFrame->add($playerFrame);
			$playerFrame->setY($posY);

			if ($index % 2 !== 0) {
				$lineQuad = new Quad_BgsPlayerCard();
				$playerFrame->add($lineQuad);
				$lineQuad->setSize($width, 4);
				$lineQuad->setSubStyle($lineQuad::SUBSTYLE_BgPlayerCardBig);
				$lineQuad->setZ(0.001);
			}

			$donatingPlayer = $this->maniaControl->playerManager->getPlayerByIndex($playerIndex);
			$array          = array($index => $posX + 5, $donatingPlayer->nickname => $posX + 18, $donations => $posX + 70);
			$this->maniaControl->manialinkManager->labelLine($playerFrame, $array);

			$posY -= 4;
			$index++;

			if ($index > 100) {
				break;
			}
		}

		// Render and display xml
		$this->maniaControl->manialinkManager->displayWidget($maniaLink, $player, 'TopDons');
	}
}
