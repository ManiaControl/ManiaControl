<?php

namespace MCTeam;

use FML\Controls\Control;
use FML\Controls\Frame;
use FML\Controls\Gauge;
use FML\Controls\Label;
use FML\Controls\Labels\Label_Button;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_BgsPlayerCard;
use FML\Controls\Quads\Quad_Icons128x32_1;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\Controls\Quads\Quad_UIConstruction_Buttons;
use FML\ManiaLink;
use FML\Script\Features\KeyAction;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Callbacks\Callbacks;
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\Commands\CommandListener;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Manialinks\SidebarMenuEntryListener;
use ManiaControl\Manialinks\SidebarMenuManager;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;
use ManiaControl\Plugins\Plugin;
use ManiaControl\Script\ScriptManager;
use ManiaControl\Server\Commands;
use ManiaControl\Server\Server;
use ManiaControl\Settings\Setting;
use ManiaControl\Settings\SettingManager;
use ManiaControl\Utils\ColorUtil;
use Maniaplanet\DedicatedServer\Structures\VoteRatio;
use Maniaplanet\DedicatedServer\Xmlrpc\ChangeInProgressException;
use Maniaplanet\DedicatedServer\Xmlrpc\GameModeException;


/**
 * ManiaControl Custom-Votes Plugin
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class CustomVotesPlugin implements SidebarMenuEntryListener, CommandListener, CallbackListener, ManialinkPageAnswerListener, TimerListener, Plugin {
	/*
	 * Constants
	 */
	const PLUGIN_ID      = 5;
	const PLUGIN_VERSION = 0.2;
	const PLUGIN_NAME    = 'CustomVotesPlugin';
	const PLUGIN_AUTHOR  = 'kremsy';

	const SETTING_WIDGET_POSX                = 'Widget-Position: X';
	const SETTING_WIDGET_POSY                = 'Widget-Position: Y';
	const SETTING_WIDGET_WIDTH               = 'Widget-Size: Width';
	const SETTING_WIDGET_HEIGHT              = 'Widget-Size: Height';
	const SETTING_VOTE_TIME                  = 'Voting Time';
	const SETTING_DEFAULT_PLAYER_RATIO       = 'Minimum Player Voters Ratio';
	const SETTING_DEFAULT_RATIO              = 'Default Success Ratio';
	const SETTING_SPECTATOR_ALLOW_VOTE       = 'Allow Spectators to vote';
	const SETTING_SPECTATOR_ALLOW_START_VOTE = 'Allow Spectators to start a vote';

	const MLID_WIDGET         = 'CustomVotesPlugin.WidgetId';
	const MLID_ICON           = 'CustomVotesPlugin.IconWidgetId';
	const CUSTOMVOTES_MENU_ID = 'CustomVotesPlugin.MenuId';

	const ACTION_POSITIVE_VOTE = 'CustomVotesPlugin.PositiveVote';
	const ACTION_NEGATIVE_VOTE = 'CustomVotesPlugin.NegativeVote';
	const ACTION_START_VOTE    = 'CustomVotesPlugin.StartVote.';


	const CB_CUSTOM_VOTE_FINISHED = 'CustomVotesPlugin.CustomVoteFinished';

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl  = null;
	private $voteCommands  = array();
	private $voteMenuItems = array();
	/** @var CurrentVote $currentVote */
	private $currentVote = null;

	/**
	 * @see \ManiaControl\Plugins\Plugin::prepare()
	 */
	public static function prepare(ManiaControl $maniaControl) {
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getId()
	 */
	public static function getId() {
		return self::PLUGIN_ID;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getName()
	 */
	public static function getName() {
		return self::PLUGIN_NAME;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getVersion()
	 */
	public static function getVersion() {
		return self::PLUGIN_VERSION;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getAuthor()
	 */
	public static function getAuthor() {
		return self::PLUGIN_AUTHOR;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getDescription()
	 */
	public static function getDescription() {
		return 'Plugin offers your Custom Votes like Restart, Skip, Balance...';
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::load()
	 */
	public function load(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Commands
		$this->maniaControl->getCommandManager()->registerCommandListener('vote', $this, 'chat_vote', false, 'Starts a new vote.');

		// Callbacks
		$this->maniaControl->getManialinkManager()->registerManialinkPageAnswerListener(self::ACTION_POSITIVE_VOTE, $this, 'handlePositiveVote');
		$this->maniaControl->getManialinkManager()->registerManialinkPageAnswerListener(self::ACTION_NEGATIVE_VOTE, $this, 'handleNegativeVote');
		$this->maniaControl->getTimerManager()->registerTimerListening($this, 'handle1Second', 1000);
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Commands::CB_VOTE_CANCELLED, $this, 'handleVoteCancelled');

		$this->maniaControl->getCallbackManager()->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 'handleManialinkPageAnswer');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(self::CB_CUSTOM_VOTE_FINISHED, $this, 'handleVoteFinished');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(PlayerManager::CB_PLAYERCONNECT, $this, 'handlePlayerConnect');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Server::CB_TEAM_MODE_CHANGED, $this, 'constructMenu');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(ScriptManager::CB_PAUSE_STATUS_CHANGED, $this, 'constructMenu');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(SettingManager::CB_SETTING_CHANGED, $this, 'handleSettingChanged');

		// Settings
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_POSX, -80); //160 -15
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_POSY, 80); //-15
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_WIDTH, 50); //30
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_HEIGHT, 20); //25

		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_DEFAULT_RATIO, 0.75);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_DEFAULT_PLAYER_RATIO, 0.65);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_SPECTATOR_ALLOW_VOTE, false);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_SPECTATOR_ALLOW_START_VOTE, true);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_VOTE_TIME, 40);

		$this->maniaControl->getManialinkManager()->getSidebarMenuManager()->addMenuEntry(SidebarMenuManager::ORDER_PLAYER_MENU + 5, self::CUSTOMVOTES_MENU_ID, $this, 'showIcon');
		//Define Votes
		$this->defineVote("teambalance", "Vote for Team Balance");
		$this->defineVote("skipmap", "Vote for Skip Map")->setStopCallback(Callbacks::ENDMAP);
		$this->defineVote("nextmap", "Vote for Skip Map")->setStopCallback(Callbacks::ENDMAP);
		$this->defineVote("skip", "Vote for Skip Map")->setStopCallback(Callbacks::ENDMAP);
		$this->defineVote("restartmap", "Vote for Restart Map")->setStopCallback(Callbacks::ENDMAP);
		$this->defineVote("restart", "Vote for Restart Map")->setStopCallback(Callbacks::ENDMAP);
		$this->defineVote("pausegame", "Vote for Pause Game");
		$this->defineVote("replay", "Vote to replay current map");

		foreach ($this->voteCommands as $name => $voteCommand) {
			$this->maniaControl->getCommandManager()->registerCommandListener($name, $this, 'handleChatVote', false, $voteCommand->name);
		}

		/* Disable Standard Votes */
		$ratioArray[] = new VoteRatio(VoteRatio::COMMAND_BAN, -1.);
		$ratioArray[] = new VoteRatio(VoteRatio::COMMAND_KICK, -1.);
		$ratioArray[] = new VoteRatio(VoteRatio::COMMAND_RESTART_MAP, -1.);
		$ratioArray[] = new VoteRatio(VoteRatio::COMMAND_TEAM_BALANCE, -1.);
		$ratioArray[] = new VoteRatio(VoteRatio::COMMAND_NEXT_MAP, -1.);

		$this->maniaControl->getClient()->setCallVoteRatios($ratioArray, false, true);

		$this->constructMenu();
		return true;
	}

	/**
	 * Define a Vote
	 *
	 * @param int    $voteIndex
	 * @param string $voteName
	 * @param bool   $idBased
	 * @param string $startText
	 * @param float  $neededRatio
	 * @return \MCTeam\VoteCommand
	 */
	public function defineVote($voteIndex, $voteName, $idBased = false, $startText = '', $neededRatio = -1) {
		if ($neededRatio < 0) {
			$neededRatio = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_DEFAULT_RATIO);
		}
		$voteCommand                    = new VoteCommand($voteIndex, $voteName, $idBased, $neededRatio);
		$voteCommand->startText         = $startText;
		$this->voteCommands[$voteIndex] = $voteCommand;

		return $voteCommand;
	}

	/**
	 * Handle ManiaControl OnInit callback
	 *
	 * @internal param array $callback
	 */
	public function constructMenu() {
		// Menu RestartMap
		$itemQuad = new Quad_UIConstruction_Buttons();
		$itemQuad->setSubStyle($itemQuad::SUBSTYLE_Reload);
		$itemQuad->setAction(self::ACTION_START_VOTE . 'restartmap');
		$this->addVoteMenuItem($itemQuad, 5, 'Vote for Restart-Map');

		if ($this->maniaControl->getServer()->getScriptManager()->modeUsesPause()) {
			$itemQuad = new Quad_Icons128x32_1();
			$itemQuad->setSubStyle($itemQuad::SUBSTYLE_ManiaLinkSwitch);
			$itemQuad->setAction(self::ACTION_START_VOTE . 'pausegame');
			$this->addVoteMenuItem($itemQuad, 10, 'Vote for a pause of Current Game');
		}

		//Menu SkipMap
		$itemQuad = new Quad_Icons64x64_1();
		$itemQuad->setSubStyle($itemQuad::SUBSTYLE_ArrowFastNext);
		$itemQuad->setAction(self::ACTION_START_VOTE . 'skipmap');
		$this->addVoteMenuItem($itemQuad, 15, 'Vote for a Map Skip');

		if ($this->maniaControl->getServer()->getScriptManager()->modeIsTeamMode()) {
			//Menu TeamBalance
			$itemQuad = new Quad_Icons128x32_1();
			$itemQuad->setSubStyle($itemQuad::SUBSTYLE_RT_Team);
			$itemQuad->setAction(self::ACTION_START_VOTE . 'teambalance');
			$this->addVoteMenuItem($itemQuad, 20, 'Vote for Team-Balance');
		}
		//Show the Menu's icon
		$this->showIcon();
	}

	/**
	 * Add a new Vote Menu Item
	 *
	 * @param Control $control
	 * @param int     $order
	 * @param string  $description
	 */
	public function addVoteMenuItem(Control $control, $order = 0, $description = null) {
		if (!isset($this->voteMenuItems[$order])) {
			$this->voteMenuItems[$order] = array();
			array_push($this->voteMenuItems[$order], array($control, $description));
			krsort($this->voteMenuItems);
		}
	}

	/**
	 * Shows the Icon Widget
	 *
	 * @param bool $login
	 */
	public function showIcon($login = false) {
		$pos               = $this->maniaControl->getManialinkManager()->getSidebarMenuManager()->getEntryPosition(self::CUSTOMVOTES_MENU_ID);
		$width             = $this->maniaControl->getSettingManager()->getSettingValue($this->maniaControl->getManialinkManager()->getSidebarMenuManager(), SidebarMenuManager::SETTING_MENU_ITEMSIZE);
		$quadStyle         = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultQuadStyle();
		$quadSubstyle      = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultQuadSubstyle();
		$itemMarginFactorX = 1.3;
		$itemMarginFactorY = 1.2;

		$itemSize = $width;

		$maniaLink = new ManiaLink(self::MLID_ICON);

		//Custom Vote Menu Iconsframe
		$frame = new Frame();
		$maniaLink->addChild($frame);
		$frame->setPosition($pos->getX(), $pos->getY());
		$frame->setZ(ManialinkManager::MAIN_MANIALINK_Z_VALUE);

		$backgroundQuad = new Quad();
		$frame->addChild($backgroundQuad);
		$backgroundQuad->setSize($width * $itemMarginFactorX, $itemSize * $itemMarginFactorY);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);

		$iconFrame = new Frame();
		$frame->addChild($iconFrame);

		$iconFrame->setSize($itemSize, $itemSize);
		$itemQuad = new Quad_UIConstruction_Buttons();
		$itemQuad->setSubStyle($itemQuad::SUBSTYLE_Add);
		$itemQuad->setSize($itemSize, $itemSize);
		$iconFrame->addChild($itemQuad);

		//Define Description Label
		$menuEntries      = count($this->voteMenuItems);
		$descriptionFrame = new Frame();
		$maniaLink->addChild($descriptionFrame);
		$descriptionFrame->setPosition($pos->getX() - $menuEntries * $itemSize * 1.05 - 5, $pos->getY());

		$descriptionLabel = new Label();
		$descriptionFrame->addChild($descriptionLabel);
		$descriptionLabel->setAlign($descriptionLabel::RIGHT, $descriptionLabel::TOP);
		$descriptionLabel->setSize(40, 4);
		$descriptionLabel->setTextSize(1.4);
		$descriptionLabel->setTextColor('fff');

		//Popout Frame
		$popoutFrame = new Frame();
		$maniaLink->addChild($popoutFrame);
		$popoutFrame->setPosition($pos->getX() - $itemSize * 0.5, $pos->getY());
		$popoutFrame->setZ(ManialinkManager::MAIN_MANIALINK_Z_VALUE);
		$popoutFrame->setHorizontalAlign($popoutFrame::RIGHT);
		$popoutFrame->setSize(4 * $itemSize * $itemMarginFactorX, $itemSize * $itemMarginFactorY);
		$popoutFrame->setVisible(false);


		$backgroundQuad = new Quad();
		$popoutFrame->addChild($backgroundQuad);
		$backgroundQuad->setHorizontalAlign($backgroundQuad::RIGHT);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);
		$backgroundQuad->setSize($menuEntries * $itemSize * 1.05 + 2, $itemSize * $itemMarginFactorY);

		$itemQuad->addToggleFeature($popoutFrame);

		// Add items
		$posX = -1;
		foreach ($this->voteMenuItems as $menuItems) {
			foreach ($menuItems as $menuItem) {
				/** @var Quad $menuQuad */
				$menuQuad = $menuItem[0];
				$popoutFrame->addChild($menuQuad);
				$menuQuad->setSize($itemSize, $itemSize);
				$menuQuad->setX($posX);
				$menuQuad->setHorizontalAlign($menuQuad::RIGHT);
				$posX -= $itemSize * 1.05;

				if ($menuItem[1]) {
					$menuQuad->removeAllScriptFeatures();
					$description = '$s' . $menuItem[1];
					$menuQuad->addTooltipLabelFeature($descriptionLabel, $description);
				}
			}
		}

		// Send manialink
		$this->maniaControl->getManialinkManager()->sendManialink($maniaLink, $login);
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::unload()
	 */
	public function unload() {
		//Enable Standard Votes
		$defaultRatio = $this->maniaControl->getClient()->getCallVoteRatio();

		$ratioArray[] = new VoteRatio(VoteRatio::COMMAND_BAN, $defaultRatio);
		$ratioArray[] = new VoteRatio(VoteRatio::COMMAND_KICK, $defaultRatio);
		$ratioArray[] = new VoteRatio(VoteRatio::COMMAND_RESTART_MAP, $defaultRatio);
		$ratioArray[] = new VoteRatio(VoteRatio::COMMAND_TEAM_BALANCE, $defaultRatio);
		$ratioArray[] = new VoteRatio(VoteRatio::COMMAND_NEXT_MAP, $defaultRatio);

		$this->maniaControl->getClient()->setCallVoteRatios($ratioArray, false);

		$this->destroyVote();
		$this->maniaControl->getManialinkManager()->hideManialink(self::MLID_ICON);
	}

	/**
	 * Destroys the current Vote
	 */
	private function destroyVote() {
		$emptyManialink = new ManiaLink(self::MLID_WIDGET);
		$this->maniaControl->getManialinkManager()->sendManialink($emptyManialink);

		// Remove the Listener for the Stop Callback if a stop callback is defined
		if ($this->currentVote && $this->currentVote->stopCallback) {
			$this->maniaControl->getCallbackManager()->unregisterCallbackListening($this->currentVote->stopCallback, $this);
		}

		$this->currentVote = null;
	}

	/**
	 * Handle PlayerConnect callback
	 *
	 * @param Player $player
	 */
	public function handlePlayerConnect(Player $player) {
		$this->showIcon($player->login);
	}

	/**
	 * Chat Vote
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function chat_vote(array $chat, Player $player) {
		$command = explode(" ", $chat[1][2]);
		if (isset($command[1])) {
			if (isset($this->voteCommands[$command[1]])) {
				$this->startVote($player, strtolower($command[1]));
			}
		}
	}

	/**
	 * Start a vote
	 *
	 * @param Player   $player
	 * @param int      $voteIndex
	 * @param callable $function calls the given function only if the vote is successful and returns as Parameter the Voting-Results
	 */
	public function startVote(Player $player, $voteIndex, $function = null) {
		// Check if the Player is muted
		if ($player->isMuted()) {
			$this->maniaControl->getChat()->sendError('Muted Players are not allowed to start a vote.', $player);
			return;
		}

		// Spectators are not allowed to start a vote
		if ($player->isSpectator
		    && !$this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_SPECTATOR_ALLOW_START_VOTE)
		) {
			$this->maniaControl->getChat()->sendError('Spectators are not allowed to start a vote.', $player);
			return;
		}

		//Vote does not exist
		if (!isset($this->voteCommands[$voteIndex])) {
			$this->maniaControl->getChat()->sendError('Undefined vote.', $player);
			return;
		}

		//A vote is currently running
		if (isset($this->currentVote)) {
			$this->maniaControl->getChat()->sendError('There is currently another vote running.', $player);
			return;
		}

		$maxTime = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_VOTE_TIME);

		/** @var VoteCommand $voteCommand */
		$voteCommand = $this->voteCommands[$voteIndex];

		$this->currentVote                    = new CurrentVote($voteCommand, $player, time() + $maxTime);
		$this->currentVote->neededRatio       = floatval($this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_DEFAULT_RATIO));
		$this->currentVote->neededPlayerRatio = floatval($this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_DEFAULT_PLAYER_RATIO));
		$this->currentVote->function          = $function;

		if ($voteCommand->getStopCallback()) {
			$this->maniaControl->getCallbackManager()->registerCallbackListener($voteCommand->getStopCallback(), $this, 'handleStopCallback');
			$this->currentVote->stopCallback = $voteCommand->getStopCallback();
		}

		if ($this->currentVote->voteCommand->startText) {
			$message = $this->currentVote->voteCommand->startText;
		} else {
			$message = '$fff' . $player->getEscapedNickname() . '$s$f8f started a $fff$<' . $this->currentVote->voteCommand->name . '$>$f8f!';
		}

		$this->maniaControl->getChat()->sendSuccess($message);
	}

	/**
	 * Destroys the Vote on the Stop Callback
	 */
	public function handleStopCallback() {
		$this->destroyVote();
	}

	/**
	 * Destroy the Vote on Cancelled Callback
	 */
	public function handleVoteCancelled() {
		$this->destroyVote();
	}

	/**
	 * Handle Standard Votes
	 *
	 * @param string $voteName
	 * @param float  $voteResult
	 */
	public function handleVoteFinished($voteName, $voteResult) {
		if ($voteResult >= $this->currentVote->neededRatio) {
			// Call Closure if one exists
			if (is_callable($this->currentVote->function)) {
				call_user_func($this->currentVote->function, $voteResult);
				return;
			}

			switch ($voteName) {
				case 'teambalance':
					$this->maniaControl->getClient()->autoTeamBalance();
					$this->maniaControl->getChat()->sendInformation('$f8fVote to $fffbalance the Teams$f8f has been successful!');
					break;
				case 'skipmap':
				case 'skip':
				case 'nextmap':
					try {
						$this->maniaControl->getMapManager()->getMapActions()->skipMap();
					} catch (ChangeInProgressException $e) {
					}
					$this->maniaControl->getChat()->sendInformation('$f8fVote to $fffskip the Map$f8f has been successful!');
					break;
				case 'restartmap':
					try {
						$this->maniaControl->getClient()->restartMap();
					} catch (ChangeInProgressException $e) {
					}
					$this->maniaControl->getChat()->sendInformation('$f8fVote to $fffrestart the Map$f8f has been successful!');
					break;
				case 'pausegame':
					try {
						//Gamemodes like Elite, Speedball
						$this->maniaControl->getClient()->sendModeScriptCommands(array('Command_ForceWarmUp' => true));
						$this->maniaControl->getChat()->sendInformation('$f8fVote to $fffpause the current Game$f8f has been successful!');
					} catch (GameModeException $ex) {
					}

					//TODO verify if not everything is replaced through the new pause
					$this->maniaControl->getModeScriptEventManager()->startPause();
					$this->maniaControl->getChat()->sendInformation('$f8fVote to $fffpause the current Game$f8f has been successful!');
					break;
				case 'replay':
					$this->maniaControl->getMapManager()->getMapQueue()->addFirstMapToMapQueue($this->currentVote->voter, $this->maniaControl->getMapManager()->getCurrentMap());
					$this->maniaControl->getChat()->sendInformation('$f8fVote to $fffreplay the Map$f8f has been successful!');
					break;
			}
		} else {
			//FIXME bugreport, no fail message on vote fail sometimes
			$this->maniaControl->getChat()->sendError('Vote Failed!');
		}
	}

	/**
	 * Handles the ManialinkPageAnswers and start a vote if a button in the panel got clicked
	 *
	 * @param array $callback
	 */
	public function handleManialinkPageAnswer(array $callback) {
		$actionId    = $callback[1][2];
		$actionArray = explode('.', $actionId);
		if (count($actionArray) <= 2) {
			return;
		}

		$voteIndex = $actionArray[2];
		if (isset($this->voteCommands[$voteIndex])) {
			$login  = $callback[1][1];
			$player = $this->maniaControl->getPlayerManager()->getPlayer($login);
			$this->startVote($player, $voteIndex);
		}
	}

	/**
	 * Handle a Player Chat Vote
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function handleChatVote(array $chat, Player $player) {
		$chatCommand = explode(' ', $chat[1][2]);
		$chatCommand = $chatCommand[0];
		$chatCommand = str_replace('/', '', $chatCommand);

		if (isset($this->voteCommands[$chatCommand])) {
			$this->startVote($player, $chatCommand);
		}
	}

	/**
	 * Undefine a Vote
	 *
	 * @param int $voteIndex
	 */
	public function undefineVote($voteIndex) {
		unset($this->voteCommands[$voteIndex]);
	}

	/**
	 * Handles a Positive Vote
	 *
	 * @param array  $callback
	 * @param Player $player
	 */
	public function handlePositiveVote(array $callback, Player $player) {
		if (!isset($this->currentVote)
		    || $player->isSpectator
		       && !$this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_SPECTATOR_ALLOW_VOTE)
		) {
			return;
		}

		$this->currentVote->votePositive($player->login);
	}

	/**
	 * Handles a negative Vote
	 *
	 * @param array  $callback
	 * @param Player $player
	 */
	public function handleNegativeVote(array $callback, Player $player) {
		if (!isset($this->currentVote)
		    || $player->isSpectator
		       && !$this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_SPECTATOR_ALLOW_VOTE)
		) {
			return;
		}

		$this->currentVote->voteNegative($player->login);
	}

	/**
	 * Handle ManiaControl 1 Second Callback
	 */
	public function handle1Second() {
		if (!isset($this->currentVote)) {
			return;
		}

		$voteCount      = $this->currentVote->getVoteCount();
		$votePercentage = 0;
		if ($voteCount > 0) {
			$votePercentage = $this->currentVote->positiveVotes / floatval($voteCount);
		}

		$timeUntilExpire = $this->currentVote->expireTime - time();
		$this->showVoteWidget($timeUntilExpire, $votePercentage);

		$playerCount      = $this->maniaControl->getPlayerManager()->getPlayerCount();
		$playersVoteRatio = 0;
		if ($playerCount > 0 && $voteCount > 0) {
			$playersVoteRatio = floatval($voteCount) / floatval($playerCount);
		}

		//Check if vote is over
		if ($timeUntilExpire <= 0 || (($playersVoteRatio >= $this->currentVote->neededPlayerRatio) && (($votePercentage >= $this->currentVote->neededRatio) || ($votePercentage <= 1 - $this->currentVote->neededRatio)))) {
			// Trigger callback
			$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_CUSTOM_VOTE_FINISHED, $this->currentVote->voteCommand->index, $votePercentage);

			//reset vote
			$this->destroyVote();
		}
	}

	/**
	 * Show the vote widget
	 *
	 * @param int   $timeUntilExpire
	 * @param float $votePercentage
	 */
	private function showVoteWidget($timeUntilExpire, $votePercentage) {
		$posX    = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_POSX);
		$posY    = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_POSY);
		$width   = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_WIDTH);
		$height  = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_HEIGHT);
		$maxTime = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_VOTE_TIME);

		$quadStyle    = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultQuadStyle();
		$quadSubstyle = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultQuadSubstyle();
		$labelStyle   = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultLabelStyle();

		$maniaLink = new ManiaLink(self::MLID_WIDGET);

		// mainframe
		$frame = new Frame();
		$maniaLink->addChild($frame);
		$frame->setSize($width, $height);
		$frame->setPosition($posX, $posY, 30);

		// Background Quad
		$backgroundQuad = new Quad();
		$frame->addChild($backgroundQuad);
		$backgroundQuad->setSize($width, $height);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);

		//Vote for label
		$label = new Label_Text();
		$frame->addChild($label);
		$label->setY($height / 2 - 3);
		$label->setSize($width - 5, $height);
		$label->setTextSize(1.3);
		$label->setText('$s ' . $this->currentVote->voteCommand->name);

		//Started by nick
		$label = new Label_Text();
		$frame->addChild($label);
		$label->setY($height / 2 - 6);
		$label->setSize($width - 5, 2);
		$label->setTextSize(1);
		$label->setTextColor('F80');
		$label->setText('$sStarted by ' . $this->currentVote->voter->nickname);


		//Time Gauge
		$timeGauge = new Gauge();
		$frame->addChild($timeGauge);
		$timeGauge->setY(1.5);
		$timeGauge->setSize($width * 0.95, 6);
		$timeGauge->setDrawBackground(false);
		if (!$timeUntilExpire) {
			$timeUntilExpire = 1;
		}
		$timeGaugeRatio = (100 / $maxTime * $timeUntilExpire) / 100;
		$timeGauge->setRatio($timeGaugeRatio + 0.15 - $timeGaugeRatio * 0.15);
		$gaugeColor = ColorUtil::floatToStatusColor($timeGaugeRatio);
		$timeGauge->setColor($gaugeColor . '9');

		//Time Left
		$label = new Label_Text();
		$frame->addChild($label);
		$label->setY(0);
		$label->setSize($width - 5, $height);
		$label->setTextSize(1.1);
		$label->setText('$sTime left: ' . $timeUntilExpire . "s");
		$label->setTextColor('FFF');

		//Vote Gauge
		$voteGauge = new Gauge();
		$frame->addChild($voteGauge);
		$voteGauge->setY(-4);
		$voteGauge->setSize($width * 0.65, 12);
		$voteGauge->setDrawBackground(false);
		$voteGauge->setRatio($votePercentage + 0.10 - $votePercentage * 0.10);
		$gaugeColor = ColorUtil::floatToStatusColor($votePercentage);
		$voteGauge->setColor($gaugeColor . '6');
		$voteGauge->setZ(-0.1);

		$posY      = -4.4;
		$voteLabel = new Label();
		$frame->addChild($voteLabel);
		$voteLabel->setY($posY);
		$voteLabel->setSize($width * 0.65, 12);
		$voteLabel->setStyle($labelStyle);
		$voteLabel->setTextSize(1);
		$voteLabel->setText('  ' . round($votePercentage * 100.) . '% (' . $this->currentVote->getVoteCount() . ')');


		$positiveQuad = new Quad_BgsPlayerCard();
		$frame->addChild($positiveQuad);
		$positiveQuad->setPosition(-$width / 2 + 6, $posY);
		$positiveQuad->setSubStyle($positiveQuad::SUBSTYLE_BgPlayerCardBig);
		$positiveQuad->setSize(5, 5);

		$positiveLabel = new Label_Button();
		$frame->addChild($positiveLabel);
		$positiveLabel->setPosition(-$width / 2 + 6, $posY);
		$positiveLabel->setStyle($labelStyle);
		$positiveLabel->setTextSize(1);
		$positiveLabel->setSize(3, 3);
		$positiveLabel->setTextColor('0F0');
		$positiveLabel->setText('F1');

		$negativeQuad = clone $positiveQuad;
		$frame->addChild($negativeQuad);
		$negativeQuad->setX($width / 2 - 6);

		$negativeLabel = clone $positiveLabel;
		$frame->addChild($negativeLabel);
		$negativeLabel->setX($width / 2 - 6);
		$negativeLabel->setTextColor('F00');
		$negativeLabel->setText('F2');

		// Voting Actions
		$positiveQuad->addActionTriggerFeature(self::ACTION_POSITIVE_VOTE);
		$negativeQuad->addActionTriggerFeature(self::ACTION_NEGATIVE_VOTE);

		$script            = $maniaLink->getScript();
		$keyActionPositive = new KeyAction(self::ACTION_POSITIVE_VOTE, 'F1');
		$script->addFeature($keyActionPositive);
		$keyActionNegative = new KeyAction(self::ACTION_NEGATIVE_VOTE, 'F2');
		$script->addFeature($keyActionNegative);

		// Send manialink
		$this->maniaControl->getManialinkManager()->sendManialink($maniaLink);
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

		$this->constructMenu();
	}
}

