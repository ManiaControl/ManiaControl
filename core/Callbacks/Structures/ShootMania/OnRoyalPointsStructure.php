<?php

namespace ManiaControl\Callbacks\Structures\ShootMania;


use ManiaControl\Callbacks\Structures\Common\BaseStructure;
use ManiaControl\ManiaControl;

/**
 * Structure Class for the OnRoyalPoints Structure Callback
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnRoyalPointsStructure extends BaseStructure {
	protected $playerLogin;
	protected $type;
	protected $points;

	/**
	 * Construct a new On Hit Structure
	 *
	 * @param ManiaControl $maniaControl
	 * @param array        $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$this->playerLogin = $this->getPlainJsonObject()->login;
		$this->type        = $this->getPlainJsonObject()->type;
		$this->points      = $this->getPlainJsonObject()->points;
	}

	/**
	 * Gets the Player
	 *
	 * @api
	 * @return \ManiaControl\Players\Player Player
	 */
	public function getPlayer() {
		return $this->maniaControl->getPlayerManager()->getPlayer($this->playerLogin);
	}

	/**
	 * Gets the Type of Points
	 *
	 * @api
	 * @see \ManiaControl\Callbacks\Structures\ShootMania\Models\RoyalPointTypes
	 * @return mixed
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * Gets the number of points scored
	 *
	 * @api
	 * @return int
	 */
	public function getPoints() {
		return intval($this->points);
	}


}