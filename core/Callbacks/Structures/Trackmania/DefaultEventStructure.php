<?php

namespace ManiaControl\Callbacks\Structures\Trackmania;


use ManiaControl\Callbacks\Structures\BaseStructure;
use ManiaControl\ManiaControl;

/**
 * Structure Class for the Default Event Structure Callback
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class DefaultEventStructure extends BaseStructure {
	public $time;
	public $type;

	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$this->time = $this->getPlainJsonObject()->time;
		$this->type = $this->getPlainJsonObject()->type;
	}

	/**
	 * @return int
	 */
	public function getTime() {
		return $this->time;
	}

	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}
}