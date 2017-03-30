<?php

namespace ManiaControl\Callbacks\Structures\ShootMania;


use ManiaControl\Callbacks\Structures\Common\BaseStructure;
use ManiaControl\ManiaControl;

/**
 * Structure Class for the OnCustomEvent Structure Callback
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnActionEvent extends BaseStructure {
	private $time;
	private $player;
	private $actionInput;

	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$this->time        = $this->getPlainJsonObject()->time;
		$this->actionInput = $this->getPlainJsonObject()->actioninput;

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
	 * < player who triggered the action
	 *
	 * @return \ManiaControl\Players\Player
	 */
	public function getPlayer() {
		return $this->player;
	}

	/**
	 * < The input pressed to trigger the action
	 *
	 * @return string
	 */
	public function getActionInput() {
		return $this->actionInput;
	}


}