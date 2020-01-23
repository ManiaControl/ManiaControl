<?php

namespace ManiaControl\Callbacks\Structures\TrackMania;


use ManiaControl\Callbacks\Structures\Common\BaseStructure;
use ManiaControl\ManiaControl;

/**
 * Structure Class for the On Warmup Start and End Round Callback Structure
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnWarmupStartEndRoundStructure extends BaseStructure {
	private $current;
	private $total;

	/**
	 * OnWayPointEventStructure constructor.
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 * @param                            $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$this->current = (int) $this->getPlainJsonObject()->current;
		$this->total   = (int) $this->getPlainJsonObject()->total;
	}

	/**
	 * Gets the number of the current round
	 *
	 * @api
	 * @return int
	 */
	public function getCurrent() {
		return $this->current;
	}

	/**
	 * Gets the number of the warmup rounds
	 *
	 * @api
	 * @return int
	 */
	public function getTotal() {
		return $this->total;
	}
}