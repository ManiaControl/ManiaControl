<?php

namespace ManiaControl\Callbacks\Structures\XmlRpc;

use ManiaControl\Callbacks\Structures\Common\BaseResponseStructure;
use ManiaControl\ManiaControl;

/**
 * Structure Class for the List Structure Callback
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class CallbackListStructure extends BaseResponseStructure {
	/** @var  array $callbacks */
	private $callbacks;

	/**
	 * Construct a new Callbacks List Structure
	 *
	 * @param ManiaControl $maniaControl
	 * @param array        $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$this->callbacks = $this->getPlainJsonObject()->callbacks;
	}


	/**
	 * Get Array of the Callbacks
	 *
	 * @api
	 * @return string[]
	 */
	public function getCallbacks() {
		return $this->callbacks;
	}

}