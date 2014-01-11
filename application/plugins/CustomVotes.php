<?php

namespace ManiaControl\Plugins;

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
use FML\Script\Script;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\ColorUtil;
use ManiaControl\Commands\CommandListener;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;
use ManiaControl\Server\ServerCommands;


/**
 * ManiaControl Chat-Message Plugin
 *
 * @author kremsy and steeffeen
 */
class CustomVotesPlugin implements CommandListener, CallbackListener, ManialinkPageAnswerListener, Plugin {
	/**
	 * Constants
	 */
	const PLUGIN_ID      = 10;
	const PLUGIN_VERSION = 0.1;
	const PLUGIN_NAME    = 'CustomVotesPlugin';
	const PLUGIN_AUTHOR  = 'kremsy and steeffeen';

	const SETTING_VOTE_ICON_POSX   = 'Vote-Icon-Position: X';
	const SETTING_VOTE_ICON_POSY   = 'Vote-Icon-Position: Y';
	const SETTING_VOTE_ICON_WIDTH  = 'Vote-Icon-Size: Width';
	const SETTING_VOTE_ICON_HEIGHT = 'Vote-Icon-Size: Height';

	const SETTING_WIDGET_POSX                = 'Widget-Position: X';
	const SETTING_WIDGET_POSY                = 'Widget-Position: Y';
	const SETTING_WIDGET_WIDTH               = 'Widget-Size: Width';
	const SETTING_WIDGET_HEIGHT              = 'Widget-Size: Height';
	const SETTING_VOTE_TIME                  = 'Voting Time';
	const SETTING_DEFAULT_RATIO              = 'Default Success Ratio';
	const SETTING_SPECTATOR_ALLOW_VOTE       = 'Allow Specators to vote';
	const SETTING_SPECTATOR_ALLOW_START_VOTE = 'Allow Specators to start a vote';

	const MLID_WIDGET = 'CustomVotesPlugin.WidgetId';
	const MLID_ICON   = 'CustomVotesPlugin.IconWidgetId';

	const VOTE_FOR_ACTION     = '1';
	const VOTE_AGAINST_ACTION = '-1';

	const ACTION_POSITIVE_VOTE = 'CustomVotesPlugin.PositivVote';
	const ACTION_NEGATIVE_VOTE = 'CustomVotesPlugin.NegativeVote';
	const ACTION_START_VOTE    = 'CustomVotesPlugin.StartVote.';


	const CB_CUSTOM_VOTE_FINISHED = 'CustomVotesPlugin.CustomVoteFinished';

	/**
	 * Private properties
	 */
	/** @var maniaControl $maniaControl */
	private $maniaControl = null;
	private $voteCommands = array();
	private $currentVote = null;
	private $currentVoteExpireTime = 0;
	private $playersVoted = array();
	private $playersVotedPositiv = 0;
	/** @var Player $voter */
	private $voter = null;

