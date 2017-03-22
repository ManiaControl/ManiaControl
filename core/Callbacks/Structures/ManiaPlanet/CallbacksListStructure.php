<?php

namespace ManiaControl\Callbacks\Structures\ManiaPlanet;

use ManiaControl\ManiaControl;

/**
 * Structure Class for the List Structure Callback
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class CallbacksListStructure {
	/** @var  string $responseId */
	private $responseId;
	/** @var  array $callbacks */
	private $callbacks;

	/** @var ManiaControl $maniaControl */
	private $maniaControl;

	/**
	 * Construct a new Armor Empty Structure
	 *
	 * @param ManiaControl $maniaControl
	 * @param array        $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		$this->maniaControl = $maniaControl;

		//Not tested yet, TODO test
		$json = json_decode($data);

		$this->responseId = $json->responseId;
		$this->callbacks  = $json->callbacks;
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