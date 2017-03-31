<?php

namespace ManiaControl\Callbacks\Structures\ShootMania;


use ManiaControl\Callbacks\Structures\Common\BaseStructure;
use ManiaControl\ManiaControl;

/**
 * Structure Class for the OnCommand Structure Callback
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnCommandStructure extends BaseStructure {
	private $time;
	private $name;
	private $value;

	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$this->time  = $this->getPlainJsonObject()->time;
		$this->name  = $this->getPlainJsonObject()->name;
		$this->value = $this->getPlainJsonObject()->value;
	}

	/**
	 * < Server time when the event occured
	 *
	 * @return int
	 */
	public function getTime() {
		return $this->time;
	}

	/**
	 * < Name of the command
	 *
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
	 * @return mixed
	 */
	public function getValue() {
		return $this->value;
	}


}