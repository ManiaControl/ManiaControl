<?php

namespace ManiaControl\Callbacks\Structures\ShootMania;


use ManiaControl\Callbacks\Structures\Common\BasePlayerTimeStructure;
use ManiaControl\ManiaControl;

/**
 * Structure Class for the OnPlayerTriggersSector Structure Callback
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnPlayerTriggersSectorStructure extends BasePlayerTimeStructure {
	private $sectorId;

	/**
	 * OnPlayerTriggersSectorStructure constructor.
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 * @param                            $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$this->sectorId = $this->getPlainJsonObject()->sectorid;

	}

	/**
	 * < Id of the triggered sector
	 *
	 * @api
	 * @return string
	 */
	public function getSectorId() {
		return $this->sectorId;
	}


}