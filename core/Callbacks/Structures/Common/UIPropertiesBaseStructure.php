<?php

namespace ManiaControl\Callbacks\Structures\Common;

use ManiaControl\ManiaControl;

/**
 * Base Structure Class for all Callbacks using a Timestamp
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class UIPropertiesBaseStructure extends BaseResponseStructure {
	private $uiPropertiesXML;
	private $uiPropertiesJson;

	/**
	 * BaseResponseStructure constructor.
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 * @param                            $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$this->uiPropertiesXML  = $data[1];
		$this->uiPropertiesJson = $data[2];
	}

	/**
	 * Gets the UI Properties as XML
	 *
	 * @return mixed
	 */
	public function getUiPropertiesXML() {
		return $this->uiPropertiesXML;
	}

	/**
	 * Gets the UI Properties as Json
	 *
	 * @return mixed
	 */
	public function getUiPropertiesJson() {
		return $this->uiPropertiesJson;
	}

	/**
	 * Gets the UI Properties as JSON Decoded Object
	 *
	 * @return mixed
	 */
	public function getUiPropertiesObject() {
		return json_decode($this->uiPropertiesJson);
	}

	/**
	 * Gets the UI Properties as JSON Decoded Array
	 *
	 * @return mixed
	 */
	public function getUiPropertiesArray() {
		return json_decode($this->uiPropertiesJson, true);
	}
}