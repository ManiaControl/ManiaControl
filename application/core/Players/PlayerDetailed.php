<?php

namespace ManiaControl\Players;
use FML\Controls\Control;
use FML\Controls\Frame;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\ManiaLink;
use FML\Script\Script;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkManager;

/**
 * Player Detailed Page
 *
 * @author steeffeen & kremsy
 */
class PlayerDetailed {


	/**
	 * Private properties
	 */
	private $maniaControl = null;

	/**
	 * Create a new Player Detailed instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

	/*	$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_CLOSE_PLAYER_ADV, $this, 'closePlayerAdvancedWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(ManialinkManager::CB_MAIN_WINDOW_CLOSED, $this, 'closeWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 'handleManialinkPageAnswer');

		// Update Widget Events
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERINFOCHANGED, $this, 'updateWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERDISCONNECTED, $this, 'updateWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERJOINED, $this, 'updateWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(AuthenticationManager::CB_AUTH_LEVEL_CHANGED, $this, 'updateWidget'); */

		// settings
		$this->width        = $this->maniaControl->manialinkManager->styleManager->getListWidgetsWidth();
		$this->height       = $this->maniaControl->manialinkManager->styleManager->getListWidgetsHeight();
		$this->quadStyle    = $this->maniaControl->manialinkManager->styleManager->getDefaultMainWindowStyle();
		$this->quadSubstyle = $this->maniaControl->manialinkManager->styleManager->getDefaultMainWindowSubStyle();

	}


	public function showPlayerDetailed(Player $player, $targetLogin) {
		$target = $this->maniaControl->playerManager->getPlayer($targetLogin);
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


		$y = $this->height / 2 - 10;
		//Nickname
		$label = new Label_Text();
		$frame->add($label);
		$label->setPosition(-$this->width / 2 + 10, $y);
		$label->setText($target->nickname);
		$label->setHAlign(Control::LEFT);


		//Define MainLabel (Login)
		$y -= 5;
		$mainLabel = new Label_Text();
		$frame->add($mainLabel);
		$mainLabel->setPosition(-$this->width / 2 + 10, $y);
		$mainLabel->setTextSize(1.2);
		$mainLabel->setHAlign(Control::LEFT);
		$mainLabel->setText("Login:");

		$y -= 5;
		$label = clone $mainLabel;
		$frame->add($label);
		$label->setY($y);
		$label->setText("Nation: ");

		$y -= 5;
		$label = clone $mainLabel;
		$frame->add($label);
		$label->setY($y);
		$label->setText("Province:");

		$y -= 5;
		$label = clone $mainLabel;
		$frame->add($label);
		$label->setY($y);
		$label->setText("Authorization:");

		$y -= 5;
		$label = clone $mainLabel;
		$frame->add($label);
		$label->setY($y);
		$label->setText("Ladder Rank:");

		$y -= 5;
		$label = clone $mainLabel;
		$frame->add($label);
		$label->setY($y);
		$label->setText("Ladder Score:");

		$y -= 5;
		$label = clone $mainLabel;
		$frame->add($label);
		$label->setY($y);
		$label->setText("Plays since:");

		//Login
		$y = $this->height / 2 - 15;
		$mainLabel = new Label_Text();
		$frame->add($mainLabel);
		$mainLabel->setPosition(-$this->width / 2 + 30, $y);
		$mainLabel->setText($target->login);
		$mainLabel->setTextSize(1.2);
		$mainLabel->setHAlign(Control::LEFT);

		//Country
		$y -= 5;
		$label = clone $mainLabel;
		$frame->add($label);
		$label->setY($y);
		$label->setText($target->getCountry());

		//Province
		$y -= 5;
		$label = clone $mainLabel;
		$frame->add($label);
		$label->setY($y);
		$label->setText($target->getProvince());

		//AuthLevel
		$y -= 5;
		$label = clone $mainLabel;
		$frame->add($label);
		$label->setY($y);
		$label->setText($this->maniaControl->authenticationManager->getAuthLevelName($target->authLevel));

		//LadderRank
		$y -= 5;
		$label = clone $mainLabel;
		$frame->add($label);
		$label->setY($y);
		$label->setText($target->ladderRank);

		//LadderScore
		$y -= 5;
		$label = clone $mainLabel;
		$frame->add($label);
		$label->setY($y);
		$label->setText(round($target->ladderScore,2));

		//Played Since
		$y -= 5;
		$label = clone $mainLabel;
		$frame->add($label);
		$label->setY($y);
		$label->setText(date("Y-m-d", time() - 3600 * 24 * $target->maniaPlanetPlayDays));

		//Avatar
		$label = new Label_Text();
		$frame->add($label);
		$label->setPosition($this->width / 2 - 10, $this->height / 2 - 10);
		$label->setText("Avatar");
		$label->setTextSize(1.3);
		$label->setHAlign(Control::RIGHT);

		$quad = new Quad();
		$frame->add($quad);
		$quad->setImage('file://' . $target->avatar);
		$quad->setPosition($this->width / 2 - 10, $this->height / 2 - 10);
		$quad->setAlign(Control::RIGHT, Control::TOP);
		$quad->setSize(20, 20);

		//Statistics
		$frame->add($this->statisticsFrame($target));


		// render and display xml
		$this->maniaControl->manialinkManager->displayWidget($maniaLink, $player);
	}

	public function statisticsFrame($player){
		$frame = new Frame();

		/*$mainLabel = new Label_Text();
		$frame->add($mainLabel);
		$mainLabel->setPosition(-$this->width / 2 + 50, $this->height / 2 - 10);
		$mainLabel->setTextSize(1.2);
		$mainLabel->setHAlign(Control::LEFT);
		$mainLabel->setText("Statistics");*/

		$playerStats = $this->maniaControl->statisticManager->getAllPlayerStats($player);

		$y = $this->height / 2 - 15;
		foreach($playerStats as $stat){
			$statProperties = $stat[0];
			$value = $stat[1];

			$label = new Label_Text();
			$frame->add($label);
			$label->setPosition(-$this->width / 2 + 70, $y);
			$label->setText($statProperties->name);
			$label->setHAlign(Control::LEFT);
			$label->setTextSize(1.5);

			$label = new Label_Text();
			$frame->add($label);
			$label->setPosition(-$this->width / 2 + 100, $y);
			$label->setText($value);
			$label->setHAlign(Control::LEFT);
			$label->setTextSize(1.5);

			$y -= 4;
		}
		return $frame;
	}
} 