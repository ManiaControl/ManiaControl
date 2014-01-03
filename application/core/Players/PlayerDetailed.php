<?php

namespace ManiaControl\Players;
use FML\Controls\Frame;
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
		var_dump($player);
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

		// render and display xml
		$this->maniaControl->manialinkManager->displayWidget($maniaLink, $player);
	}
} 