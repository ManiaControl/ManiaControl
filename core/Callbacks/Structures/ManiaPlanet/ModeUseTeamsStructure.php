<?php

namespace ManiaControl\Callbacks\Structures\ManiaPlanet;


use ManiaControl\Callbacks\Structures\Common\BaseResponseStructure;
use ManiaControl\ManiaControl;

/**
 * Structure Class for the Mode use  Teams Callback
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ModeUseTeamsStructure extends BaseResponseStructure {
	private $teams;

	/**
	 * StartServerStructure constructor.
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 * @param                            $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$this->teams = $this->getPlainJsonObject()->teams;
	}

	/**
	 * Returns if the Mode is using Teams
	 *
	 * @api
	 * @return boolean < true if the game mode uses teams, false otherwise
	 */
	public function modeIsUsingTeams() {
		return $this->teams;
	}
}