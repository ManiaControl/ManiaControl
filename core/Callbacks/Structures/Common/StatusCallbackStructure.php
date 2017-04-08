<?php

namespace ManiaControl\Callbacks\Structures\Common;


use ManiaControl\Callbacks\Structures\Common\BaseResponseStructure;
use ManiaControl\ManiaControl;

/**
 * Structure Class for the StatusCallback Structure Callback
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class StatusCallbackStructure extends BaseResponseStructure {
	protected $active;

	/**
	 * Construct a new On Hit Structure
	 *
	 * @param ManiaControl $maniaControl
	 * @param array        $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$this->active = $this->getPlainJsonObject()->active;
	}

	/**
	 * True if the Status (Like Combo Pause or Warmup) is Ongoing
	 *
	 * @api
	 * @return boolean
	 */
	public function getActive() {
		return $this->active;
	}

}