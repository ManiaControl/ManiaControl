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
use FML\ManiaLink;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\ColorUtil;
use ManiaControl\Commands\CommandListener;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Players\Player;


/**
 * ManiaControl Chat-Message Plugin
 *
 * @author kremsy and steeffeen
 */
class CustomVotesPlugin implements CommandListener, CallbackListener, ManialinkPageAnswerListener, Plugin {
	/**
	 * Constants
	 */
	const PLUGIN_ID      = 9;
	const PLUGIN_VERSION = 0.1;
	const PLUGIN_NAME    = 'CustomVotesPlugin';
	const PLUGIN_AUTHOR  = 'kremsy and steeffeen';

	const SETTING_WIDGET_POSX                = 'Widget-Position: X';
	const SETTING_WIDGET_POSY                = 'Widget-Position: Y';
	const SETTING_WIDGET_WIDTH               = 'Widget-Size: Width';
	const SETTING_WIDGET_HEIGHT              = 'Widget-Size: Height';
	const SETTING_VOTE_TIME                  = 'Voting Time';
	const SETTING_DEFAULT_RATIO              = 'Default Success Ratio';
	const SETTING_SPECTATOR_ALLOW_VOTE       = 'Allow Specators to vote';
	const SETTING_SPECTATOR_ALLOW_START_VOTE = 'Allow Specators to start a vote';

	const MLID_WIDGET = 'CustomVotesPlugin.WidgetId';

	const VOTE_FOR_ACTION     = '1';
	const VOTE_AGAINST_ACTION = '-1';

	const ACTION_POSITIVE_VOTE = 'CustomVotesPlugin.PositivVote';
	const ACTION_NEGATIVE_VOTE = 'CustomVotesPlugin.NegativeVote';

	/**
	 * Private properties
	 */

	/**
	 *
	 * @var maniaControl $maniaControl
	 */
	private $maniaControl = null;
	private $voteCommands = array();
	private $currentVote = '';
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

		$this->defineVote("bal");

		$this->maniaControl->commandManager->registerCommandListener('vote', $this, 'chat_vote');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_1_SECOND, $this, 'handle1Second');
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_POSITIVE_VOTE, $this, 'handlePositiveVote');
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_NEGATIVE_VOTE, $this, 'handleNegativeVote');

		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 'handleManialinkPageAnswer');


		$this->maniaControl->settingManager->initSetting($this, self::SETTING_WIDGET_POSX, 160 - 42 - 15);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_WIDGET_POSY, 90 - 2 - 15);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_WIDGET_WIDTH, 30);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_WIDGET_HEIGHT, 25);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_DEFAULT_RATIO, 0.65);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_SPECTATOR_ALLOW_VOTE, false);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_SPECTATOR_ALLOW_START_VOTE, false);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_VOTE_TIME, 60);

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
	 * Chat Vote
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function chat_vote(array $chat, Player $player) {
		$command = explode(" ", $chat[1][2]);
		if(isset($command[1])) {

			$this->startVote($player, strtolower($command[1]));
		}
	}


	public function handleManialinkPageAnswer(array $callback){
		var_dump($callback);
	}
	/**
	 * Defines a Vote
	 *
	 * @param $voteName
	 */
	public function defineVote($voteName, $neededRatio = 0.65) {
		$this->voteCommands[strtolower($voteName)] = $voteName;
	}

	/**
	 * Starts a vote
	 *
	 * @param \ManiaControl\Players\Player $player
	 * @param                              $voteName
	 */
	public function startVote(Player $player, $voteName) {
		if($this->maniaControl->playerManager->playerActions->isPlayerMuted($player)) {
			return;
		}

		if($player->isSpectator && !$this->maniaControl->settingManager->getSetting($this, self::SETTING_SPECTATOR_ALLOW_START_VOTE)) {
			return;
		}

		$this->maniaControl->chat->sendChat("Vote started");
		$this->currentVote           = $voteName;
		$this->currentVoteExpireTime = time() + 60; //TODO as setting

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
		if($this->currentVote == '') {
			return;
		}

		$votePercentage = $this->playersVotedPositiv / count($this->playersVoted);

		$timeUntilExpire = $this->currentVoteExpireTime - time();
		$this->showVoteWidget($timeUntilExpire, $votePercentage);

		if($timeUntilExpire <= 0) {
			$this->maniaControl->chat->sendChat("Vote finished");
			$this->currentVote = '';

			$emptyManialink = new ManiaLink(self::MLID_WIDGET);
			$manialinkText  = $emptyManialink->render()->saveXML();
			$this->maniaControl->manialinkManager->sendManialink($manialinkText);

			$voter = null;
		}
	}

	private function showVoteWidget($timeUntilExpire, $votePercentage) {
		$pos_x  = $this->maniaControl->settingManager->getSetting($this, self::SETTING_WIDGET_POSX);
		$pos_y  = $this->maniaControl->settingManager->getSetting($this, self::SETTING_WIDGET_POSY);
		$width  = $this->maniaControl->settingManager->getSetting($this, self::SETTING_WIDGET_WIDTH);
		$height = $this->maniaControl->settingManager->getSetting($this, self::SETTING_WIDGET_HEIGHT);

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
		$label->setText('Vote for ' . $this->currentVote);
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
		$maxTime        = 60; //TODO set maxtime
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