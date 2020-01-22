<?php

namespace ManiaControl\Callbacks\Structures\TrackMania;


use ManiaControl\Callbacks\Structures\Common\BaseResponseStructure;
use ManiaControl\ManiaControl;

/**
 * Structure Class for the On Points Repartition Structure Callback
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnPointsRepartitionStructure extends BaseResponseStructure {
	private $pointsRepartition = array();

	/**
	 * OnWayPointEventStructure constructor.
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 * @param                            $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$this->pointsRepartition = $this->getPlainJsonObject()->pointsrepartition;
	}


	/**
	 * @api
	 * @return array
	 */
	public function getPointsRepartition() {
		return $this->pointsRepartition;
	}

}