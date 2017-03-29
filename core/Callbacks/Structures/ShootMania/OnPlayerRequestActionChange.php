<?php

namespace ManiaControl\Callbacks\Structures\ShootMania;


use ManiaControl\Callbacks\Structures\BaseStructure;
use ManiaControl\ManiaControl;

/**
 * Structure Class for the OnPlayerRequestActionChange Structure Callback
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnPlayerRequestActionChange extends BaseStructure {
	private $time;
	private $player;
	private $actionChange;

	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$this->time         = $this->getPlainJsonObject()->time;
		$this->actionChange = $this->getPlainJsonObject()->actionchange;

		$this->player = $this->maniaControl->getPlayerManager()->getPlayer($this->getPlainJsonObject()->login);
	}

	/**
	 * Returns Server time when the event occured
	 *
	 * @return int
	 */
	public function getTime() {
		return $this->time;
	}

	/**
	 * <  player who requested a new action
	 *
	 * @return \ManiaControl\Players\Player
	 */
	public function getPlayer() {
		return $this->player;
	}

	/**
	 * < Can be -1 (request previous action) or 1 (request next action)
	 *
	 * @return string
	 */
	public function getActionChange() {
		return $this->actionChange;
	}


}