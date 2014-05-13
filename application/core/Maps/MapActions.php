<?php

namespace ManiaControl\Maps;

use ManiaControl\ManiaControl;
use Maniaplanet\DedicatedServer\Xmlrpc\ChangeInProgressException;

/**
 * Map Actions Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class MapActions {
	/*
	 * Private Properties
	 */
	private $maniaControl = null;

	/**
	 * Create a MapActions Instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
	}

	/**
	 * Skips the current Map
	 */
	public function skipMap() {
		//Force a EndMap on the MapQueue to set the next Map
		$this->maniaControl->mapManager->mapQueue->endMap(null);

		//ignore EndMap on MapQueue
		$this->maniaControl->mapManager->mapQueue->dontQueueNextMapChange();

		//Switch The Map
		try {
			$this->maniaControl->client->nextMap();
		} catch (ChangeInProgressException $e) {
		}
	}
} 