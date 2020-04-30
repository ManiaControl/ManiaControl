<?php

namespace ManiaControl\Callbacks\Structures\ShootMania;

use ManiaControl\Callbacks\Structures\Common\BaseResponseStructure;
use ManiaControl\ManiaControl;

/**
 * Structure Class for the AFK Properties Callback
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnAFKPropertiesStructure extends BaseResponseStructure {
	protected $idleTimeLimit;
	protected $spawnTimeLimit;
	protected $checkInterval;
	protected $forceSpec;

	/**
	 * Construct a new On Hit Structure
	 *
	 * @param ManiaControl $maniaControl
	 * @param array        $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$jsonObj              = $this->getPlainJsonObject();
		$this->idleTimeLimit  = $jsonObj->idletimelimit;
		$this->spawnTimelimit = $jsonObj->spawntimelimit;
		$this->checkInterval  = $jsonObj->checkinterval;
		$this->forceSpec      = $jsonObj->forcespec;
	}

	/**
	 * Time after which a player is considered to be AFK (ms)
	 *
	 * @api
	 * @return int
	 */
	public function getIdleTimeLimit() {
		return (int) $this->idleTimeLimit;
	}

	/**
	 * Time after spawn before which a player can't be considered to be
	 *
	 * @return int
	 */
	public function getSpawnTimeLimit() {
		return (int) $this->spawnTimeLimit;
	}

	/**
	 * Time between each AFK check (ms)
	 *
	 * @return int
	 */
	public function getCheckInterval() {
		return (int) $this->checkInterval;
	}

	/**
	 * Let the library force the AFK player into spectator mode
	 *
	 * @return bool
	 */
	public function getForceSpec() {
		return $this->forceSpec;
	}
}