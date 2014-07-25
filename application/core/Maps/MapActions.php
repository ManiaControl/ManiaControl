<?php

namespace ManiaControl\Maps;

use ManiaControl\ManiaControl;
use Maniaplanet\DedicatedServer\Xmlrpc\ChangeInProgressException;

/**
 * ManiaControl Map Actions Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class MapActions {
	/*
	 * Private properties
	 */
	private $maniaControl = null;

	/**
	 * Construct a map actions instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
	}

	/**
	 * Skip the current Map
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
