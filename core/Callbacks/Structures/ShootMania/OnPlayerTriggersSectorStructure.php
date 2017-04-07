<?php

namespace ManiaControl\Callbacks\Structures\ShootMania;


use ManiaControl\Callbacks\Structures\Common\BaseStructure;
use ManiaControl\ManiaControl;

/**
 * Structure Class for the OnPlayerTriggersSector Structure Callback
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnPlayerTriggersSectorStructure extends BaseStructure {
	private $time;
	private $player;
	private $sectorId;

	/**
	 * OnPlayerTriggersSectorStructure constructor.
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 * @param                            $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$this->time     = $this->getPlainJsonObject()->time;
		$this->sectorId = $this->getPlainJsonObject()->sectorid;

		$this->player = $this->maniaControl->getPlayerManager()->getPlayer($this->getPlainJsonObject()->login);
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
	 * < player who touched the object
	 *
	 * @api
	 * @return \ManiaControl\Players\Player
	 */
	public function getPlayer() {
		return $this->player;
	}

	/**
	 * < Id of the triggered sector
	 *
	 * @api
	 * @return string
	 */
	public function getSectorId() {
		return $this->sectorId;
	}


}