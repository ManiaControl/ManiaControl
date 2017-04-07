<?php
/**
 * Created by PhpStorm.
 * User: Lukas
 * Date: 07. Apr. 2017
 * Time: 17:56
 */

namespace ManiaControl\Callbacks\Structures\Common;


use ManiaControl\ManiaControl;

class BaseResponseStructure extends BaseStructure {
	protected $responseId;

	/**
	 * Get the Response Id
	 *
	 * @return string
	 */
	public function getResponseId() {
		return $this->responseId;
	}

	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$this->responseId = $this->getPlainJsonObject()->responseid;
	}
}