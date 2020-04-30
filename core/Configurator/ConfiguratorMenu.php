<?php

namespace ManiaControl\Configurator;

use FML\Script\Script;
use ManiaControl\Players\Player;

/**
 * Interface for Configurator Menus
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface ConfiguratorMenu {

	/**
	 * Get the menu title
	 *
	 * @return string
	 */
	public static function getTitle();

	/**
	 * Get the configurator menu frame
	 *
	 * @param float  $width
	 * @param float  $height
	 * @param Script $script
	 * @param Player $player
	 * @return \FML\Controls\Frame
	 */
	public function getMenu($width, $height, Script $script, Player $player);

	/**
	 * Save the config data
	 *
	 * @param array  $configData
	 * @param Player $player
	 */
	public function saveConfigData(array $configData, Player $player);
}
