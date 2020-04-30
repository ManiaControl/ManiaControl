<?php

namespace ManiaControl\Callbacks\Structures\ShootMania;


use ManiaControl\Callbacks\Structures\Common\BasePlayerTimeStructure;
use ManiaControl\ManiaControl;

/**
 * Structure Class for the OnPlayerRequestActionChange Structure Callback
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnPlayerRequestActionChange extends BasePlayerTimeStructure {
	private $actionChange;

	/**
	 * OnPlayerRequestActionChange constructor.
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 * @param                            $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$this->actionChange = $this->getPlainJsonObject()->actionchange;
	}

	/**
	 * < Can be -1 (request previous action) or 1 (request next action)
	 *
	 * @api
	 * @return string
	 */
	public function getActionChange() {
		return $this->actionChange;
	}
}