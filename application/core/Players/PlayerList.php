<?php

namespace ManiaControl\Players;

use FML\Controls\Control;
use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Controls\Labels\Label_Button;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_BgRaceScore2;
use FML\Controls\Quads\Quad_BgsPlayerCard;
use FML\Controls\Quads\Quad_Emblems;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\Controls\Quads\Quad_UIConstruction_Buttons;
use FML\ManiaLink;
use FML\Script\Script;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Formatter;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;

/**
 * PlayerList Widget Class
 *
 * @author steeffeen & kremsy
 */
class PlayerList implements ManialinkPageAnswerListener, CallbackListener {

	/**
	 * Constants
	 */
	const ACTION_FORCE_RED        = 'PlayerList.ForceRed';
	const ACTION_FORCE_BLUE       = 'PlayerList.ForceBlue';
	const ACTION_FORCE_SPEC       = 'PlayerList.ForceSpec';
	const ACTION_PLAYER_ADV       = 'PlayerList.PlayerAdvancedActions';
	const ACTION_CLOSE_PLAYER_ADV = 'PlayerList.ClosePlayerAdvWidget';
	const ACTION_WARN_PLAYER      = 'PlayerList.WarnPlayer';
	const ACTION_KICK_PLAYER      = 'PlayerList.KickPlayer';
	const ACTION_BAN_PLAYER       = 'PlayerList.BanPlayer';
	const ACTION_ADD_AS_MASTER    = 'PlayerList.PlayerAddAsMaster';
	const ACTION_ADD_AS_ADMIN     = 'PlayerList.PlayerAddAsAdmin';
	const ACTION_ADD_AS_MOD       = 'PlayerList.PlayerAddAsModerator';
	const ACTION_REVOKE_RIGHTS    = 'PlayerList.RevokeRights';
	const SHOWN_MAIN_WINDOW       = -1;
	/**
	 * Private properties
	 */
	private $maniaControl = null;
	private $width;
	private $height;
	private $quadStyle;
	private $quadSubstyle;
	private $playersListShown = array();

	/**
	 * Create a new server commands instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_CLOSE_PLAYER_ADV, $this, 'closePlayerAdvancedWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(ManialinkManager::CB_MAIN_WINDOW_CLOSED, $this, 'closeWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 'handleManialinkPageAnswer');

		// Update Widget Events
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERINFOCHANGED, $this, 'updateWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERDISCONNECTED, $this, 'updateWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERJOINED, $this, 'updateWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(AuthenticationManager::CB_AUTH_LEVEL_CHANGED, $this, 'updateWidget');

		// settings
		$this->width        = $this->maniaControl->manialinkManager->styleManager->getListWidgetsWidth();
		$this->height       = $this->maniaControl->manialinkManager->styleManager->getListWidgetsHeight();
		$this->quadStyle    = $this->maniaControl->manialinkManager->styleManager->getDefaultMainWindowStyle();
		$this->quadSubstyle = $this->maniaControl->manialinkManager->styleManager->getDefaultMainWindowSubStyle();

	}

	public function addPlayerToShownList(Player $player, $showStatus = self::SHOWN_MAIN_WINDOW) {
		$this->playersListShown[$player->login] = $showStatus;
	}

	public function showPlayerList(Player $player) {
		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);

		// Create script and features
		$script = new Script();
		$maniaLink->setScript($script);

		// mainframe
		$frame = new Frame();
		$maniaLink->add($frame);
		$frame->setSize($this->width, $this->height);
		$frame->setPosition(0, 0);

		// Background Quad
		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setSize($this->width, $this->height);
		$backgroundQuad->setStyles($this->quadStyle, $this->quadSubstyle);

		// Add Close Quad (X)
		$closeQuad = new Quad_Icons64x64_1();
		$frame->add($closeQuad);
		$closeQuad->setPosition($this->width * 0.483, $this->height * 0.467, 3);
		$closeQuad->setSize(6, 6);
		$closeQuad->setSubStyle(Quad_Icons64x64_1::SUBSTYLE_QuitRace);
		$closeQuad->setAction(ManialinkManager::ACTION_CLOSEWIDGET);


		// Start offsets
		$x = -$this->width / 2;
		$y = $this->height / 2;


		//Predefine Description Label
		$preDefinedDescriptionLabel = new Label();
		$preDefinedDescriptionLabel->setAlign(Control::LEFT, Control::TOP);
		$preDefinedDescriptionLabel->setPosition($x + 10, -$this->height / 2 + 5);
		$preDefinedDescriptionLabel->setSize($this->width * 0.7, 4);
		$preDefinedDescriptionLabel->setTextSize(2);
		$preDefinedDescriptionLabel->setVisible(false);

		// Headline
		$headFrame = new Frame();
		$frame->add($headFrame);
		$headFrame->setY($y - 5);
		// $array = array("Id" => $x + 5, "Nickname" => $x + 10, "Login" => $x + 40, "Ladder" => $x + 60,"Zone" => $x + 85);
		if($this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_MODERATOR)) {
			$array = array("Id" => $x + 5, "Nickname" => $x + 18, "Login" => $x + 60, "Location" => $x + 91, "Actions" => $x + 135);
		} else {
			$array = array("Id" => $x + 5, "Nickname" => $x + 18, "Login" => $x + 60, "Location" => $x + 91);
		}
		$this->maniaControl->manialinkManager->labelLine($headFrame, $array);

		// get PlayerList
		$players = $this->maniaControl->playerManager->getPlayers();

		$i = 1;
		$y -= 10;
		foreach($players as $listPlayer) {
			/**
			 *
			 * @var Player $listPlayer
			 */

