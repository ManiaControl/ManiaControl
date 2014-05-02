<?php

namespace ManiaControl\Configurators;

use FML\Script\Script;
use ManiaControl\Players\Player;

/**
 * Interface for Configurator Menus
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface ConfiguratorMenu {

	/**
	 * Get the Menu Title
	 *
	 * @return string
	 */
	public function getTitle();

	/**
	 * Get the Configurator Menu Frame
	 *
	 * @param float  $width
	 * @param float  $height
	 * @param Script $script
	 * @param Player $player
	 * @return \FML\Controls\Frame
	 */
	public function getMenu($width, $height, Script $script, Player $player);

	/**
	 * Save the Config Data
	 *
	 * @param array  $configData
	 * @param Player $player
	 */
	public function saveConfigData(array $configData, Player $player);
}
