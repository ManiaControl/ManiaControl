<?php

namespace ManiaControl\Configurators;

use FML\Script\Pages;
use FML\Script\Script;
use FML\Script\Tooltips;
use ManiaControl\Players\Player;

/**
 * Interface for configurator menus
 *
 * @author steeffeen & kremsy
 */
interface ConfiguratorMenu {

	/**
	 * Get the menu title
	 *
	 * @return string
	 */
	public function getTitle();

	/**
	 * Get the configurator menu
	 *
	 * @param float              $width
	 * @param float              $height
	 * @param               $pages temp removed
	 * @param \FML\Script\Script $script
	 * @internal param \FML\Script\Tooltips $tooltips
	 * @return \FML\Controls\Control
	 */
	public function getMenu($width, $height, $pages, Script $script);

	/**
	 * Save the config data
	 * 
	 * @param array $configData        	
	 * @param Player $player        	
	 */
	public function saveConfigData(array $configData, Player $player);
}