			$path        = $listPlayer->getProvince();
			$playerFrame = new Frame();
			$frame->add($playerFrame);

			if($i % 2 != 0) {
				$lineQuad = new Quad_BgsPlayerCard();
				$playerFrame->add($lineQuad);
				$lineQuad->setSize($this->width, 4);
				$lineQuad->setSubStyle($lineQuad::SUBSTYLE_BgPlayerCardBig);
				$lineQuad->setZ(0.001);
			}

			// $array = array($i => $x + 5, $listPlayer->nickname => $x + 10, $listPlayer->login => $x + 50, $listPlayer->ladderRank =>
			// $x + 60, $listPlayer->ladderScore => $x + 70, $path => $x + 85);
			$array = array($i => $x + 5, $listPlayer->nickname => $x + 18, $listPlayer->login => $x + 60, $path => $x + 91);
			// $properties = array('profile' => $listPlayer->login, 'script' => $script);
			$this->maniaControl->manialinkManager->labelLine($playerFrame, $array);
			$playerFrame->setY($y);

			// Team Emblem
			if($listPlayer->teamId >= 0) {
				// Player is in a Team
				$teamQuad = new Quad_Emblems();
				$playerFrame->add($teamQuad);
				$teamQuad->setX($x + 10);
				$teamQuad->setZ(0.1);
				$teamQuad->setSize(3.8, 3.8);

				switch($listPlayer->teamId) {
					case 0:
						$teamQuad->setSubStyle($teamQuad::SUBSTYLE_1);
						break;
					case 1:
						$teamQuad->setSubStyle($teamQuad::SUBSTYLE_2);
						break;
				}
			} else if($listPlayer->isSpectator) {
				// Player is in Spectator Mode
				$specQuad = new Quad_BgRaceScore2();
				$playerFrame->add($specQuad);
				$specQuad->setX($x + 10);
				$specQuad->setZ(0.1);
				$specQuad->setSubStyle($specQuad::SUBSTYLE_Spectator);
				$specQuad->setSize(3.8, 3.8);
			}

