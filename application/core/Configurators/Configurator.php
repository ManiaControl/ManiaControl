<?php

namespace ManiaControl\Configurators;

use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Players\Player;
use FML\Controls\Quad;

/**
 * Class managing ingame ManiaControl configuration
 *
 * @author steeffeen & kremsy
 */
class Configurator implements ManialinkPageAnswerListener {
	/**
	 * Constants
	 */
	const ACTION_CONFIG = 'Configurator.ConfigAction';
	
	/**
	 * Private properties
	 */
	private $maniaControl = null;

	/**
	 * Create a new Configurator
	 *
	 * @param ManiaControl $maniaControl        	
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->addAdminMenuItem();
		
		// Register for page answer
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_CONFIG, $this, 'handlePageAnswer');
	}

	/**
	 * Handle PageAnswer callback
	 *
	 * @param array $callback        	
	 * @param Player $player        	
	 */
	public function handlePageAnswer(array $callback, Player $player) {
	}

	/**
	 * Add menu item to the admin menu
	 */
	private function addAdminMenuItem() {
		$itemQuad = new Quad();
		$itemQuad->setStyles('Icons128x32_1', 'Settings');
		$itemQuad->setAction(self::ACTION_CONFIG);
		
		$this->maniaControl->adminMenu->addMenuItem($itemQuad, 5);
	}
}

?>
