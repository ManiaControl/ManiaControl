<?php

namespace ManiaControl\Server;

use ManiaControl\ManiaControl;

/**
 * Class offering Operations for the Server Directory
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Directory {
	/**
	 * Private Properties
	 */
	private $maniaControl = null;

	/**
	 * Create new Server Directory Object
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
	}

	/**
	 * Retrieve the Maps Folder Path
	 *
	 * @return string
	 */
	public function getMapsFolder() {
		return $this->maniaControl->client->getMapsDirectory();
	}

	/**
	 * Retrieve the Skins Folder Path
	 *
	 * @return string
	 */
	public function getSkinsFolder() {
		return $this->maniaControl->client->getSkinsDirectory();
	}

	/**
	 * Retrieve the Logs Folder Path
	 *
	 * @return string
	 */
	public function getLogsFolder() {
		return $this->getGameDataFolder() . '..' . DIRECTORY_SEPARATOR . 'Logs' . DIRECTORY_SEPARATOR;
	}

	/**
	 * Retrieve the Game Data Folder Path
	 *
	 * @return string
	 */
	public function getGameDataFolder() {
		return $this->maniaControl->client->gameDataDirectory();
	}

	/**
	 * Retrieve the Cache Folder Path
	 *
	 * @return string
	 */
	public function getCacheFolder() {
		return $this->getGameDataFolder() . '..' . DIRECTORY_SEPARATOR . 'CommonData' . DIRECTORY_SEPARATOR . 'Cache' . DIRECTORY_SEPARATOR;
	}
}