	/**
	 * Load the plugin
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 * @return bool
	 */
	public function load(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		$this->maniaControl->commandManager->registerCommandListener('vote', $this, 'chat_vote');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_1_SECOND, $this, 'handle1Second');
		$this->maniaControl->callbackManager->registerCallbackListener(ServerCommands::CB_VOTE_CANCELED, $this, 'handleVoteCanceled');
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_POSITIVE_VOTE, $this, 'handlePositiveVote');
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_NEGATIVE_VOTE, $this, 'handleNegativeVote');

		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 'handleManialinkPageAnswer');
		$this->maniaControl->callbackManager->registerCallbackListener(self::CB_CUSTOM_VOTE_FINISHED, $this, 'handleVoteFinished');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_ONINIT, $this, 'handleOnInit');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERJOINED, $this, 'handlePlayerConnect');

		//Settings
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_VOTE_ICON_POSX, 156.);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_VOTE_ICON_POSY, -58.6);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_VOTE_ICON_WIDTH, 6);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_VOTE_ICON_HEIGHT, 6);

		$this->maniaControl->settingManager->initSetting($this, self::SETTING_WIDGET_POSX, 160 - 15); //160 - 42 - 15
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_WIDGET_POSY, -15); //90 - 2 - 15
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_WIDGET_WIDTH, 30);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_WIDGET_HEIGHT, 25);

		$this->maniaControl->settingManager->initSetting($this, self::SETTING_DEFAULT_RATIO, 0.65);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_SPECTATOR_ALLOW_VOTE, false);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_SPECTATOR_ALLOW_START_VOTE, false);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_VOTE_TIME, 60);

		$this->defineVote("teambalance", "Team Balance");
		$this->defineVote("skipmap", "Skip Map");
		$this->defineVote("nextmap", "Skip Map");
		$this->defineVote("restartmap", "Restart Map");
		$this->defineVote("pausegame", "Pause Game");

		return true;
	}

	/**
	 * Unload the plugin and its resources
	 */
	public function unload() {
		$this->maniaControl->commandManager->unregisterCommandListener($this);
		unset($this->maniaControl);
	}

	/**
	 * Handle PlayerConnect callback
	 *
	 * @param array $callback
	 */
	public function handlePlayerConnect(array $callback) {
		$player = $callback[1];
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
		if(isset($command[1])) {
			if(isset($this->voteCommands[$command[1]])) {
				$this->startVote($player, strtolower($command[1]));
			}
		}
	}

	/**
	 * Handle ManiaControl OnInit callback
	 *
	 * @param array $callback
	 */
	public function handleOnInit(array $callback) {
		$this->showIcon();
	}

	/**
	 * Destroy the Vote on Canceled Callback
	 *
	 * @param array $callback
	 */
	public function handleVoteCanceled(array $callback) {
		//reset vote
		$this->destroyVote();
	}

	/**
	 * Handle Standard Votes
	 *
	 * @param array $callback
	 */
	public function handleVoteFinished(array $callback) {
		$voteName    = $callback[1];
		$voteResult  = $callback[2];
		$neededRatio = $this->maniaControl->settingManager->getSetting($this, self::SETTING_DEFAULT_RATIO);

		if($voteResult >= $neededRatio) {
			switch($voteName) {
				case 'teambalance':
					$this->maniaControl->client->query('AutoTeamBalance');
					$this->maniaControl->chat->sendInformation('Vote Successfully -> Teams got Balanced!');
					break;
				case 'skipmap':
				case 'nextmap':
					$this->maniaControl->client->query('NextMap');
					$this->maniaControl->chat->sendInformation('Vote Successfully -> Map skipped!');
					break;
				case 'restartmap':
					$this->maniaControl->client->query('RestartMap');
					$this->maniaControl->chat->sendInformation('Vote Successfully -> Map restarted!');
					break;
				case 'pausegame':
					$this->maniaControl->client->query('SendModeScriptCommands', array('Command_ForceWarmUp' => True));
					$this->maniaControl->chat->sendInformation('Vote Successfully -> Current Game paused!');
					break;
			}


		} else {
			$this->maniaControl->chat->sendInformation('Vote Failed!');
		}

	}

	/**
	 * Handles the ManialinkPageAnswers and start a vote if a button in the panel got clicked
	 *
	 * @param array $callback
	 */
	public function handleManialinkPageAnswer(array $callback) {
		//TODO Fx buttons

		$actionId    = $callback[1][2];
		$actionArray = explode('.', $actionId);
		if(count($actionArray) <= 2) {
			return;
		}

		$voteIndex = $actionArray[2];
		if(isset($this->voteCommands[$voteIndex])) {
			$login  = $callback[1][1];
			$player = $this->maniaControl->playerManager->getPlayer($login);
			$this->startVote($player, $voteIndex);
		}
	}

	/**
	 * Defines a Vote
	 *
	 * @param $voteName
	 */
	public function defineVote($voteIndex, $voteName, $neededRatio = -1) {
		if($neededRatio == -1) {
			$neededRatio = $this->maniaControl->settingManager->getSetting($this, self::SETTING_DEFAULT_RATIO);
		}
		$this->voteCommands[$voteIndex] = array("Index" => $voteIndex, "Name" => $voteName, "Ratio" => $neededRatio);
	}

	/**
	 * Starts a vote
	 *
	 * @param \ManiaControl\Players\Player $player
	 * @param                              $voteIndex
	 */
	public function startVote(Player $player, $voteIndex) {
		//TODO messages
		//Player is muted
		if($this->maniaControl->playerManager->playerActions->isPlayerMuted($player)) {
			return;
		}

		//Specators are not allowed to start a vote
		if($player->isSpectator && !$this->maniaControl->settingManager->getSetting($this, self::SETTING_SPECTATOR_ALLOW_START_VOTE)) {
			return;
		}

		//Vote does not exist
		if(!isset($this->voteCommands[$voteIndex])) {
			return;
		}

		//A vote is currently running
		if($this->currentVote != null) {
			return;
		}

		$maxTime = $this->maniaControl->settingManager->getSetting($this, self::SETTING_VOTE_TIME);

		$this->maniaControl->chat->sendChat("Vote started");
		$this->currentVote           = $this->voteCommands[$voteIndex];
		$this->currentVoteExpireTime = time() + $maxTime; //TODO as setting

		$this->playersVoted[$player->login] = self::VOTE_FOR_ACTION;
		$this->playersVotedPositiv++;

		$this->voter = $player;
	}

	/**
	 * Handles a Positive Vote
	 *
	 * @param array  $callback
	 * @param Player $player
	 */
	public function handlePositiveVote(array $callback, Player $player) {
		if($player->isSpectator && !$this->maniaControl->settingManager->getSetting($this, self::SETTING_SPECTATOR_ALLOW_VOTE)) {
			return;
		}

		if(isset($this->playersVoted[$player->login])) {
			if($this->playersVoted[$player->login] == self::VOTE_AGAINST_ACTION) {
				$this->playersVoted[$player->login] = self::VOTE_FOR_ACTION;
				$this->playersVotedPositiv++;
			}
		} else {
			$this->playersVoted[$player->login] = self::VOTE_FOR_ACTION;
			$this->playersVotedPositiv++;
		}
	}

	/**
	 * Handles a negative Vote
	 *
	 * @param array  $callback
	 * @param Player $player
	 */
	public function handleNegativeVote(array $callback, Player $player) {
		if($player->isSpectator && !$this->maniaControl->settingManager->getSetting($this, self::SETTING_SPECTATOR_ALLOW_VOTE)) {
			return;
		}

		if(isset($this->playersVoted[$player->login])) {
			if($this->playersVoted[$player->login] == self::VOTE_FOR_ACTION) {
				$this->playersVoted[$player->login] = self::VOTE_AGAINST_ACTION;
				$this->playersVotedPositiv--;
			}
		} else {
			$this->playersVoted[$player->login] = self::VOTE_AGAINST_ACTION;
		}
	}

	/**
	 * Handle ManiaControl 1 Second callback
	 *
	 * @param array $callback
	 */
	public function handle1Second(array $callback) {
		if($this->currentVote == null) {
			return;
		}

		$votePercentage = $this->playersVotedPositiv / count($this->playersVoted);

		$timeUntilExpire = $this->currentVoteExpireTime - time();
		$this->showVoteWidget($timeUntilExpire, $votePercentage);

		//Check if vote is over
		if($timeUntilExpire <= 0) {
			// Trigger callback
			$this->maniaControl->callbackManager->triggerCallback(self::CB_CUSTOM_VOTE_FINISHED, array(self::CB_CUSTOM_VOTE_FINISHED, $this->currentVote["Index"], $votePercentage));

			//reset vote
			$this->destroyVote();
		}
	}

	/**
	 * Destroys the current Vote
	 */
	private function destroyVote() {
		$emptyManialink = new ManiaLink(self::MLID_WIDGET);
		$manialinkText  = $emptyManialink->render()->saveXML();
		$this->maniaControl->manialinkManager->sendManialink($manialinkText);

		$this->playersVotedPositiv = 0;
		$this->playersVoted        = null;
		$this->currentVote         = null;
		$this->voter               = null;
	}

	/**
	 * Shows the vote widget
	 *
	 * @param $timeUntilExpire
	 * @param $votePercentage
	 */
	private function showVoteWidget($timeUntilExpire, $votePercentage) {
		$pos_x   = $this->maniaControl->settingManager->getSetting($this, self::SETTING_WIDGET_POSX);
		$pos_y   = $this->maniaControl->settingManager->getSetting($this, self::SETTING_WIDGET_POSY);
		$width   = $this->maniaControl->settingManager->getSetting($this, self::SETTING_WIDGET_WIDTH);
		$height  = $this->maniaControl->settingManager->getSetting($this, self::SETTING_WIDGET_HEIGHT);
		$maxTime = $this->maniaControl->settingManager->getSetting($this, self::SETTING_VOTE_TIME);

		$quadStyle    = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadStyle();
		$quadSubstyle = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadSubstyle();
		$labelStyle   = $this->maniaControl->manialinkManager->styleManager->getDefaultLabelStyle();

		$maniaLink = new ManiaLink(self::MLID_WIDGET);

		//$script    = new Script();
		//$maniaLink->setScript($script);

		// mainframe
		$frame = new Frame();
		$maniaLink->add($frame);
		$frame->setSize($width, $height);
		$frame->setPosition($pos_x, $pos_y);

		// Background Quad
		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setSize($width, $height);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);

		/*$keyQuad = new Quad();
		$frame->add($keyQuad);
		$keyQuad->setPosition(500,500);
		$keyQuad->setAction(382009003);*/


		$label = new Label_Text();
		$frame->add($label);
		$label->setY($height / 2 - 4);
		$label->setAlign(Control::CENTER, Control::CENTER);
		$label->setSize($width - 5, $height);
		$label->setTextSize(1.3);
		$label->setText('Vote for ' . $this->currentVote["Name"]);
		//$label->setTextColor("900");

		/*$label = new Label_Text();
		$frame->add($label);
		$label->setY($height / 2 - 7);
		$label->setAlign(Control::CENTER, Control::CENTER);
		$label->setSize($width - 5, $height);
		$label->setTextSize(1.3);
		$label->setText($this->currentVote);
		$label->setTextColor("F00");*/

		//Started by nick
		$label = new Label_Text();
		$frame->add($label);
		$label->setY($height / 2 - 7);
		$label->setAlign(Control::CENTER, Control::CENTER);
		$label->setSize($width - 5, 2);
		$label->setTextSize(1);
		$label->setTextColor("F80");
		$label->setText("Started by " . $this->voter->nickname);


		//Time Gaunge
		$timeGauge = new Gauge();
		$frame->add($timeGauge);
		$timeGauge->setY(0);
		$timeGauge->setSize($width * 0.95, 6);
		$timeGauge->setDrawBg(false);
		$timeGaugeRatio = (100 / $maxTime * $timeUntilExpire) / 100;
		$timeGauge->setRatio($timeGaugeRatio + 0.15 - $timeGaugeRatio * 0.15);
		$gaugeColor = ColorUtil::floatToStatusColor($timeGaugeRatio);
		$timeGauge->setColor($gaugeColor . '9');

		//Time Left
		$label = new Label_Text();
		$frame->add($label);
		$label->setY(-2);
		$label->setAlign(Control::CENTER, Control::CENTER);
		$label->setSize($width - 5, $height);
		$label->setTextSize(1.3);
		$label->setText("Time left: " . $timeUntilExpire . "s");
		$label->setTextColor("FFF");

		//Vote Gauge
		$voteGauge = new Gauge();
		$frame->add($voteGauge);
		$voteGauge->setY($height / 2 - 20);
		$voteGauge->setSize($width * 0.65, 12);
		$voteGauge->setDrawBg(false);
		$voteGauge->setRatio($votePercentage + 0.15 - $votePercentage * 0.15);
		$gaugeColor = ColorUtil::floatToStatusColor($votePercentage);
		$voteGauge->setColor($gaugeColor . '9');


		$voteLabel = new Label();
		$frame->add($voteLabel);
		$voteLabel->setY($height / 2 - 20.4);
		$voteLabel->setSize($width * 0.65, 12);
		$voteLabel->setStyle($labelStyle);
		$voteLabel->setTextSize(1);
		$voteLabel->setText('  ' . round($votePercentage * 100.) . '% (' . count($this->playersVoted) . ')');

		// Mute Player
		$y    = $height / 2 - 20.4;
		$quad = new Quad_BgsPlayerCard();
		$frame->add($quad);
		$quad->setX(-$width / 2 + 4);
		$quad->setY($y);
		$quad->setSubStyle($quad::SUBSTYLE_BgPlayerCardBig);
		$quad->setSize(5, 5);
		$quad->setAction(self::ACTION_NEGATIVE_VOTE);

		$label = new Label_Button();
		$frame->add($label);
		$label->setX(-$width / 2 + 4);
		$label->setAlign(Control::CENTER, Control::CENTER);
		$label->setY($y);
		$label->setStyle($labelStyle);
		$label->setTextSize(1);
		$label->setSize(3, 3);
		$label->setTextColor("F00");
		$label->setText("F1");

		$quad = clone $quad;
		$frame->add($quad);
		$quad->setX($width / 2 - 4);
		$quad->setAction(self::ACTION_POSITIVE_VOTE);

		$label = clone $label;
		$frame->add($label);
		$label->setX($width / 2 - 4);
		$label->setTextColor("0F0");
		$label->setText("F2");

		// Send manialink
		$manialinkText = $maniaLink->render()->saveXML();
		$this->maniaControl->manialinkManager->sendManialink($manialinkText);
	}

	/**
	 * Shows the Icon Widget
	 *
	 * @param bool $login
	 */
	private function showIcon($login = false) {
		$posX              = $this->maniaControl->settingManager->getSetting($this, self::SETTING_VOTE_ICON_POSX);
		$posY              = $this->maniaControl->settingManager->getSetting($this, self::SETTING_VOTE_ICON_POSY);
		$width             = $this->maniaControl->settingManager->getSetting($this, self::SETTING_VOTE_ICON_WIDTH);
		$height            = $this->maniaControl->settingManager->getSetting($this, self::SETTING_VOTE_ICON_HEIGHT);
		$quadStyle         = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadStyle();
		$quadSubstyle      = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadSubstyle();
		$itemMarginFactorX = 1.3;
		$itemMarginFactorY = 1.2;

		$itemSize = $width;

		$maniaLink = new ManiaLink(self::MLID_ICON);
		$script    = $maniaLink->getScript();

		//Custom Vote Menu Iconsframe
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
		$itemQuad = new Quad_Icons64x64_1();
		$itemQuad->setSubStyle($itemQuad::SUBSTYLE_StateSuggested);
		$itemQuad->setSize($itemSize, $itemSize);
		$iconFrame->add($itemQuad);

		//Define Description Label
		$menuEntries      = 4;
		$descriptionFrame = new Frame();
		$maniaLink->add($descriptionFrame);
		$descriptionFrame->setPosition($posX - $menuEntries * $itemSize * 1.15 - 6, $posY);

		$descriptionLabel = new Label();
		$descriptionFrame->add($descriptionLabel);
		$descriptionLabel->setAlign(Control::RIGHT, Control::TOP);
		$descriptionLabel->setSize(40, 4);
		$descriptionLabel->setTextSize(1.4);
		$descriptionLabel->setTextColor('fff');

		//Popout Frame
		$popoutFrame = new Frame();
		$maniaLink->add($popoutFrame);
		$popoutFrame->setPosition($posX - $itemSize * 0.5, $posY);
		$popoutFrame->setHAlign(Control::RIGHT);
		$popoutFrame->setSize(4 * $itemSize * $itemMarginFactorX, $itemSize * $itemMarginFactorY);

		$backgroundQuad = new Quad();
		$popoutFrame->add($backgroundQuad);
		$backgroundQuad->setHAlign(Control::RIGHT);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);
		$backgroundQuad->setSize($menuEntries * $itemSize * 1.15 + 2, $itemSize * $itemMarginFactorY);

		$script->addToggle($itemQuad, $popoutFrame);

		//Menu Items
		$x = -1;

		//TODO build dynamically
		//Vote Balance Teams
		$itemQuad = new Quad_Icons128x32_1();
		$popoutFrame->add($itemQuad);
		$itemQuad->setSubStyle($itemQuad::SUBSTYLE_RT_Team);
		$itemQuad->setAction(self::ACTION_START_VOTE . 'teambalance');
		$itemQuad->setSize($itemSize, $itemSize);
		$itemQuad->setX($x);
		$itemQuad->setHAlign(Control::RIGHT);
		$description = '$s' . 'Vote for Team-Balance';
		$script->addTooltip($itemQuad, $descriptionLabel, array(Script::OPTION_TOOLTIP_TEXT => $description));
		$x -= $itemSize * 1.05;

		// Vote SkipMap
		$itemQuad = new Quad_Icons64x64_1();
		$popoutFrame->add($itemQuad);
		$itemQuad->setSubStyle($itemQuad::SUBSTYLE_ArrowFastNext);
		$itemQuad->setAction(self::ACTION_START_VOTE . 'skipmap');
		$itemQuad->setSize($itemSize, $itemSize);
		$itemQuad->setX($x);
		$itemQuad->setHAlign(Control::RIGHT);
		$description = '$s' . 'Vote for Skip-Map';
		$script->addTooltip($itemQuad, $descriptionLabel, array(Script::OPTION_TOOLTIP_TEXT => $description));
		$x -= $itemSize * 1.05;

		// Set Pause
		$itemQuad = new Quad_Icons128x32_1(); //TODO check if mode supports it
		$itemQuad->setSubStyle($itemQuad::SUBSTYLE_ManiaLinkSwitch);
		$popoutFrame->add($itemQuad);
		$itemQuad->setAction(self::ACTION_START_VOTE . 'pausegame');
		$itemQuad->setSize($itemSize, $itemSize);
		$itemQuad->setX($x);
		$itemQuad->setHAlign(Control::RIGHT);
		$description = '$s' . 'Vote for a pause of Current Game';
		$script->addTooltip($itemQuad, $descriptionLabel, array(Script::OPTION_TOOLTIP_TEXT => $description));
		$x -= $itemSize * 1.05;


		// Vote RestartMap
		$itemQuad = new Quad_UIConstruction_Buttons();
		$popoutFrame->add($itemQuad);
		$itemQuad->setSubStyle($itemQuad::SUBSTYLE_Reload);
		$itemQuad->setAction(self::ACTION_START_VOTE . 'restartmap');
		$itemQuad->setSize($itemSize, $itemSize);
		$itemQuad->setX($x);
		$itemQuad->setHAlign(Control::RIGHT);
		$description = '$s' . 'Vote for Restart-Map';
		$script->addTooltip($itemQuad, $descriptionLabel, array(Script::OPTION_TOOLTIP_TEXT => $description));
		//$x -= $itemSize * 1.05;

		// Send manialink
		$manialinkText = $maniaLink->render()->saveXML();
		$this->maniaControl->manialinkManager->sendManialink($manialinkText, $login);
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