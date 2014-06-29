<?php

namespace ManiaControl\Maps;

use FML\ManiaLink;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Players\Player;

/**
 * Maps Directory Browser
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class DirectoryBrowser {
	/*
	 * Private properties
	 */
	private $maniaControl = null;

	/**
	 * Create a new Directory Browser Instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
	}

	/**
	 * Build and show the Browser ManiaLink to the given Player
	 *
	 * @param Player $player
	 */
	public function showManiaLink(Player $player) {
		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);

		$this->maniaControl->manialinkManager->sendManialink($maniaLink, $player);
	}
}
