<?php

namespace ManiaControl\Callbacks\Structures\TrackMania;


use ManiaControl\Callbacks\Structures\Common\BaseStructure;
use ManiaControl\Callbacks\Structures\Common\BaseTimeStructure;
use ManiaControl\ManiaControl;
//TODO make a common structure between shootmania and this and extend it
/**
 * Structure Class for the OnCommand Structure Callback
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnCommandStructure extends BaseTimeStructure {
	private $name;
	private $value;

	/**
	 * OnCommandStructure constructor.
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 * @param                            $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$this->name  = $this->getPlainJsonObject()->name;
		$this->value = $this->getPlainJsonObject()->value;
	}


	/**
	 * < Name of the command
	 *
	 * @api
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * < The value passed by the command
	 * "boolean": true,
	 * "integer": 123,
	 * "real": 123.456,
	 * "text": "an example value"
	 *
	 * @api
	 * @return mixed
	 */
	public function getValue() {
		return $this->value;
	}
}