			if(!$listPlayer->isFakePlayer()) {
				// Nation Quad
				$countryQuad = new Quad();
				$playerFrame->add($countryQuad);
				$countryCode = Formatter::mapCountry($listPlayer->getCountry());
				$countryQuad->setImage("file://Skins/Avatars/Flags/{$countryCode}.dds");
				$countryQuad->setX($x + 88);
				$countryQuad->setSize(4, 4);
				$countryQuad->setZ(-0.1);

				$descriptionLabel = clone $preDefinedDescriptionLabel;
				$frame->add($descriptionLabel);
				$descriptionLabel->setText($listPlayer->nickname . " from " . $listPlayer->path);
				$script->addTooltip($countryQuad, $descriptionLabel);
			}

			// Level Quad
			$rightQuad = new Quad_BgRaceScore2();
			$playerFrame->add($rightQuad);
			$rightQuad->setX($x + 13);
			$rightQuad->setZ(5);
			$rightQuad->setSubStyle($rightQuad::SUBSTYLE_CupFinisher);
			$rightQuad->setSize(7, 3.5);

			$rightLabel = new Label_Text();
			$playerFrame->add($rightLabel);
			$rightLabel->setX($x + 13.9);
			$rightLabel->setTextSize(0.8);
			$rightLabel->setZ(10);

			$descriptionLabel = clone $preDefinedDescriptionLabel;
			$frame->add($descriptionLabel);
			$descriptionLabel->setText($this->maniaControl->authenticationManager->getAuthLevelName($listPlayer->authLevel) . " " . $listPlayer->nickname);
			$script->addTooltip($rightQuad, $descriptionLabel);

			// Player Profile Quad
			$playerQuad = new Quad_UIConstruction_Buttons();
			$playerFrame->add($playerQuad);
			$playerQuad->setX($x + 58);
			$playerQuad->setZ(20);
			$playerQuad->setSubStyle($playerQuad::SUBSTYLE_Author);
			$playerQuad->setSize(3.8, 3.8);
			$script->addProfileButton($playerQuad, $listPlayer->login);

			// Description Label
			$descriptionLabel = clone $preDefinedDescriptionLabel;
			$frame->add($descriptionLabel);
			$descriptionLabel->setText("View Player profile of " . $listPlayer->nickname);
			$script->addTooltip($playerQuad, $descriptionLabel);

			switch($listPlayer->authLevel) {
				case authenticationManager::AUTH_LEVEL_MASTERADMIN:
					$rightLabel->setText("MA");
					break;
				case authenticationManager::AUTH_LEVEL_SUPERADMIN:
					$rightLabel->setText("SA");
					break;
				case authenticationManager::AUTH_LEVEL_ADMIN:
					$rightLabel->setText("AD");
					break;
				case authenticationManager::AUTH_LEVEL_MODERATOR:
					$rightLabel->setText("MOD");
			}

			$rightLabel->setTextColor("fff");

