<?php

namespace ManiaControl\Plugins;

use FML\Controls\Control;
use FML\Controls\Frame;
use FML\Controls\Gauge;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quad;
use FML\ManiaLink;
use FML\Script\Script;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\ColorUtil;
use ManiaControl\Commands\CommandListener;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;


/**
 * ManiaControl Chat-Message Plugin
 *
 * @author kremsy and steeffeen
 */
class CustomVotesPlugin implements CommandListener, CallbackListener, Plugin {
	/**
	 * Constants
	 */
	const PLUGIN_ID      = 9;
	const PLUGIN_VERSION = 0.1;
	const PLUGIN_NAME    = 'CustomVotesPlugin';
	const PLUGIN_AUTHOR  = 'kremsy and steeffeen';

	const MLID_WIDGET = 'CustomVotesPlugin.WidgetId';

	const VOTE_FOR_ACTION = '1';
	const VOTE_AGAINST_ACTION = '-1';

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
		var_dump($command);
		if(isset($command[1])) {

			$this->startVote($player, strtolower($command[1]));
		}
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
	 * @param $player
	 * @param $voteName
	 */
	public function startVote($player, $voteName) {
		if($this->maniaControl->playerManager->playerActions->isPlayerMuted($player)) {
			return;
		}
		$this->maniaControl->chat->sendChat("Vote started");
		$this->currentVote           = $voteName;
		$this->currentVoteExpireTime = time() + 20; //TODO as setting

		$this->playersVoted[$player->login] = self::VOTE_FOR_ACTION;
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

		$timeUntilExpire = $this->currentVoteExpireTime - time();
		$this->showVoteWidget($timeUntilExpire);

		if($timeUntilExpire <= 0) {
			$this->maniaControl->chat->sendChat("Vote finished");
			$this->currentVote = '';

			$emptyManialink = new ManiaLink(self::MLID_WIDGET);
			$manialinkText  = $emptyManialink->render()->saveXML();
			$this->maniaControl->manialinkManager->sendManialink($manialinkText);
		}
	}


	private function showVoteWidget($timeUntilExpire) {
		//$pos_x        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MAP_WIDGET_POSX);
		//$pos_y        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MAP_WIDGET_POSY);
		//$width        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MAP_WIDGET_WIDTH);
		//$height       = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MAP_WIDGET_HEIGHT);
		$pos_x  = 160 - 42 - 15;
		$pos_y  = 90 - 2 - 15;
		$width  = 30;
		$height = 25;

		$quadStyle    = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadStyle();
		$quadSubstyle = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadSubstyle();
		$labelStyle   = $this->maniaControl->manialinkManager->styleManager->getDefaultLabelStyle();

		$maniaLink = new ManiaLink(self::MLID_WIDGET);
		$script    = new Script();
		$maniaLink->setScript($script);

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
		$script->addMapInfoButton($backgroundQuad);

		$label = new Label_Text();
		$frame->add($label);
		$label->setY($height / 2 - 4);
		$label->setAlign(Control::CENTER, Control::CENTER);
		$label->setSize($width - 5, $height);
		$label->setTextSize(1.6);
		$label->setText('Vote');
		$label->setTextColor("900");

		$label = new Label_Text();
		$frame->add($label);
		$label->setY($height / 2 - 8);
		$label->setAlign(Control::CENTER, Control::CENTER);
		$label->setSize($width - 5, $height);
		$label->setTextSize(1.3);
		$label->setText($this->currentVote);
		$label->setTextColor("F00");

		$label = new Label_Text();
		$frame->add($label);
		$label->setY($height / 2 - 10);
		$label->setAlign(Control::CENTER, Control::CENTER);
		$label->setSize($width - 5, $height);
		$label->setTextSize(1.3);
		$label->setText("Time left: " . $timeUntilExpire . "s");
		$label->setTextColor("FFF");

		$voteGauge = new Gauge();
		$frame->add($voteGauge);
		$voteGauge->setSize($width * 0.95, 6);
		$voteGauge->setDrawBg(false);
		$votePerc = 0.5;
		$voteGauge->setRatio($votePerc + 0.15 - $votePerc * 0.15);
		$gaugeColor = ColorUtil::floatToStatusColor($votePerc);
		$voteGauge->setColor($gaugeColor . '9');


		$voteGauge = new Gauge();
		$frame->add($voteGauge);
		$voteGauge->setY($height / 2 - 20);
		$voteGauge->setSize($width * 0.7, 12);
		$voteGauge->setDrawBg(false);
		$votePerc = 0.5;
		$voteGauge->setRatio($votePerc + 0.15 - $votePerc * 0.15);
		$gaugeColor = ColorUtil::floatToStatusColor($votePerc);
		$voteGauge->setColor($gaugeColor . '9');


		/*$karmaLabel = new Label();
		$frame->add($karmaLabel);
		$karmaLabel->setPosition(0, -0.4, 1);
		$karmaLabel->setSize($width * 0.9, $height * 0.9);
		$karmaLabel->setStyle($labelStyle);
		$karmaLabel->setTextSize(1);*/

		/*
					$karmaLabel->setText('  ' . round($karma * 100.) . '% (' . $votes['count'] . ')');
					$karma = 0.;
					$karmaGauge->setRatio(0.);
					$karmaGauge->setColor('00fb');
					$karmaLabel->setText('-');
			*/


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