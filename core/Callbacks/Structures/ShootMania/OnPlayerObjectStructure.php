<?php

namespace ManiaControl\Callbacks\Structures\ShootMania;


use ManiaControl\Callbacks\Structures\Common\BaseStructure;
use ManiaControl\ManiaControl;

/**
 * Structure Class for the OnPlayerTouchesObject Structure Callback
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnPlayerObjectStructure extends BaseStructure {
	private $time;
	private $player;
	private $objectId;
	private $modelId;
	private $modelName;

	/**
	 * OnPlayerObjectStructure constructor.
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 * @param                            $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$this->time      = $this->getPlainJsonObject()->time;
		$this->objectId  = $this->getPlainJsonObject()->objectid;
		$this->modelId   = $this->getPlainJsonObject()->modelid;
		$this->modelName = $this->getPlainJsonObject()->modelname;

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
	 * < Login of the player who touched the object
	 *
	 * @api
	 * @return \ManiaControl\Players\Player
	 */
	public function getPlayer() {
		return $this->player;
	}

	/**
	 * < The id of the object
	 *
	 * @api
	 * @return string
	 */
	public function getObjectId() {
		return $this->objectId;
	}

	/**
	 * < The id of the object model
	 *
	 * @api
	 * @return string
	 */
	public function getModelId() {
		return $this->modelId;
	}

	/**
	 * < The name of the object model
	 *
	 * @api
	 * @return string
	 */
	public function getModelName() {
		return $this->modelName;
	}
}