			if($this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_MODERATOR)) {
				// Further Player actions Quad
				$playerQuad = new Quad_Icons64x64_1();
				$playerFrame->add($playerQuad);
				$playerQuad->setX($x + 132);
				$playerQuad->setZ(0.1);
				$playerQuad->setSubStyle($playerQuad::SUBSTYLE_Buddy);
				$playerQuad->setSize(3.8, 3.8);
				$playerQuad->setAction(self::ACTION_PLAYER_ADV . "." . $listPlayer->login);

				$descriptionLabel = clone $preDefinedDescriptionLabel;
				$frame->add($descriptionLabel);
				$descriptionLabel->setText("Advanced Player Actions on " . $listPlayer->nickname);
				$script->addTooltip($playerQuad, $descriptionLabel);

				// Force to Red-Team Quad
				$redQuad = new Quad_Emblems();
				$playerFrame->add($redQuad);
				$redQuad->setX($x + 145);
				$redQuad->setZ(0.1);
				$redQuad->setSubStyle($redQuad::SUBSTYLE_2);
				$redQuad->setSize(3.8, 3.8);
				$redQuad->setAction(self::ACTION_FORCE_RED . "." . $listPlayer->login);

				// Force to Red-Team Description Label
				$descriptionLabel = clone $preDefinedDescriptionLabel;;
				$frame->add($descriptionLabel);
				$descriptionLabel->setText("Force " . $listPlayer->nickname . '$z to Red Team!');
				$script->addTooltip($redQuad, $descriptionLabel);


				// Force to Blue-Team Quad
				$blueQuad = new Quad_Emblems();
				$playerFrame->add($blueQuad);
				$blueQuad->setX($x + 141);
				$blueQuad->setZ(0.1);
				$blueQuad->setSubStyle($blueQuad::SUBSTYLE_1);
				$blueQuad->setSize(3.8, 3.8);
				$blueQuad->setAction(self::ACTION_FORCE_BLUE . "." . $listPlayer->login);

				// Force to Blue-Team Description Label
				$descriptionLabel = clone $preDefinedDescriptionLabel;
				$frame->add($descriptionLabel);
				$descriptionLabel->setText("Force " . $listPlayer->nickname . '$z to Blue Team!');
				$script->addTooltip($blueQuad, $descriptionLabel);

				// Force to Spectator Quad
				$spectatorQuad = new Quad_BgRaceScore2();
				$playerFrame->add($spectatorQuad);
				$spectatorQuad->setX($x + 137);
				$spectatorQuad->setZ(0.1);
				$spectatorQuad->setSubStyle($spectatorQuad::SUBSTYLE_Spectator);
				$spectatorQuad->setSize(3.8, 3.8);
				$spectatorQuad->setAction(self::ACTION_FORCE_SPEC . "." . $listPlayer->login);

				// Force to Spectator Description Label
				$descriptionLabel = clone $preDefinedDescriptionLabel;
				$frame->add($descriptionLabel);
				$descriptionLabel->setText("Force " . $listPlayer->nickname . '$z to Spectator!');
				$script->addTooltip($spectatorQuad, $descriptionLabel);
			}
			$i++;
			$y -= 4;
		}

		// show advanced window
		if($this->playersListShown[$player->login] != false && $this->playersListShown[$player->login] != self::SHOWN_MAIN_WINDOW) {
			$frame = $this->showAdvancedPlayerWidget($this->playersListShown[$player->login]);
			$maniaLink->add($frame);
		}

		// render and display xml
		$this->maniaControl->manialinkManager->displayWidget($maniaLink, $player);
	}

	/**
	 * Displays the Advanced Player Window
	 *
	 * @param Player $caller
	 * @param        $login
	 */
	public function advancedPlayerWidget(Player $caller, $login) {
		if(!$caller) {
			return;
		}
		$this->playersListShown[$caller->login] = $login; // Show a certain player
		$this->showPlayerList($caller); // reopen playerlist
	}

	/**
	 * Extra window with special actions on players like warn,kick, ban, authorization levels...
	 *
	 * @param $login
	 * @return Frame
	 */
	public function showAdvancedPlayerWidget($login) {
		$player = $this->maniaControl->playerManager->getPlayer($login);

		// todo all configurable or as constants
		$x         = $this->width / 2 + 2.5;
		$width     = 35;
		$height    = $this->height * 0.7;
		$hAlign    = Control::LEFT;
		$style     = Label_Text::STYLE_TextCardSmall;
		$textSize  = 1.5;
		$textColor = 'FFF';
		$quadWidth = $width - 7;

		// mainframe
		$frame = new Frame();
		$frame->setSize($width, $height);
		$frame->setPosition($x + $width / 2, 0);

		// Add Close Quad (X)
		$closeQuad = new Quad_Icons64x64_1();
		$frame->add($closeQuad);
		$closeQuad->setPosition($width * 0.4, $height * 0.43, 3);
		$closeQuad->setSize(6, 6);
		$closeQuad->setSubStyle(Quad_Icons64x64_1::SUBSTYLE_QuitRace);
		$closeQuad->setAction(self::ACTION_CLOSE_PLAYER_ADV);

		// Background Quad
		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setSize($width, $height);
		$backgroundQuad->setStyles($this->quadStyle, $this->quadSubstyle);
		$backgroundQuad->setZ(0.1);

		// Show headline
		$label = new Label_Text();
		$frame->add($label);
		$label->setHAlign($hAlign);
		$label->setX(-$width / 2 + 5);
		$label->setY($height / 2 - 5);
		$label->setStyle($style);
		$label->setTextSize($textSize);
		$label->setText("Advanced Actions");
		$label->setTextColor($textColor);

		// Show Nickname
		$label = new Label_Text();
		$frame->add($label);
		$label->setHAlign($hAlign);
		$label->setX(0);
		$label->setAlign(Control::CENTER, Control::CENTER);
		$label->setY($height / 2 - 8);
		$label->setStyle($style);
		$label->setTextSize($textSize);
		$label->setText($player->nickname);
		$label->setTextColor($textColor);

		$y = $height / 2 - 14;
		// Show Warn
		$quad = new Quad_BgsPlayerCard();
		$frame->add($quad);
		$quad->setX(0);
		$quad->setY($y);
		$quad->setSubStyle($quad::SUBSTYLE_BgPlayerCardBig);
		$quad->setSize($quadWidth, 5);
		$quad->setAction(self::ACTION_WARN_PLAYER . "." . $login);

		$label = new Label_Button();
		$frame->add($label);
		$label->setX(0);
		$label->setAlign(Control::CENTER, Control::CENTER);
		$label->setY($y);
		$label->setStyle($style);
		$label->setTextSize($textSize);
		$label->setText("Warn");
		$label->setTextColor($textColor);

		$y -= 5;

		// Show Kick
		$quad = clone $quad;
		$frame->add($quad);
		$quad->setY($y);
		$quad->setAction(self::ACTION_KICK_PLAYER . "." . $login);

		$label = clone $label;
		$frame->add($label);
		$label->setY($y);
		$label->setText("Kick");
		$label->setTextColor("F90");

		$y -= 5;
		// Show Ban
		$quad = clone $quad;
		$frame->add($quad);
		$quad->setY($y);
		$quad->setAction(self::ACTION_BAN_PLAYER . "." . $login);

		$label = clone $label;
		$frame->add($label);
		$label->setY($y);
		$label->setText("Ban");
		$label->setTextColor("700");

		$y -= 10;
		// Show Add as Master-Admin
		$quad = clone $quad;
		$frame->add($quad);
		$quad->setY($y);
		$quad->setAction(self::ACTION_ADD_AS_MASTER . "." . $login);

		$label = clone $label;
		$frame->add($label);
		$label->setY($y);

		$label->setText("Set SuperAdmin");

		$label->setTextColor($textColor);

		$y -= 5;
		// Show Add as Admin
		$quad = clone $quad;
		$frame->add($quad);
		$quad->setY($y);
		$quad->setAction(self::ACTION_ADD_AS_ADMIN . "." . $login);

		$label = clone $label;
		$frame->add($label);
		$label->setY($y);
		$label->setText("Set Admin");
		$label->setTextColor($textColor);

		$y -= 5;
		// Show Add as Moderator
		$quad = clone $quad;
		$frame->add($quad);
		$quad->setY($y);
		$quad->setAction(self::ACTION_ADD_AS_MOD . "." . $login);

		$label = clone $label;
		$frame->add($label);
		$label->setY($y);
		$label->setText("Set Moderator");
		$label->setTextColor($textColor);

		if($this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_MODERATOR)) {
			$y -= 5;
			// Revoke Rights
			$quad = clone $quad;
			$frame->add($quad);
			$quad->setY($y);
			$quad->setAction(self::ACTION_REVOKE_RIGHTS . "." . $login);

			$label = clone $label;
			$frame->add($label);
			$label->setY($y);
			$label->setText("Revoke Rights");
			$label->setTextColor("700");
		}

		return $frame;
	}

	/**
	 * Closes the widget
	 *
	 * @param array $callback
	 */
	public function closeWidget(array $callback) {
		$player = $callback[1];
		unset($this->playersListShown[$player->login]);
	}

	/**
	 * Closes the player advanced widget widget
	 *
	 * @param array  $callback
	 * @param Player $player
	 */
	public function closePlayerAdvancedWidget(array $callback, Player $player) {
		$this->playersListShown[$player->login] = self::SHOWN_MAIN_WINDOW;
		$this->showPlayerList($player); // overwrite the manialink
	}

	/**
	 * Called on ManialinkPageAnswer
	 *
	 * @param array $callback
	 */
	public function handleManialinkPageAnswer(array $callback) {
		$actionId    = $callback[1][2];
		$actionArray = explode(".", $actionId);
		if(count($actionArray) <= 2) {
			return;
		}
		$action      = $actionArray[0] . "." . $actionArray[1];
		$adminLogin  = $callback[1][1];
		$targetLogin = $actionArray[2];

		switch($action) {
			case self::ACTION_FORCE_BLUE:
				$this->maniaControl->playerManager->playerActions->forcePlayerToTeam($adminLogin, $targetLogin, PlayerActions::BLUE_TEAM);
				break;
			case self::ACTION_FORCE_RED:
				$this->maniaControl->playerManager->playerActions->forcePlayerToTeam($adminLogin, $targetLogin, PlayerActions::RED_TEAM);
				break;
			case self::ACTION_FORCE_SPEC:
				$this->maniaControl->playerManager->playerActions->forcePlayerToSpectator($adminLogin, $targetLogin, PlayerActions::SPECTATOR_BUT_KEEP_SELECTABLE);
				break;
			case self::ACTION_WARN_PLAYER:
				$this->maniaControl->playerManager->playerActions->warnPlayer($adminLogin, $targetLogin);
				break;
			case self::ACTION_KICK_PLAYER:
				$this->maniaControl->playerManager->playerActions->kickPlayer($adminLogin, $targetLogin);
				break;
			case self::ACTION_BAN_PLAYER:
				$this->maniaControl->playerManager->playerActions->banPlayer($adminLogin, $targetLogin);
				break;
			case self::ACTION_PLAYER_ADV:
				$admin = $this->maniaControl->playerManager->getPlayer($adminLogin);
				$this->advancedPlayerWidget($admin, $targetLogin);
				break;
			case self::ACTION_ADD_AS_MASTER:
				$this->maniaControl->playerManager->playerActions->grandAuthLevel($adminLogin, $targetLogin, AuthenticationManager::AUTH_LEVEL_SUPERADMIN);
				break;
			case self::ACTION_ADD_AS_ADMIN:
				$this->maniaControl->playerManager->playerActions->grandAuthLevel($adminLogin, $targetLogin, AuthenticationManager::AUTH_LEVEL_ADMIN);
				break;
			case self::ACTION_ADD_AS_MOD:
				$this->maniaControl->playerManager->playerActions->grandAuthLevel($adminLogin, $targetLogin, AuthenticationManager::AUTH_LEVEL_MODERATOR);
				break;
			case self::ACTION_REVOKE_RIGHTS:
				$this->maniaControl->playerManager->playerActions->revokeAuthLevel($adminLogin, $targetLogin);
				break;
		}
	}

	/**
	 * Reopen the widget on PlayerInfoChanged / Player Connect and Disconnect
	 *
	 * @param array $callback
	 */
	public function updateWidget(array $callback) {
		foreach($this->playersListShown as $login => $shown) {
			if($shown) {
				// Check if Shown player still exists
				if($shown != self::SHOWN_MAIN_WINDOW && $this->maniaControl->playerManager->getPlayer($shown) == null) {
					$this->playersListShown[$login] = false;
				}
				$player = $this->maniaControl->playerManager->getPlayer($login);
				if($player != null) {
					$this->showPlayerList($player);
				} else {
					// if player with the open widget disconnected remove him from the shownlist
					unset($this->playersListShown[$login]);
				}
			}
		}
	}
} 