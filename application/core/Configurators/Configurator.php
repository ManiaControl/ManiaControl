<?php

namespace ManiaControl\Configurators;

use ManiaControl\ManiaControl;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Players\Player;
use FML\ManiaLink;
use FML\Controls\Quad;
use FML\Controls\Frame;
use FML\Controls\Quads\Quad_UiSMSpectatorScoreBig;
use FML\Controls\Quads\Quad_EnergyBar;
use FML\Controls\Quads\Quad_BgsPlayerCard;
use FML\Controls\Quads\Quad_BgRaceScore2;

/**
 * Class managing ingame ManiaControl configuration
 *
 * @author steeffeen & kremsy
 */
class Configurator implements CallbackListener, ManialinkPageAnswerListener {
	/**
	 * Constants
	 */
	const MLID_MENU = 'Configurator.Menu.MLID';
	const ACTION_TOGGLEMENU = 'Configurator.ToggleMenuAction';
	
	/**
	 * Private properties
	 */
	private $maniaControl = null;
	private $playersMenuShown = array();
	private $manialink = null;
	private $emptyManialink = null;

	/**
	 * Create a new Configurator
	 *
	 * @param ManiaControl $maniaControl        	
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->addAdminMenuItem();
		
		// Register for page answers
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_TOGGLEMENU, $this, 
				'handleToggleMenuAction');
		
		// Register for callbacks
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERDISCONNECT, $this, 
				'handlePlayerDisconnect');
	}

	/**
	 * Handle toggle menu action
	 *
	 * @param array $callback        	
	 * @param Player $player        	
	 */
	public function handleToggleMenuAction(array $callback, Player $player) {
		if (isset($this->playersMenuShown[$player->login])) {
			$this->hideMenu($player);
			return;
		}
		$this->showMenu($player);
	}

	/**
	 * Handle PlayerDisconnect callback
	 *
	 * @param array $callback        	
	 */
	public function handlePlayerDisconnect(array $callback) {
		$login = $callback[1][0];
		if (isset($this->playersMenuShown[$login])) {
			unset($this->playersMenuShown[$login]);
		}
	}

	/**
	 * Show the menu to the player
	 *
	 * @param Player $player        	
	 */
	private function showMenu(Player $player) {
		$this->buildManialink();
		$manialinkText = $this->manialink->render()->saveXML();
		$this->maniaControl->manialinkManager->sendManialink($manialinkText, $player->login);
		$this->maniaControl->manialinkManager->disableAltMenu($player);
		$this->playersMenuShown[$player->login] = true;
	}

	/**
	 * Hide the menu for the player
	 *
	 * @param Player $player        	
	 */
	private function hideMenu(Player $player) {
		$this->buildManialink();
		$manialinkText = $this->emptyManialink->render()->saveXML();
		$this->maniaControl->manialinkManager->sendManialink($manialinkText, $player->login);
		$this->maniaControl->manialinkManager->enableAltMenu($player);
		unset($this->playersMenuShown[$player->login]);
	}

	/**
	 * Build menu manialink if necessary
	 *
	 * @param bool $forceBuild        	
	 */
	private function buildManialink($forceBuild = false) {
		if (is_object($this->manialink) && !$forceBuild) return;
		
		$this->emptyManialink = new ManiaLink(self::MLID_MENU);
		
		$manialink = new ManiaLink(self::MLID_MENU);
		
		$frame = new Frame();
		$manialink->add($frame);
		
		$backgroundQuad = new Quad_BgRaceScore2();
		$frame->add($backgroundQuad);
		$backgroundQuad->setSize(100, 70);
		$backgroundQuad->setSubStyle($backgroundQuad::SUBSTYLE_HandleSelectable);
		
		$this->manialink = $manialink;
	}

	/**
	 * Add menu item to the admin menu
	 */
	private function addAdminMenuItem() {
		$itemQuad = new Quad();
		$itemQuad->setStyles('Icons128x32_1', 'Settings');
		$itemQuad->setAction(self::ACTION_TOGGLEMENU);
		$this->maniaControl->adminMenu->addMenuItem($itemQuad, 5);
	}
}

?>
