<?php

namespace ManiaControl\Callbacks\Structures\XmlRpc;

use ManiaControl\Callbacks\Structures\Common\BaseStructure;
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
	private $responseId;
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

		$this->responseId = $this->getPlainJsonObject()->responseid;
		$this->callbacks  = $this->getPlainJsonObject()->callbacks;
	}

	/**
	 * Get the Response Id //TODO Trait for all Response Ids
	 *
	 * @return string
	 */
	public function getResponseId() {
		return $this->responseId;
	}

	/**
	 * Get Array of the Callbacks
	 *
	 * @return string[]
	 */
	public function getCallbacks() {
		return $this->callbacks;
	}

}