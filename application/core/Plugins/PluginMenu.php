<?php

namespace ManiaControl\Plugins;

use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use ManiaControl\Configurators\ConfiguratorMenu;
use FML\Script\Pages;
use FML\Script\Tooltips;
use FML\Controls\Frame;

/**
 * Configurator for enabling and disabling plugins
 *
 * @author steeffeen
 */
class PluginMenu implements ConfiguratorMenu {
	/**
	 * Private properties
	 */
	private $maniaControl = null;

	/**
	 * Create a new plugin menu instance
	 *
	 * @param ManiaControl $maniaControl        	
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
	}

	/**
	 *
	 * @see \ManiaControl\Configurators\ConfiguratorMenu::getTitle()
	 */
	public function getTitle() {
		return "Plugins";
	}

	/**
	 *
	 * @see \ManiaControl\Configurators\ConfiguratorMenu::getMenu()
	 */
	public function getMenu($width, $height, Pages $pages, Tooltips $tooltips) {
		$frame = new Frame();
		return $frame;
	}

	/**
	 *
	 * @see \ManiaControl\Configurators\ConfiguratorMenu::saveConfigData()
	 */
	public function saveConfigData(array $configData, Player $player) {
	}
}

?>
