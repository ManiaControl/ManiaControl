<?php

namespace ManiaControl\Callbacks\Structures\XmlRpc;

use ManiaControl\ManiaControl;

/**
 * Structure Class for the Method Help Structure Callback
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class MethodHelpStructure extends DocumentationStructure {
	private $methodName;

	/**
	 * Construct a new Callbacks List Structure
	 *
	 * @param ManiaControl $maniaControl
	 * @param array        $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$this->methodName = $this->getPlainJsonObject()->method;
	}

	/**
	 * Gets the Name of the Method
	 *
	 * @api
	 * @return mixed
	 */
	public function getMethodName() {
		return $this->methodName;
	}

}