<?php

namespace ManiaControl\Callbacks\Structures\ShootMania;


use ManiaControl\Callbacks\Structures\Common\BaseStructure;
use ManiaControl\ManiaControl;

/**
 * Structure Class for the OnCustomEvent Structure Callback
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnActionCustomEventStructure extends BaseStructure {
	private $time;
	private $actionId;
	private $shooter;
	private $victim;
	private $param1;
	private $param2 = array();

	/**
	 * OnActionCustomEventStructure constructor.
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 * @param                            $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$this->time     = $this->getPlainJsonObject()->time;
		$this->actionId = $this->getPlainJsonObject()->actionid;

		$this->victim  = $this->maniaControl->getPlayerManager()->getPlayer($this->getPlainJsonObject()->victim);
		$this->shooter = $this->maniaControl->getPlayerManager()->getPlayer($this->getPlainJsonObject()->shooter);

		$this->param1 = $this->getPlainJsonObject()->param1;
		$this->param2 = $this->getPlainJsonObject()->param2;
	}

	/**
	 * Returns Server time when the event occured
	 *
	 * @api
	 * @return int
	 */
	public function getTime() {
		return $this->time;
	}

	/**
	 * < Id of the action that triggered the event
	 *
	 * @api
	 * @return string
	 */
	public function getActionId() {
		return $this->actionId;
	}

	/**
	 * < Login of the player who shot if any
	 *
	 * @api
	 * @return \ManiaControl\Players\Player
	 */
	public function getShooter() {
		return $this->shooter;
	}

	/**
	 * < player who got hit if any
	 *
	 * @api
	 * @return \ManiaControl\Players\Player
	 */
	public function getVictim() {
		return $this->victim;
	}

	/**
	 * < First custom param of the event
	 *
	 * @api
	 * @return string
	 */
	public function getParam1() {
		return $this->param1;
	}

	/**
	 * < Second custom param of the event
	 *
	 * @api
	 * @return array
	 */
	public function getParam2() {
		return $this->param2;
	}

}