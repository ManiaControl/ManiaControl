<?php

namespace ManiaControl\Players;

use FML\Controls\Frame;
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
use ManiaControl\Manialinks\LabelLine;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Utils\Formatter;
use Maniaplanet\DedicatedServer\Xmlrpc\PlayerStateException;
use Maniaplanet\DedicatedServer\Xmlrpc\ServerOptionsException;
use Maniaplanet\DedicatedServer\Xmlrpc\UnknownPlayerException;
use MCTeam\CustomVotesPlugin;

/**
 * PlayerList Widget Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
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
	/** @deprecated */
	const ACTION_ADD_AS_MASTER        = 'PlayerList.PlayerAddAsMaster';
	const ACTION_ADD_AS_SUPER         = 'PlayerList.PlayerAddAsSuper';
	const ACTION_ADD_AS_ADMIN         = 'PlayerList.PlayerAddAsAdmin';
	const ACTION_ADD_AS_MOD           = 'PlayerList.PlayerAddAsModerator';
	const ACTION_REVOKE_RIGHTS        = 'PlayerList.RevokeRights';
	const ACTION_OPEN_PLAYER_DETAILED = 'PlayerList.OpenPlayerDetailed';
	const ACTION_SPECTATE_PLAYER      = 'PlayerList.SpectatePlayer';
	const ACTION_PAGING_CHUNKS        = 'PlayerList.PagingChunks';
	const CACHE_CURRENT_PAGE          = 'PlayerList.CurrentPage';
	const DEFAULT_CUSTOM_VOTE_PLUGIN  = 'MCTeam\CustomVotesPlugin';
	const SHOWN_MAIN_WINDOW           = -1;
	const MAX_PLAYERS_PER_PAGE        = 15;
	const MAX_PAGES_PER_CHUNK         = 2;

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl     = null;
	private $playersListShown = array();

	/**
	 * Construct a new PlayerList instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Callbacks
		$this->maniaControl->getManialinkManager()->registerManialinkPageAnswerListener(self::ACTION_CLOSE_PLAYER_ADV, $this, 'closePlayerAdvancedWidget');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(ManialinkManager::CB_MAIN_WINDOW_CLOSED, $this, 'closeWidget');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(ManialinkManager::CB_MAIN_WINDOW_OPENED, $this, 'handleWidgetOpened');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 'handleManialinkPageAnswer');

		// Update Widget Events
		$this->maniaControl->getCallbackManager()->registerCallbackListener(PlayerManager::CB_PLAYERINFOSCHANGED, $this, 'updateWidget');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(PlayerManager::CB_PLAYERDISCONNECT, $this, 'updateWidget');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(PlayerManager::CB_PLAYERCONNECT, $this, 'updateWidget');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(AuthenticationManager::CB_AUTH_LEVEL_CHANGED, $this, 'updateWidget');
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
		if ($openedWidget !== 'PlayerList') {
			unset($this->playersListShown[$player->login]);
		}
	}

	/**
	 * Closes the widget
	 *
	 * @param Player $player
	 */
	public function closeWidget(Player $player) {
		$player->destroyCache($this, self::CACHE_CURRENT_PAGE);
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
	public function showPlayerList(Player $player, $pageIndex = -1) {
		$width  = $this->maniaControl->getManialinkManager()->getStyleManager()->getListWidgetsWidth();
		$height = $this->maniaControl->getManialinkManager()->getStyleManager()->getListWidgetsHeight();

		// get PlayerList
		$players = $this->maniaControl->getPlayerManager()->getPlayers();

		if ($pageIndex < 0) {
			$pageIndex = (int) $player->getCache($this, self::CACHE_CURRENT_PAGE);
		}
		$player->setCache($this, self::CACHE_CURRENT_PAGE, $pageIndex);

		$totalPlayersCount = count($players);
		$chunkIndex        = $this->getChunkIndexFromPageNumber($pageIndex, $totalPlayersCount);
		$playerBeginIndex  = $this->getChunkStatsBeginIndex($chunkIndex);

		$pagesCount = ceil($totalPlayersCount / self::MAX_PLAYERS_PER_PAGE);

		//create manialink
		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);
		$script    = $maniaLink->getScript();
		$paging    = new Paging();
		$script->addFeature($paging);
		$paging->setCustomMaxPageNumber($pagesCount);
		$paging->setChunkActionAppendsPageNumber(true);
		$paging->setChunkActions(self::ACTION_PAGING_CHUNKS);
		$paging->setStartPageNumber($pageIndex + 1);

		// Main frame
		$frame = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultListFrame($script, $paging);
		$maniaLink->addChild($frame);

		// Start offsets
		$posX = -$width / 2;
		$posY = $height / 2;

		// Define z-values
		$backgroundZ = -0.1;

		// Predefine Description Label
		$descriptionLabel = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultDescriptionLabel();
		$frame->addChild($descriptionLabel);

		// Headline
		$headFrame = new Frame();
		$frame->addChild($headFrame);
		$headFrame->setY($posY - 5);

		$labelLine = new LabelLine($headFrame);
		$labelLine->addLabelEntryText('Id', $posX + 5);
		$labelLine->addLabelEntryText('Nickname', $posX + 18);
		$labelLine->addLabelEntryText('Login', $posX + 70);
		$labelLine->addLabelEntryText('Location', $posX + 101);
		if ($this->maniaControl->getAuthenticationManager()->checkRight($player, AuthenticationManager::AUTH_LEVEL_MODERATOR)) {
			$labelLine->addLabelEntryText('Actions', $posX + 135);
		}
		$labelLine->render();


		$index       = 1;
		$posY        = $height / 2 - 10;
		$pageFrame   = null;
		$playerIndex = 1 + $playerBeginIndex;

		//Slice Array to chunk length
		$players    = array_slice($players, $playerBeginIndex, self::MAX_PAGES_PER_CHUNK * self::MAX_PLAYERS_PER_PAGE, true);
		$pageNumber = 1 + $chunkIndex * self::MAX_PAGES_PER_CHUNK;

		foreach ($players as $listPlayer) {
			if ($index % self::MAX_PLAYERS_PER_PAGE === 1) {
				$pageFrame = new Frame();
				$frame->addChild($pageFrame);

				$paging->addPageControl($pageFrame, $pageNumber);
				$pageNumber++;
				$posY = $height / 2 - 10;
			}

			$path        = $listPlayer->getProvince();
			$playerFrame = new Frame();
			$pageFrame->addChild($playerFrame);

			if ($index % 2 !== 0) {
				$lineQuad = new Quad_BgsPlayerCard();
				$playerFrame->addChild($lineQuad);
				$lineQuad->setSize($width, 4);
				$lineQuad->setSubStyle($lineQuad::SUBSTYLE_BgPlayerCardBig);
				$lineQuad->setZ($backgroundZ);
			}
			$labelLine = new LabelLine($playerFrame);

			$labelLine->addLabelEntryText($playerIndex, $posX + 5, 13);
			$labelLine->addLabelEntryText($listPlayer->nickname, $posX + 18, 43);
			$labelLine->addLabelEntryText($listPlayer->login, $posX + 70, 26);
			$labelLine->addLabelEntryText($path, $posX + 101, 27);

			$labelLine->render();

			$playerFrame->setY($posY);

			// Show current Player Arrow
			if ($listPlayer->index === $player->index) {
				$currentQuad = new Quad_Icons64x64_1();
				$playerFrame->addChild($currentQuad);
				$currentQuad->setX($posX + 3.5);
				$currentQuad->setSize(4, 4);
				$currentQuad->setSubStyle($currentQuad::SUBSTYLE_ArrowBlue);
			}

			// Team Emblem
			if ($listPlayer->teamId >= 0) {
				// Player is in a Team
				$teamQuad = new Quad_Emblems();
				$playerFrame->addChild($teamQuad);
				$teamQuad->setX($posX + 10);
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
				$playerFrame->addChild($specQuad);
				$specQuad->setX($posX + 10);
				$specQuad->setSubStyle($specQuad::SUBSTYLE_Spectator);
				$specQuad->setSize(3.8, 3.8);
			}

			$countryCode = Formatter::mapCountry($listPlayer->getCountry());
			if ($countryCode !== 'OTH') {
				// Nation Quad
				$countryQuad = new Quad();
				$playerFrame->addChild($countryQuad);
				$countryQuad->setImageUrl("file://ZoneFlags/Login/{$listPlayer->login}/country");
				$countryQuad->setX($posX + 98);
				$countryQuad->setSize(4, 4);

				$countryQuad->addTooltipLabelFeature($descriptionLabel, '$<' . $listPlayer->nickname . '$> from ' . $listPlayer->path);
			}

			// Level Quad
			$rightQuad = new Quad_BgRaceScore2();
			$playerFrame->addChild($rightQuad);
			// TODO colorize to player personally
			$rightQuad->setSize(7, 3.5);
			$rightQuad->setSubStyle($rightQuad::SUBSTYLE_CupFinisher);
			$rightQuad->setX($posX + 13);

			$rightLabel = new Label_Text();
			$playerFrame->addChild($rightLabel);
			$rightLabel->setX($posX + 13.9);
			$rightLabel->setText($this->maniaControl->getAuthenticationManager()->getAuthLevelAbbreviation($listPlayer->authLevel));
			$rightLabel->setTextSize(0.8);
			$rightLabel->setTextColor('fff');

			$description = $this->maniaControl->getAuthenticationManager()->getAuthLevelName($listPlayer) . ' ' . $listPlayer->nickname;
			$rightLabel->addTooltipLabelFeature($descriptionLabel, $description);

			// Player Statistics
			$playerQuad = new Quad_Icons64x64_1();
			$playerFrame->addChild($playerQuad);
			$playerQuad->setX($posX + 61);
			$playerQuad->setSize(2.7, 2.7);
			$playerQuad->setSubStyle($playerQuad::SUBSTYLE_TrackInfo);
			$playerQuad->setAction(self::ACTION_OPEN_PLAYER_DETAILED . '.' . $listPlayer->login);
			$description = 'View Statistics of $<' . $listPlayer->nickname . '$>';
			$playerQuad->addTooltipLabelFeature($descriptionLabel, $description);

			// Camera Quad
			$playerQuad = new Quad_UIConstruction_Buttons();
			$playerFrame->addChild($playerQuad);
			$playerQuad->setX($posX + 64.5);
			$playerQuad->setSize(3.8, 3.8);
			$playerQuad->setSubStyle($playerQuad::SUBSTYLE_Camera);
			$description = 'Spectate $<' . $listPlayer->nickname . '$>';
			$playerQuad->addTooltipLabelFeature($descriptionLabel, $description);
			$playerQuad->setAction(self::ACTION_SPECTATE_PLAYER . '.' . $listPlayer->login);

			// Player Profile Quad
			$playerQuad = new Quad_UIConstruction_Buttons();
			$playerFrame->addChild($playerQuad);
			$playerQuad->setX($posX + 68);
			$playerQuad->setSize(3.8, 3.8);
			$playerQuad->setSubStyle($playerQuad::SUBSTYLE_Author);
			$playerQuad->addPlayerProfileFeature($listPlayer->login);

			// Description Label
			$description = 'View Player Profile of $<' . $listPlayer->nickname . '$>';
			$playerQuad->addTooltipLabelFeature($descriptionLabel, $description);

			if ($this->maniaControl->getAuthenticationManager()->checkRight($player, AuthenticationManager::AUTH_LEVEL_MODERATOR)) {
				// Further Player actions Quad
				$playerQuad = new Quad_Icons64x64_1();
				$playerFrame->addChild($playerQuad);
				$playerQuad->setX($posX + 132);
				$playerQuad->setSize(3.8, 3.8);
				$playerQuad->setSubStyle($playerQuad::SUBSTYLE_Buddy);
				$playerQuad->setAction(self::ACTION_PLAYER_ADV . '.' . $listPlayer->login);

				// Description Label
				$description = 'Advanced Player Actions for $<' . $listPlayer->nickname . '$>';
				$playerQuad->addTooltipLabelFeature($descriptionLabel, $description);
			}

			if ($this->maniaControl->getServer()->getScriptManager()->modeIsTeamMode()) {
				if ($this->maniaControl->getAuthenticationManager()->checkPermission($player, PlayerActions::SETTING_PERMISSION_FORCE_PLAYER_TEAM)) {
					// Force to Red-Team Quad
					$redQuad = new Quad_Emblems();
					$playerFrame->addChild($redQuad);
					$redQuad->setX($posX + 144);
					$redQuad->setSize(3.8, 3.8);
					$redQuad->setSubStyle($redQuad::SUBSTYLE_2);
					$redQuad->setAction(self::ACTION_FORCE_RED . '.' . $listPlayer->login);

					// Force to Red-Team Description Label
					$description = 'Force $<' . $listPlayer->nickname . '$> to Red Team!';
					$redQuad->addTooltipLabelFeature($descriptionLabel, $description);

					// Force to Blue-Team Quad
					$blueQuad = new Quad_Emblems();
					$playerFrame->addChild($blueQuad);
					$blueQuad->setX($posX + 140);
					$blueQuad->setSize(3.8, 3.8);
					$blueQuad->setSubStyle($blueQuad::SUBSTYLE_1);
					$blueQuad->setAction(self::ACTION_FORCE_BLUE . '.' . $listPlayer->login);

					// Force to Blue-Team Description Label
					$description = 'Force $<' . $listPlayer->nickname . '$> to Blue Team!';
					$blueQuad->addTooltipLabelFeature($descriptionLabel, $description);

				} else if ($this->maniaControl->getPluginManager()->isPluginActive(self::DEFAULT_CUSTOM_VOTE_PLUGIN)) {
					// Kick Player Vote
					$kickQuad = new Quad_UIConstruction_Buttons();
					$playerFrame->addChild($kickQuad);
					$kickQuad->setX($posX + 140);
					$kickQuad->setSize(3.8, 3.8);
					$kickQuad->setSubStyle($kickQuad::SUBSTYLE_Validate_Step2);
					$kickQuad->setAction(self::ACTION_KICK_PLAYER_VOTE . '.' . $listPlayer->login);

					$description = 'Start a Kick Vote on $<' . $listPlayer->nickname . '$>!';
					$kickQuad->addTooltipLabelFeature($descriptionLabel, $description);
				}
			} else {
				if ($this->maniaControl->getAuthenticationManager()->checkPermission($player, PlayerActions::SETTING_PERMISSION_FORCE_PLAYER_PLAY)) {
					// Force to Play
					$playQuad = new Quad_Emblems();
					$playerFrame->addChild($playQuad);
					$playQuad->setX($posX + 142);
					$playQuad->setSize(3.8, 3.8);
					$playQuad->setSubStyle($playQuad::SUBSTYLE_2);
					$playQuad->setAction(self::ACTION_FORCE_PLAY . '.' . $listPlayer->login);

					$description = 'Force $<' . $listPlayer->nickname . '$> to Play!';
					$playQuad->addTooltipLabelFeature($descriptionLabel, $description);
				}
			}

			if ($this->maniaControl->getAuthenticationManager()->checkPermission($player, PlayerActions::SETTING_PERMISSION_FORCE_PLAYER_SPEC)) {
				// Force to Spectator Quad
				$spectatorQuad = new Quad_BgRaceScore2();
				$playerFrame->addChild($spectatorQuad);
				$spectatorQuad->setX($posX + 136);
				$spectatorQuad->setSize(3.8, 3.8);
				$spectatorQuad->setSubStyle($spectatorQuad::SUBSTYLE_Spectator);
				$spectatorQuad->setAction(self::ACTION_FORCE_SPEC . '.' . $listPlayer->login);

				// Force to Spectator Description Label
				$description = 'Force $<' . $listPlayer->nickname . '$> to Spectator!';
				$spectatorQuad->addTooltipLabelFeature($descriptionLabel, $description);
			} else if ($this->maniaControl->getPluginManager()->isPluginActive(self::DEFAULT_CUSTOM_VOTE_PLUGIN)) {
				// Force to Spectator Quad
				$spectatorQuad = new Quad_BgRaceScore2();
				$playerFrame->addChild($spectatorQuad);
				$spectatorQuad->setX($posX + 136);
				$spectatorQuad->setSize(3.8, 3.8);
				$spectatorQuad->setSubStyle($spectatorQuad::SUBSTYLE_Spectator);
				$spectatorQuad->setAction(self::ACTION_FORCE_SPEC_VOTE . '.' . $listPlayer->login);

				// Force to Spectator Description Label
				$description = 'Start a Vote to force $<' . $listPlayer->nickname . '$> to Spectator!';
				$spectatorQuad->addTooltipLabelFeature($descriptionLabel, $description);
			}

			$posY -= 4;
			$index++;
			$playerIndex++;
		}

		// Show advanced window
		$listShownValue = $this->playersListShown[$player->login];
		if ($listShownValue && $listShownValue !== self::SHOWN_MAIN_WINDOW) {
			$frame = $this->showAdvancedPlayerWidget($player, $listShownValue);
			$maniaLink->addChild($frame);
		}

		// Render and display xml
		$this->maniaControl->getManialinkManager()->displayWidget($maniaLink, $player, 'PlayerList');
	}

	/**
	 * Extra window with special actions on players like warn,kick, ban, authorization levels...
	 *
	 * @param Player $admin
	 * @param string $login
	 * @return Frame
	 */
	public function showAdvancedPlayerWidget(Player $admin, $login) {
		$player       = $this->maniaControl->getPlayerManager()->getPlayer($login);
		$width        = $this->maniaControl->getManialinkManager()->getStyleManager()->getListWidgetsWidth();
		$height       = $this->maniaControl->getManialinkManager()->getStyleManager()->getListWidgetsHeight();
		$quadStyle    = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultMainWindowStyle();
		$quadSubstyle = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultMainWindowSubStyle();

		//Settings
		$posX      = $width / 2 + 0.2;
		$width     = 35;
		$textSize  = 1.5;
		$textColor = 'fff';
		$quadWidth = $width - 7;

		// mainframe
		$frame = new Frame();
		$frame->setSize($width, $height);
		$frame->setPosition($posX + $width / 2, 0, ManialinkManager::MAIN_MANIALINK_Z_VALUE);

		// Add Close Quad (X)
		$closeQuad = new Quad_Icons64x64_1();
		$frame->addChild($closeQuad);
		$closeQuad->setPosition($width / 2 - 2.5, $height / 2 - 2.5, 3);
		$closeQuad->setSize(6, 6);
		$closeQuad->setSubStyle($closeQuad::SUBSTYLE_QuitRace);
		$closeQuad->setAction(self::ACTION_CLOSE_PLAYER_ADV);

		// Background Quad
		$backgroundQuad = new Quad();
		$frame->addChild($backgroundQuad);
		$backgroundQuad->setSize($width, $height);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);
		$backgroundQuad->setZ(-0.3);

		// Show headline
		$label = new Label_Text();
		$frame->addChild($label);
		$label->setHorizontalAlign($label::LEFT);
		$label->setX(-$width / 2 + 5);
		$label->setY($height / 2 - 5);
		$label->setStyle($label::STYLE_TextCardSmall);
		$label->setTextSize($textSize);
		$label->setText('Advanced Actions');
		$label->setTextColor($textColor);

		// Nickname background
		$quad = new Quad();
		$frame->addChild($quad);
		$quad->setPosition(0, $height / 2 - 10, -0.1);
		$quad->setStyles($quadStyle, $quadSubstyle);
		$quad->setSize($width, 5);

		// Show Nickname
		$label = new Label_Text();
		$frame->addChild($label);
		$label->setWidth($width * 0.9);
		$label->setY($height / 2 - 10);
		$label->setStyle($label::STYLE_TextCardSmall);
		$label->setTextSize($textSize);
		$label->setText($player->nickname);
		$label->setTextColor($textColor);
		$label->setHorizontalAlign($label::CENTER);

		// Buttons
		$buttonHeight = 5;
		$buttonSpacing = 6.5;
		$buttonWidth = $quadWidth;
		$posY = $height / 2 - 20;

		$muteUnmuteButtonDescription =  $player->isMuted() ?             'UnMute'       :             'Mute';
		$muteUnmuteButtonAction      = ($player->isMuted() ? self::ACTION_UNMUTE_PLAYER : self::ACTION_MUTE_PLAYER) . '.' . $login;
		$muteUnmuteButton = $this->maniaControl->getManialinkManager()->getElementBuilder()->buildRoundTextButton(
			$muteUnmuteButtonDescription,
			$buttonWidth,
			$buttonHeight,
			$muteUnmuteButtonAction
		);
		$frame->addChild($muteUnmuteButton);
		$muteUnmuteButton->setY($posY);

		$posY -= $buttonSpacing;

		$warnButton = $this->maniaControl->getManialinkManager()->getElementBuilder()->buildRoundTextButton(
			'Warn',
			$buttonWidth,
			$buttonHeight,
			self::ACTION_WARN_PLAYER . '.' . $login
		);
		$frame->addChild($warnButton);
		$warnButton->setY($posY);

		$posY -= $buttonSpacing;

		$kickButton = $this->maniaControl->getManialinkManager()->getElementBuilder()->buildRoundTextButton(
			'$f90Kick',
			$buttonWidth,
			$buttonHeight,
			self::ACTION_KICK_PLAYER . '.' . $login
		);
		$frame->addChild($kickButton);
		$kickButton->setY($posY);

		$posY -= $buttonSpacing;

		$banButton = $this->maniaControl->getManialinkManager()->getElementBuilder()->buildRoundTextButton(
			'$700Ban',
			$buttonWidth,
			$buttonHeight,
			self::ACTION_BAN_PLAYER . '.' . $login
		);
		$frame->addChild($banButton);
		$banButton->setY($posY);

		$posY -= 2 * $buttonSpacing;

		$superColor = $this->maniaControl->getColorManager()->getColorByLevel(AuthenticationManager::AUTH_LEVEL_SUPERADMIN);
		$superButton = $this->maniaControl->getManialinkManager()->getElementBuilder()->buildRoundTextButton(
			"{$superColor}Set SuperAdmin",
			$buttonWidth,
			$buttonHeight,
			self::ACTION_ADD_AS_SUPER . '.' . $login
		);
		$frame->addChild($superButton);
		$superButton->setY($posY);

		$posY -= $buttonSpacing;

		$adminColor = $this->maniaControl->getColorManager()->getColorByLevel(AuthenticationManager::AUTH_LEVEL_ADMIN);
		$adminButton = $this->maniaControl->getManialinkManager()->getElementBuilder()->buildRoundTextButton(
			"{$adminColor}Set Admin",
			$buttonWidth,
			$buttonHeight,
			self::ACTION_ADD_AS_ADMIN . '.' . $login
		);
		$frame->addChild($adminButton);
		$adminButton->setY($posY);

		$posY -= $buttonSpacing;

		$modColor = $this->maniaControl->getColorManager()->getColorByLevel(AuthenticationManager::AUTH_LEVEL_MODERATOR);
		$modButton = $this->maniaControl->getManialinkManager()->getElementBuilder()->buildRoundTextButton(
			"{$modColor}Set Moderator",
			$buttonWidth,
			$buttonHeight,
			self::ACTION_ADD_AS_MOD . '.' . $login
		);
		$frame->addChild($modButton);
		$modButton->setY($posY);

		if ($player->authLevel > 0
		    && $this->maniaControl->getAuthenticationManager()->checkRight($admin, $player->authLevel + 1)
		) {
			$posY -= $buttonSpacing;

			$revokeButton = $this->maniaControl->getManialinkManager()->getElementBuilder()->buildRoundTextButton(
				'$700Revoke Rights',
				$buttonWidth,
				$buttonHeight,
				self::ACTION_REVOKE_RIGHTS . '.' . $login
			);
			$frame->addChild($revokeButton);
			$revokeButton->setY($posY);
		}

		return $frame;
	}

	/**
	 * Get the Chunk Index with the given Page Index
	 *
	 * @param int $pageIndex
	 * @return int
	 */
	private function getChunkIndexFromPageNumber($pageIndex, $totalPlayersCount) {
		$pagesCount = ceil($totalPlayersCount / self::MAX_PLAYERS_PER_PAGE);
		if ($pageIndex > $pagesCount - 1) {
			$pageIndex = $pagesCount - 1;
		}
		return floor($pageIndex / self::MAX_PAGES_PER_CHUNK);
	}

	/**
	 * Calculate the First Player Index to show for the given Chunk
	 *
	 * @param int $chunkIndex
	 * @return int
	 */
	private function getChunkStatsBeginIndex($chunkIndex) {
		return $chunkIndex * self::MAX_PAGES_PER_CHUNK * self::MAX_PLAYERS_PER_PAGE;
	}

	/**
	 * Called on ManialinkPageAnswer
	 *
	 * @param array $callback
	 */
	public function handleManialinkPageAnswer(array $callback) {
		$actionId    = $callback[1][2];
		$actionArray = explode('.', $actionId, 3);
		if(count($actionArray) < 2){
			return;
		}
		$action      = $actionArray[0] . '.' . $actionArray[1];

		if (count($actionArray) > 2) {

			$adminLogin  = $callback[1][1];
			$targetLogin = $actionArray[2];

			switch ($action) {
				case self::ACTION_SPECTATE_PLAYER:
					try {
						$this->maniaControl->getClient()->forceSpectator($adminLogin, PlayerActions::SPECTATOR_BUT_KEEP_SELECTABLE);
						$this->maniaControl->getClient()->forceSpectatorTarget($adminLogin, $targetLogin, 1);
					} catch (PlayerStateException $e) {
					} catch (UnknownPlayerException $e) {
					} catch(ServerOptionsException $e){
						//too many Spectators
					}
					break;
				case self::ACTION_OPEN_PLAYER_DETAILED:
					$player = $this->maniaControl->getPlayerManager()->getPlayer($adminLogin);
					$this->maniaControl->getPlayerManager()->getPlayerDetailed()->showPlayerDetailed($player, $targetLogin);
					unset($this->playersListShown[$player->login]);
					break;
				case self::ACTION_FORCE_BLUE:
					$this->maniaControl->getPlayerManager()->getPlayerActions()->forcePlayerToTeam($adminLogin, $targetLogin, PlayerActions::TEAM_BLUE);
					break;
				case self::ACTION_FORCE_RED:
					$this->maniaControl->getPlayerManager()->getPlayerActions()->forcePlayerToTeam($adminLogin, $targetLogin, PlayerActions::TEAM_RED);
					break;
				case self::ACTION_FORCE_SPEC:
					$this->maniaControl->getPlayerManager()->getPlayerActions()->forcePlayerToSpectator($adminLogin, $targetLogin, PlayerActions::SPECTATOR_BUT_KEEP_SELECTABLE);
					break;
				case self::ACTION_FORCE_PLAY:
					$this->maniaControl->getPlayerManager()->getPlayerActions()->forcePlayerToPlay($adminLogin, $targetLogin);
					break;
				case self::ACTION_MUTE_PLAYER:
					$this->maniaControl->getPlayerManager()->getPlayerActions()->mutePlayer($adminLogin, $targetLogin);
					$this->showPlayerList($this->maniaControl->getPlayerManager()->getPlayer($adminLogin));
					break;
				case self::ACTION_UNMUTE_PLAYER:
					$this->maniaControl->getPlayerManager()->getPlayerActions()->unMutePlayer($adminLogin, $targetLogin);
					$this->showPlayerList($this->maniaControl->getPlayerManager()->getPlayer($adminLogin));
					break;
				case self::ACTION_WARN_PLAYER:
					$this->maniaControl->getPlayerManager()->getPlayerActions()->warnPlayer($adminLogin, $targetLogin);
					break;
				case self::ACTION_KICK_PLAYER:
					$this->maniaControl->getPlayerManager()->getPlayerActions()->kickPlayer($adminLogin, $targetLogin);
					break;
				case self::ACTION_BAN_PLAYER:
					$this->maniaControl->getPlayerManager()->getPlayerActions()->banPlayer($adminLogin, $targetLogin);
					break;
				case self::ACTION_PLAYER_ADV:
					$admin = $this->maniaControl->getPlayerManager()->getPlayer($adminLogin);
					$this->advancedPlayerWidget($admin, $targetLogin);
					break;
				case self::ACTION_ADD_AS_MASTER:
				case self::ACTION_ADD_AS_SUPER:
					$this->maniaControl->getPlayerManager()->getPlayerActions()->grantAuthLevel($adminLogin, $targetLogin, AuthenticationManager::AUTH_LEVEL_SUPERADMIN);
					break;
				case self::ACTION_ADD_AS_ADMIN:
					$this->maniaControl->getPlayerManager()->getPlayerActions()->grantAuthLevel($adminLogin, $targetLogin, AuthenticationManager::AUTH_LEVEL_ADMIN);
					break;
				case self::ACTION_ADD_AS_MOD:
					$this->maniaControl->getPlayerManager()->getPlayerActions()->grantAuthLevel($adminLogin, $targetLogin, AuthenticationManager::AUTH_LEVEL_MODERATOR);
					break;
				case self::ACTION_REVOKE_RIGHTS:
					$this->maniaControl->getPlayerManager()->getPlayerActions()->revokeAuthLevel($adminLogin, $targetLogin);
					break;
				case self::ACTION_FORCE_SPEC_VOTE:
					/** @var $votesPlugin CustomVotesPlugin */
					$votesPlugin = $this->maniaControl->getPluginManager()->getPlugin(self::DEFAULT_CUSTOM_VOTE_PLUGIN);

					$admin  = $this->maniaControl->getPlayerManager()->getPlayer($adminLogin);
					$target = $this->maniaControl->getPlayerManager()->getPlayer($targetLogin);

					$startMessage = $admin->getEscapedNickname() . '$s started a vote to force $<' . $target->nickname . '$> into spectator!';

					$votesPlugin->defineVote('forcespec', 'Force ' . $target->getEscapedNickname() . ' Spec', true, $startMessage);

					$votesPlugin->startVote($admin, 'forcespec', function ($result) use (&$votesPlugin, &$target) {
						$message = $this->maniaControl->getChat()->formatMessage(
							'Vote successful -> %s forced to Spectator!',
							$target
						);
						$this->maniaControl->getChat()->sendSuccess($message);
						$votesPlugin->undefineVote('forcespec');

						try {
							$this->maniaControl->getClient()->forceSpectator($target->login, PlayerActions::SPECTATOR_BUT_KEEP_SELECTABLE);
							$this->maniaControl->getClient()->spectatorReleasePlayerSlot($target->login);
						} catch (PlayerStateException $e) {
						} catch (UnknownPlayerException $e) {
						}
					});
					break;
				case self::ACTION_KICK_PLAYER_VOTE:
					/** @var $votesPlugin CustomVotesPlugin */
					$votesPlugin = $this->maniaControl->getPluginManager()->getPlugin(self::DEFAULT_CUSTOM_VOTE_PLUGIN);

					$admin  = $this->maniaControl->getPlayerManager()->getPlayer($adminLogin);
					$target = $this->maniaControl->getPlayerManager()->getPlayer($targetLogin);

					$startMessage = $admin->getEscapedNickname() . '$s started a vote to kick $<' . $target->nickname . '$>!';


					$votesPlugin->defineVote('kick', 'Kick ' . $target->getEscapedNickname(), true, $startMessage);

					$votesPlugin->startVote($admin, 'kick', function ($result) use (&$votesPlugin, &$target) {
						$message = $this->maniaControl->getChat()->formatMessage(
							'Vote successful -> %s got kicked!',
							$target
						);
						$this->maniaControl->getChat()->sendSuccess($message);
						$votesPlugin->undefineVote('kick');

						$message = '$39FYou got kicked due to a Public Vote!';
						try {
							$this->maniaControl->getClient()->kick($target->login, $message);
						} catch (UnknownPlayerException $e) {
						}
					});
					break;
			}
		} else if (count($actionArray) == 2) {
			$playerLogin = $callback[1][1];
			$player      = $this->maniaControl->getPlayerManager()->getPlayer($playerLogin);
			if (substr($action, 0, strlen(self::ACTION_PAGING_CHUNKS)) === self::ACTION_PAGING_CHUNKS) {
				// Paging chunks
				$neededPage = (int) substr($action, strlen(self::ACTION_PAGING_CHUNKS));
				$this->showPlayerList($player, $neededPage - 1);
			}
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
	 */
	public function updateWidget() {
		foreach ($this->playersListShown as $login => $shown) {
			if (!$shown) {
				continue;
			}

			// Check if shown player still exists
			$player = $this->maniaControl->getPlayerManager()->getPlayer($login);
			if (!$player) {
				unset($this->playersListShown[$login]);
				continue;
			}

			// Reopen widget
			if ($shown !== self::SHOWN_MAIN_WINDOW) {
				$this->playersListShown[$login] = false;
			}
			$this->showPlayerList($player);
		}
	}
}
