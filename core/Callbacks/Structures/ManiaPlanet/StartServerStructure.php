<?php

namespace ManiaControl\Callbacks\Structures\ManiaPlanet;


use ManiaControl\Callbacks\Structures\BaseStructure;
use ManiaControl\ManiaControl;

/**
 * Structure Class for the Default Start Server Callback
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class StartServerStructure extends BaseStructure {
	private $restarted;

	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$this->restarted = $this->getPlainJsonObject()->restarted;
	}

	/**
	 * Flag if the Server got Restarted
	 *
	 * @return mixed
	 */
	public function getRestarted() {
		return $this->restarted;
	}
}