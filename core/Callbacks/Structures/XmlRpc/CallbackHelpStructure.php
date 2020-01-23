<?php

namespace ManiaControl\Callbacks\Structures\XmlRpc;

use ManiaControl\ManiaControl;

/**
 * Structure Class for the CallbackHelp Structure Callback
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class CallbackHelpStructure extends DocumentationStructure {
	private $callbackName;

	/**
	 * Construct a new Callbacks List Structure
	 *
	 * @param ManiaControl $maniaControl
	 * @param array        $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$this->callbackName = $this->getPlainJsonObject()->callback;
	}

	/**
	 * Gets the Name of the Method
	 *
	 * @api
	 * @return mixed
	 */
	public function getCallbackName() {
		return $this->callbackName;
	}

}