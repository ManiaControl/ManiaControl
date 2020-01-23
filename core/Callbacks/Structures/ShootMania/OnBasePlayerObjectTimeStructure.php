<?php

namespace ManiaControl\Callbacks\Structures\ShootMania;


use ManiaControl\Callbacks\Structures\Common\BasePlayerTimeStructure;
use ManiaControl\ManiaControl;

/**
 * Structure Class for the OnPlayerTouchesObject Structure Callback
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnBasePlayerObjectTimeStructure extends BasePlayerTimeStructure {
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

		$this->objectId  = $this->getPlainJsonObject()->objectid;
		$this->modelId   = $this->getPlainJsonObject()->modelid;
		$this->modelName = $this->getPlainJsonObject()->modelname;

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