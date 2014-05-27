<?php

namespace ManiaControl\Players;

use FML\Controls\Control;
use FML\Controls\Frame;
use FML\Controls\Labels\Label_Button;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_BgRaceScore2;
use FML\Controls\Quads\Quad_BgsPlayerCard;
use FML\Controls\Quads\Quad_Emblems;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\Controls\Quads\Quad_UIConstruction_Buttons;
use FML\ManiaLink;
use FML\Script\Features\Paging;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Utils\Formatter;
use Maniaplanet\DedicatedServer\Xmlrpc\PlayerStateException;
use Maniaplanet\DedicatedServer\Xmlrpc\UnknownPlayerException;
use MCTeam\CustomVotesPlugin;

/**
 * PlayerList Widget Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class PlayerList implements ManialinkPageAnswerListener, CallbackListener, TimerListener {
	/*
	 * Constants
	 */
	const ACTION_FORCE_RED            = 'PlayerList.ForceRed';
	const ACTION_FORCE_BLUE           = 'PlayerList.ForceBlue';
	const ACTION_FORCE_SPEC           = 'PlayerList.ForceSpec';
	const ACTION_FORCE_SPEC_VOTE      = 'PlayerList.ForceSpecVote';
	const ACTION_FORCE_PLAY           = 'PlayerList.ForcePlay';
	const ACTION_PLAYER_ADV           = 'PlayerList.PlayerAdvancedActions';
	const ACTION_CLOSE_PLAYER_ADV     = 'PlayerList.ClosePlayerAdvWidget';
	const ACTION_MUTE_PLAYER          = 'PlayerList.MutePlayer';
	const ACTION_UNMUTE_PLAYER        = 'PlayerList.UnMutePlayer';
	const ACTION_WARN_PLAYER          = 'PlayerList.WarnPlayer';
	const ACTION_KICK_PLAYER          = 'PlayerList.KickPlayer';
	const ACTION_KICK_PLAYER_VOTE     = 'PlayerList.KickPlayerVote';
	const ACTION_BAN_PLAYER           = 'PlayerList.BanPlayer';
	const ACTION_ADD_AS_MASTER        = 'PlayerList.PlayerAddAsMaster';
	const ACTION_ADD_AS_ADMIN         = 'PlayerList.PlayerAddAsAdmin';
	const ACTION_ADD_AS_MOD           = 'PlayerList.PlayerAddAsModerator';
	const ACTION_REVOKE_RIGHTS        = 'PlayerList.RevokeRights';
	const ACTION_OPEN_PLAYER_DETAILED = 'PlayerList.OpenPlayerDetailed';
	const ACTION_SPECTATE_PLAYER      = 'PlayerList.SpectatePlayer';
	const DEFAULT_CUSTOM_VOTE_PLUGIN  = 'MCTeam\CustomVotesPlugin';
	const SHOWN_MAIN_WINDOW           = -1;
	const MAX_PLAYERS_PER_PAGE        = 15;

	/*
	 * Private Properties
	 */
	private $maniaControl = null;
	private $playersListShown = array();

	/**
	 * Create a PlayerList Instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_CLOSE_PLAYER_ADV, $this, 'closePlayerAdvancedWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(ManialinkManager::CB_MAIN_WINDOW_CLOSED, $this, 'closeWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(ManialinkManager::CB_MAIN_WINDOW_OPENED, $this, 'handleWidgetOpened');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 'handleManialinkPageAnswer');

		// Update Widget Events
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERINFOCHANGED, $this, 'updateWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERDISCONNECT, $this, 'updateWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERCONNECT, $this, 'updateWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(AuthenticationManager::CB_AUTH_LEVEL_CHANGED, $this, 'updateWidget');
	}

	/**
	 * Add Player to Shown List
	 *
	 * @param Player $player
	 * @param int    $showStatus
	 */
	public function addPlayerToShownList(Player $player, $showStatus = self::SHOWN_MAIN_WINDOW) {
		$this->playersListShown[$player->login] = $showStatus;
	}

	/**
	 * Unset the player if he opened another Main Widget
	 *
	 * @param Player $player
	 * @param        $openedWidget
	 */
	public function handleWidgetOpened(Player $player, $openedWidget) {
		//unset when another main widget got opened
		if ($openedWidget != 'PlayerList') {
			unset($this->playersListShown[$player->login]);
		}
	}

	/**
	 * Closes the widget
	 *
	 * @param Player $player
	 */
	public function closeWidget(Player $player) {
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
	 * Show the PlayerList Widget to the Player
	 *
	 * @param Player $player
	 */
	public function showPlayerList(Player $player) {
		$width  = $this->maniaControl->manialinkManager->styleManager->getListWidgetsWidth();
		$height = $this->maniaControl->manialinkManager->styleManager->getListWidgetsHeight();

		// get PlayerList
		$players = $this->maniaControl->playerManager->getPlayers();

		//create manialink
		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);
		$script    = $maniaLink->getScript();
		$paging    = new Paging();
		$script->addFeature($paging);

		// Main frame
		$frame = $this->maniaControl->manialinkManager->styleManager->getDefaultListFrame($script, $paging);
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
		if ($this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_MODERATOR)) {
			$array = array("Id" => $x + 5, "Nickname" => $x + 18, "Login" => $x + 70, "Location" => $x + 101, "Actions" => $x + 135);
		} else {
			$array = array("Id" => $x + 5, "Nickname" => $x + 18, "Login" => $x + 70, "Location" => $x + 101);
		}
		$this->maniaControl->manialinkManager->labelLine($headFrame, $array);

		$i         = 1;
		$y         = $height / 2 - 10;
		$pageFrame = null;

		foreach ($players as $listPlayer) {
			if ($i % self::MAX_PLAYERS_PER_PAGE === 1) {
				$pageFrame = new Frame();
				$frame->add($pageFrame);

				$paging->addPage($pageFrame);
				$y = $height / 2 - 10;
			}

			$path        = $listPlayer->getProvince();
			$playerFrame = new Frame();
			$pageFrame->add($playerFrame);

			if ($i % 2 != 0) {
				$lineQuad = new Quad_BgsPlayerCard();
				$playerFrame->add($lineQuad);
				$lineQuad->setSize($width, 4);
				$lineQuad->setSubStyle($lineQuad::SUBSTYLE_BgPlayerCardBig);
				$lineQuad->setZ(0.001);
			}

			$array = array($i => $x + 5, $listPlayer->nickname => $x + 18, $listPlayer->login => $x + 70, $path => $x + 101);
			$this->maniaControl->manialinkManager->labelLine($playerFrame, $array);

			$playerFrame->setY($y);

			//Show current Player Arrow
			if ($listPlayer->index == $player->index) {
				$currentQuad = new Quad_Icons64x64_1();
				$playerFrame->add($currentQuad);
				$currentQuad->setX($x + 3.5);
				$currentQuad->setZ(0.2);
				$currentQuad->setSize(4, 4);
				$currentQuad->setSubStyle($currentQuad::SUBSTYLE_ArrowBlue);
			}

			// Team Emblem
			if ($listPlayer->teamId >= 0) {
				// Player is in a Team
				$teamQuad = new Quad_Emblems();
				$playerFrame->add($teamQuad);
				$teamQuad->setX($x + 10);
				$teamQuad->setZ(0.1);
				$teamQuad->setSize(3.8, 3.8);

				switch ($listPlayer->teamId) {
					case 0:
						$teamQuad->setSubStyle($teamQuad::SUBSTYLE_1);
						break;
					case 1:
						$teamQuad->setSubStyle($teamQuad::SUBSTYLE_2);
						break;
				}
			} else if ($listPlayer->isSpectator) {
				// Player is in Spectator Mode
				$specQuad = new Quad_BgRaceScore2();
				$playerFrame->add($specQuad);
				$specQuad->setX($x + 10);
				$specQuad->setZ(0.1);
				$specQuad->setSubStyle($specQuad::SUBSTYLE_Spectator);
				$specQuad->setSize(3.8, 3.8);
			}

			$countryCode = Formatter::mapCountry($listPlayer->getCountry());
			if ($countryCode != 'OTH') {
				// Nation Quad
				$countryQuad = new Quad();
				$playerFrame->add($countryQuad);
				$countryQuad->setImage("file://ZoneFlags/Login/{$listPlayer->login}/country");
				$countryQuad->setX($x + 98);
				$countryQuad->setSize(4, 4);
				$countryQuad->setZ(1);

				$countryQuad->addTooltipLabelFeature($descriptionLabel, '$<' . $listPlayer->nickname . '$> from ' . $listPlayer->path);
			}

			// Level Quad
			$rightQuad = new Quad_BgRaceScore2();
			$playerFrame->add($rightQuad);
			$rightQuad->setX($x + 13);
			$rightQuad->setZ(3);
			$rightQuad->setSubStyle($rightQuad::SUBSTYLE_CupFinisher);
			$rightQuad->setSize(7, 3.5);

			$rightLabel = new Label_Text();
			$playerFrame->add($rightLabel);
			$rightLabel->setX($x + 13.9);
			$rightLabel->setTextSize(0.8);
			$rightLabel->setZ(3.1);
			$rightLabel->setText($this->maniaControl->authenticationManager->getAuthLevelAbbreviation($listPlayer->authLevel));
			$rightLabel->setTextColor("fff");

			$description = $this->maniaControl->authenticationManager->getAuthLevelName($listPlayer) . " " . $listPlayer->nickname;
			$rightLabel->addTooltipLabelFeature($descriptionLabel, $description);

			// Player Statistics
			$playerQuad = new Quad_Icons64x64_1();
			$playerFrame->add($playerQuad);
			$playerQuad->setX($x + 61);
			$playerQuad->setZ(3);
			$playerQuad->setSubStyle($playerQuad::SUBSTYLE_TrackInfo);
			$playerQuad->setSize(2.7, 2.7);
			$playerQuad->setAction(self::ACTION_OPEN_PLAYER_DETAILED . "." . $listPlayer->login);
			$description = 'View Statistics of $<' . $listPlayer->nickname . '$>';
			$playerQuad->addTooltipLabelFeature($descriptionLabel, $description);

			// Camera Quad
			$playerQuad = new Quad_UIConstruction_Buttons();
			$playerFrame->add($playerQuad);
			$playerQuad->setX($x + 64.5);
			$playerQuad->setZ(3);
			$playerQuad->setSubStyle($playerQuad::SUBSTYLE_Camera);
			$playerQuad->setSize(3.8, 3.8);
			$description = 'Spectate $<' . $listPlayer->nickname . '$>';
			$playerQuad->addTooltipLabelFeature($descriptionLabel, $description);
			$playerQuad->setAction(self::ACTION_SPECTATE_PLAYER . "." . $listPlayer->login);

			// Player Profile Quad
			$playerQuad = new Quad_UIConstruction_Buttons();
			$playerFrame->add($playerQuad);
			$playerQuad->setX($x + 68);
			$playerQuad->setZ(3);
			$playerQuad->setSubStyle($playerQuad::SUBSTYLE_Author);
			$playerQuad->setSize(3.8, 3.8);
			$playerQuad->addPlayerProfileFeature($listPlayer->login);

			// Description Label
			$description = 'View Player Profile of $<' . $listPlayer->nickname . '$>';
			$playerQuad->addTooltipLabelFeature($descriptionLabel, $description);

			if ($this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_MODERATOR)) {
				// Further Player actions Quad
				$playerQuad = new Quad_Icons64x64_1();
				$playerFrame->add($playerQuad);
				$playerQuad->setX($x + 132);
				$playerQuad->setZ(0.1);
				$playerQuad->setSubStyle($playerQuad::SUBSTYLE_Buddy);
				$playerQuad->setSize(3.8, 3.8);
				$playerQuad->setAction(self::ACTION_PLAYER_ADV . "." . $listPlayer->login);

				// Description Label
				$description = 'Advanced Player Actions for $<' . $listPlayer->nickname . '$>';
				$playerQuad->addTooltipLabelFeature($descriptionLabel, $description);
			}

			if ($this->maniaControl->server->isTeamMode()) {
				if ($this->maniaControl->authenticationManager->checkPermission($player, PlayerActions::SETTING_PERMISSION_FORCE_PLAYER_TEAM)) {
					// Force to Red-Team Quad
					$redQuad = new Quad_Emblems();
					$playerFrame->add($redQuad);
					$redQuad->setX($x + 145);
					$redQuad->setZ(0.1);
					$redQuad->setSubStyle($redQuad::SUBSTYLE_2);
					$redQuad->setSize(3.8, 3.8);
					$redQuad->setAction(self::ACTION_FORCE_RED . "." . $listPlayer->login);

					// Force to Red-Team Description Label
					$description = 'Force $<' . $listPlayer->nickname . '$> to Red Team!';
					$redQuad->addTooltipLabelFeature($descriptionLabel, $description);

					// Force to Blue-Team Quad
					$blueQuad = new Quad_Emblems();
					$playerFrame->add($blueQuad);
					$blueQuad->setX($x + 141);
					$blueQuad->setZ(0.1);
					$blueQuad->setSubStyle($blueQuad::SUBSTYLE_1);
					$blueQuad->setSize(3.8, 3.8);
					$blueQuad->setAction(self::ACTION_FORCE_BLUE . "." . $listPlayer->login);

					// Force to Blue-Team Description Label
					$description = 'Force $<' . $listPlayer->nickname . '$> to Blue Team!';
					$blueQuad->addTooltipLabelFeature($descriptionLabel, $description);

				} else if ($this->maniaControl->pluginManager->isPluginActive(self::DEFAULT_CUSTOM_VOTE_PLUGIN)) {
					// Kick Player Vote
					$kickQuad = new Quad_UIConstruction_Buttons();
					$playerFrame->add($kickQuad);
					$kickQuad->setX($x + 141);
					$kickQuad->setZ(0.1);
					$kickQuad->setSubStyle($kickQuad::SUBSTYLE_Validate_Step2);
					$kickQuad->setSize(3.8, 3.8);
					$kickQuad->setAction(self::ACTION_KICK_PLAYER_VOTE . "." . $listPlayer->login);

					$description = 'Start a Kick Vote on $<' . $listPlayer->nickname . '$>!';
					$kickQuad->addTooltipLabelFeature($descriptionLabel, $description);
				}
			} else {
				if ($this->maniaControl->authenticationManager->checkPermission($player, PlayerActions::SETTING_PERMISSION_FORCE_PLAYER_PLAY)) {
					// Force to Play
					$playQuad = new Quad_Emblems();
					$playerFrame->add($playQuad);
					$playQuad->setX($x + 143);
					$playQuad->setZ(0.1);
					$playQuad->setSubStyle($playQuad::SUBSTYLE_2);
					$playQuad->setSize(3.8, 3.8);
					$playQuad->setAction(self::ACTION_FORCE_PLAY . "." . $listPlayer->login);

					$description = 'Force $<' . $listPlayer->nickname . '$> to Play!';
					$playQuad->addTooltipLabelFeature($descriptionLabel, $description);
				}
			}

			if ($this->maniaControl->authenticationManager->checkPermission($player, PlayerActions::SETTING_PERMISSION_FORCE_PLAYER_SPEC)) {
				// Force to Spectator Quad
				$spectatorQuad = new Quad_BgRaceScore2();
				$playerFrame->add($spectatorQuad);
				$spectatorQuad->setX($x + 137);
				$spectatorQuad->setZ(0.1);
				$spectatorQuad->setSubStyle($spectatorQuad::SUBSTYLE_Spectator);
				$spectatorQuad->setSize(3.8, 3.8);
				$spectatorQuad->setAction(self::ACTION_FORCE_SPEC . "." . $listPlayer->login);

				// Force to Spectator Description Label
				$description = 'Force $<' . $listPlayer->nickname . '$> to Spectator!';
				$spectatorQuad->addTooltipLabelFeature($descriptionLabel, $description);
			} else if ($this->maniaControl->pluginManager->isPluginActive(self::DEFAULT_CUSTOM_VOTE_PLUGIN)) {
				// Force to Spectator Quad
				$spectatorQuad = new Quad_BgRaceScore2();
				$playerFrame->add($spectatorQuad);
				$spectatorQuad->setX($x + 137);
				$spectatorQuad->setZ(0.1);
				$spectatorQuad->setSubStyle($spectatorQuad::SUBSTYLE_Spectator);
				$spectatorQuad->setSize(3.8, 3.8);
				$spectatorQuad->setAction(self::ACTION_FORCE_SPEC_VOTE . "." . $listPlayer->login);

				// Force to Spectator Description Label
				$description = 'Start a Vote to force $<' . $listPlayer->nickname . '$> to Spectator!';
				$spectatorQuad->addTooltipLabelFeature($descriptionLabel, $description);
			}

			$y -= 4;
			$i++;
		}

		// Show advanced window
		if ($this->playersListShown[$player->login] && $this->playersListShown[$player->login] != self::SHOWN_MAIN_WINDOW) {
			$frame = $this->showAdvancedPlayerWidget($player, $this->playersListShown[$player->login]);
			$maniaLink->add($frame);
		}

		// Render and display xml
		$this->maniaControl->manialinkManager->displayWidget($maniaLink, $player, 'PlayerList');
	}

	/**
	 * Extra window with special actions on players like warn,kick, ban, authorization levels...
	 *
	 * @param Player $admin
	 * @param string $login
	 * @return Frame
	 */
	public function showAdvancedPlayerWidget(Player $admin, $login) {
		$player       = $this->maniaControl->playerManager->getPlayer($login);
		$width        = $this->maniaControl->manialinkManager->styleManager->getListWidgetsWidth();
		$height       = $this->maniaControl->manialinkManager->styleManager->getListWidgetsHeight();
		$quadStyle    = $this->maniaControl->manialinkManager->styleManager->getDefaultMainWindowStyle();
		$quadSubstyle = $this->maniaControl->manialinkManager->styleManager->getDefaultMainWindowSubStyle();

		//Settings
		$x         = $width / 2 + 2.5;
		$width     = 35;
		$height    = $height * 0.75;
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
		$backgroundQuad->setImage('https://dl.dropboxusercontent.com/u/105352981/Stuff/CAM%20SM%20BORDER%20PNG.png'); //TODO just a test
		//$backgroundQuad->setStyles($quadStyle, $quadSubstyle);
		$backgroundQuad->setZ(0.2);

		// Background Quad
		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setSize($width - 2, $height - 2);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);
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
		$label->setY($height / 2 - 8);
		$label->setStyle($style);
		$label->setTextSize($textSize);
		$label->setText($player->nickname);
		$label->setTextColor($textColor);

		// Mute Player
		$y    = $height / 2 - 14;
		$quad = new Quad_BgsPlayerCard();
		$frame->add($quad);
		$quad->setX(0);
		$quad->setY($y);
		$quad->setSubStyle($quad::SUBSTYLE_BgPlayerCardBig);
		$quad->setSize($quadWidth, 5);

		$label = new Label_Button();
		$frame->add($label);
		$label->setX(0);
		$label->setY($y);
		$label->setStyle($style);
		$label->setTextSize($textSize);
		$label->setTextColor($textColor);

		if (!$this->maniaControl->playerManager->playerActions->isPlayerMuted($login)) {
			$label->setText("Mute");
			$quad->setAction(self::ACTION_MUTE_PLAYER . "." . $login);
		} else {
			$label->setText("UnMute");
			$quad->setAction(self::ACTION_UNMUTE_PLAYER . "." . $login);
		}

		// Warn Player
		$y -= 5;
		$quad = clone $quad;
		$frame->add($quad);
		$quad->setY($y);
		$quad->setAction(self::ACTION_WARN_PLAYER . "." . $login);

		$label = clone $label;
		$frame->add($label);
		$label->setY($y);
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

		if ($player->authLevel > 0 && $this->maniaControl->authenticationManager->checkRight($admin, $player->authLevel + 1)) {
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
	 * Called on ManialinkPageAnswer
	 *
	 * @param array $callback
	 */
	public function handleManialinkPageAnswer(array $callback) {
		$actionId    = $callback[1][2];
		$actionArray = explode('.', $actionId, 3);
		if (count($actionArray) <= 2) {
			return;
		}

		$action      = $actionArray[0] . "." . $actionArray[1];
		$adminLogin  = $callback[1][1];
		$targetLogin = $actionArray[2];

		switch ($action) {
			case self::ACTION_SPECTATE_PLAYER:
				try {
					$this->maniaControl->client->forceSpectator($adminLogin, PlayerActions::SPECTATOR_BUT_KEEP_SELECTABLE);
					$this->maniaControl->client->forceSpectatorTarget($adminLogin, $targetLogin, 1);
				} catch (PlayerStateException $e) {
				}
				break;
			case self::ACTION_OPEN_PLAYER_DETAILED:
				$player = $this->maniaControl->playerManager->getPlayer($adminLogin);
				$this->maniaControl->playerManager->playerDetailed->showPlayerDetailed($player, $targetLogin);
				unset($this->playersListShown[$player->login]);
				break;
			case self::ACTION_FORCE_BLUE:
				$this->maniaControl->playerManager->playerActions->forcePlayerToTeam($adminLogin, $targetLogin, PlayerActions::TEAM_BLUE);
				break;
			case self::ACTION_FORCE_RED:
				$this->maniaControl->playerManager->playerActions->forcePlayerToTeam($adminLogin, $targetLogin, PlayerActions::TEAM_RED);
				break;
			case self::ACTION_FORCE_SPEC:
				$this->maniaControl->playerManager->playerActions->forcePlayerToSpectator($adminLogin, $targetLogin, PlayerActions::SPECTATOR_BUT_KEEP_SELECTABLE);
				break;
			case self::ACTION_FORCE_PLAY:
				$this->maniaControl->playerManager->playerActions->forcePlayerToPlay($adminLogin, $targetLogin);
				break;
			case self::ACTION_MUTE_PLAYER:
				$this->maniaControl->playerManager->playerActions->mutePlayer($adminLogin, $targetLogin);
				$this->showPlayerList($this->maniaControl->playerManager->getPlayer($adminLogin));
				break;
			case self::ACTION_UNMUTE_PLAYER:
				$this->maniaControl->playerManager->playerActions->unMutePlayer($adminLogin, $targetLogin);
				$this->showPlayerList($this->maniaControl->playerManager->getPlayer($adminLogin));
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
			case self::ACTION_FORCE_SPEC_VOTE:
				/** @var $votesPlugin CustomVotesPlugin */
				$votesPlugin = $this->maniaControl->pluginManager->getPlugin(self::DEFAULT_CUSTOM_VOTE_PLUGIN);

				$admin  = $this->maniaControl->playerManager->getPlayer($adminLogin);
				$target = $this->maniaControl->playerManager->getPlayer($targetLogin);

				$startMessage = '$<' . $admin->nickname . '$>$s started a vote to force $<' . $target->nickname . '$> into spectator!';

				$votesPlugin->defineVote('forcespec', "Force $<" . $target->nickname . "$> Spec", true, $startMessage);

				$self = $this;
				$votesPlugin->startVote($admin, 'forcespec', function ($result) use (&$self, &$votesPlugin, &$target) {
					$self->maniaControl->chat->sendInformation('$sVote Successfully -> Player $<' . $target->nickname . '$> forced to Spectator!');
					$votesPlugin->undefineVote('forcespec');

					try {
						$self->maniaControl->client->forceSpectator($target->login, PlayerActions::SPECTATOR_BUT_KEEP_SELECTABLE);
						$self->maniaControl->client->spectatorReleasePlayerSlot($target->login);
					} catch (PlayerStateException $e) {
					}
				});
				break;
			case self::ACTION_KICK_PLAYER_VOTE:
				/** @var $votesPlugin CustomVotesPlugin */
				$votesPlugin = $this->maniaControl->pluginManager->getPlugin(self::DEFAULT_CUSTOM_VOTE_PLUGIN);

				$admin  = $this->maniaControl->playerManager->getPlayer($adminLogin);
				$target = $this->maniaControl->playerManager->getPlayer($targetLogin);

				$startMessage = '$<' . $admin->nickname . '$>$s started a vote to kick $<' . $target->nickname . '$>!';


				$votesPlugin->defineVote('kick', "Kick $<" . $target->nickname . "$>", true, $startMessage);

				$self = $this;
				$votesPlugin->startVote($admin, 'kick', function ($result) use (&$self, &$votesPlugin, &$target) {
					$self->maniaControl->chat->sendInformation('$sVote Successfully -> $<' . $target->nickname . '$> got Kicked!');
					$votesPlugin->undefineVote('kick');

					$message = '$39F You got kicked due a Public vote!$z ';
					try {
						$self->maniaControl->client->kick($target->login, $message);
					} catch (UnknownPlayerException $e) {
					}
				});
				break;
		}
	}

	/**
	 * Display the Advanced Player Window
	 *
	 * @param Player $caller
	 * @param string $login
	 */
	public function advancedPlayerWidget(Player $caller, $login) {
		// Set status to target player login
		$this->playersListShown[$caller->login] = $login;

		// Reopen PlayerList
		$this->showPlayerList($caller);
	}

	/**
	 * Reopen the widget on PlayerInfoChanged / Player Connect and Disconnect
	 *
	 * @param Player $player
	 */
	public function updateWidget(Player $player) {
		foreach ($this->playersListShown as $login => $shown) {
			if (!$shown) {
				continue;
			}

			// Check if shown player still exists
			$player = $this->maniaControl->playerManager->getPlayer($login);
			if (!$player) {
				unset($this->playersListShown[$login]);
				continue;
			}

			// Reopen widget
			if ($shown != self::SHOWN_MAIN_WINDOW) {
				$this->playersListShown[$login] = false;
			}
			$this->showPlayerList($player);
		}
	}
}