/**
 * Vote Command Model Class
 */
// TODO: extract classes to own files
class VoteCommand {
	public $index       = '';
	public $name        = '';
	public $neededRatio = 0;
	public $idBased     = false;
	public $startText   = '';

	private $stopCallback = '';

	/**
	 * Construct a new Vote Command
	 *
	 * @param int    $index
	 * @param string $name
	 * @param bool   $idBased
	 * @param float  $neededRatio
	 */
	public function __construct($index, $name, $idBased, $neededRatio) {
		$this->index       = $index;
		$this->name        = $name;
		$this->idBased     = $idBased;
		$this->neededRatio = $neededRatio;
	}

	/**
	 * Gets the Stop Callback
	 *
	 * @return string
	 */
	public function getStopCallback() {
		return $this->stopCallback;
	}

	/**
	 * Defines a Stop Callback
	 *
	 * @param $stopCallback
	 */
	public function setStopCallback($stopCallback) {
		$this->stopCallback = $stopCallback;
	}

}

/**
 * Current Vote Model Class
 */
class CurrentVote {
	const VOTE_FOR_ACTION     = '1';
	const VOTE_AGAINST_ACTION = '-1';

	public $voteCommand       = null;
	public $expireTime        = 0;
	public $positiveVotes     = 0;
	public $neededRatio       = 0;
	public $neededPlayerRatio = 0;
	public $voter             = null;
	public $map               = null;
	public $player            = null;
	public $function          = null;
	public $stopCallback      = "";

