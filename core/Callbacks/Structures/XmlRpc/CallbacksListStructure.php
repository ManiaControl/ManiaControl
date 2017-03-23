<?php

namespace ManiaControl\Callbacks\Structures\XmlRpc;

use ManiaControl\Callbacks\Structures\BaseStructure;
use ManiaControl\ManiaControl;

/**
 * Structure Class for the List Structure Callback
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class CallbacksListStructure extends BaseStructure {
	/** @var  string $responseId */
	public $responseId;
	/** @var  array $callbacks */
	public $callbacks;

	/**
	 * Construct a new Armor Empty Structure
	 *
	 * @param ManiaControl $maniaControl
	 * @param array        $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$this->responseId = $this->getJson()->responseid;
		$this->callbacks  = $this->getJson()->callbacks;

		$this->dump();
	}

	/**
	 * @return string
	 */
	public function getResponseId() {
		return $this->responseId;
	}

	/**
	 * @return array
	 */
	public function getCallbacks() {
		return $this->callbacks;
	}

}