<?php

namespace ManiaControl\Callbacks\Structures\ManiaPlanet;


use ManiaControl\Callbacks\Structures\BaseStructure;
use ManiaControl\ManiaControl;

/**
 * Structure Class for the Default Start End Callbacks
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class StartEndStructure extends BaseStructure {
	public $count;

	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$this->count = $this->getPlainJsonObject()->count;
	}

	/**
	 * @return mixed
	 */
	public function getCount() {
		return $this->count;
	}
}