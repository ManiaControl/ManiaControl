<?php

namespace ManiaControl\Callbacks\Structures\XmlRpc;

use ManiaControl\Callbacks\Structures\Common\BaseResponseStructure;
use ManiaControl\ManiaControl;

/**
 * Structure Class for the MethodList Structure Callback
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class MethodListStructure extends BaseResponseStructure {
	/** @var  array $callbacks */
	private $methods;

	/**
	 * Construct a new Callbacks List Structure
	 *
	 * @param ManiaControl $maniaControl
	 * @param array        $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$this->methods  = $this->getPlainJsonObject()->methods;
	}


	/**
	 * Get Array of the Methods
	 *
	 * @api
	 * @return string[]
	 */
	public function getMethods() {
		return $this->methods;
	}

}