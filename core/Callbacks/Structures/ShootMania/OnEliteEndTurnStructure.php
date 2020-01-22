<?php

namespace ManiaControl\Callbacks\Structures\ShootMania;


use ManiaControl\Callbacks\Structures\Common\BaseStructure;
use ManiaControl\ManiaControl;

/**
 * Structure Class for the OnEliteEndTurn Structure Callback
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnEliteEndTurnStructure extends BaseStructure {
	protected $victoryType;

	/**
	 * Construct a new On Hit Structure
	 *
	 * @param ManiaControl $maniaControl
	 * @param array        $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$this->victoryType = $this->getPlainJsonObject()->victorytype;
	}

	/**
	 * Gets the Victory Type
	 *
	 * @api
	 * @see \ManiaControl\Callbacks\Structures\ShootMania\Models\VictoryTypes
	 * @return int VictoryType
	 */
	public function getVictoryType() {
		return $this->victoryType;
	}
}