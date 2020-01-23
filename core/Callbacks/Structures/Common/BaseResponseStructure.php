<?php

namespace ManiaControl\Callbacks\Structures\Common;

use ManiaControl\ManiaControl;

/**
 * Base Structure Class for all Callbacks using a Response Id
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class BaseResponseStructure extends BaseStructure {
	protected $responseId;

	/**
	 * BaseResponseStructure constructor.
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 * @param                            $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$this->responseId = $this->getPlainJsonObject()->responseid;
	}

	/**
	 * Get the Response Id
	 *
	 * @api
	 * @return string
	 */
	public function getResponseId() {
		return $this->responseId;
	}

}