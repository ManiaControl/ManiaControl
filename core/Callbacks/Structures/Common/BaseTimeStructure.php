<?php

namespace ManiaControl\Callbacks\Structures\Common;

use ManiaControl\ManiaControl;

/**
 * Base Structure Class for all Callbacks using a Timestamp
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class BaseTimeStructure extends BaseStructure {
	protected $time;

	/**
	 * BaseResponseStructure constructor.
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 * @param                            $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$this->time = $this->getPlainJsonObject()->time;
	}

	/**
	 * Gets the Server Time the Callback was sent
	 *
	 * @api
	 * @return int Time since Serverstart
	 */
	public function getTime() {
		return $this->time;
	}
}