	private $playersVoted = array();

	/**
	 * Construct a Current Vote
	 *
	 * @param VoteCommand $voteCommand
	 * @param Player      $voter
	 * @param             $expireTime
	 */
	public function __construct(VoteCommand $voteCommand, Player $voter, $expireTime) {
		$this->expireTime  = $expireTime;
		$this->voteCommand = $voteCommand;
		$this->voter       = $voter;
		$this->votePositive($voter->login);
	}

	/**
	 * Handle a positive Vote
	 *
	 * @param string $login
	 */
	public function votePositive($login) {
		if (isset($this->playersVoted[$login])) {
			if ($this->playersVoted[$login] == self::VOTE_AGAINST_ACTION) {
				$this->playersVoted[$login] = self::VOTE_FOR_ACTION;
				$this->positiveVotes++;
			}
		} else {
			$this->playersVoted[$login] = self::VOTE_FOR_ACTION;
			$this->positiveVotes++;
		}
	}

	/**
	 * Handle a negative Vote
	 *
	 * @param string $login
	 */
	public function voteNegative($login) {
		if (isset($this->playersVoted[$login])) {
			if ($this->playersVoted[$login] == self::VOTE_FOR_ACTION) {
				$this->playersVoted[$login] = self::VOTE_AGAINST_ACTION;
				$this->positiveVotes--;
			}
		} else {
			$this->playersVoted[$login] = self::VOTE_AGAINST_ACTION;
		}
	}

	/**
	 * Get the Number of Votes
	 *
	 * @return int
	 */
	public function getVoteCount() {
		return count($this->playersVoted);
	}
}
