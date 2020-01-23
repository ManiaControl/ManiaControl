<?php

namespace ManiaControl\Callbacks\Structures\Common;


use ManiaControl\General\JsonSerializable;
use ManiaControl\General\JsonSerializeTrait;
use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\ManiaControl;

/**
 * Base Structure of all Callback Structures
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
abstract class BaseStructure implements UsageInformationAble, JsonSerializable {
	use UsageInformationTrait, JsonSerializeTrait;

	/** @var ManiaControl $maniaControl */
	protected $maniaControl;
	private   $plainJsonObject;

	/**
	 * BaseStructure constructor.
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 * @param                            $data
	 */
	protected function __construct(ManiaControl $maniaControl, $data) {
		$this->maniaControl    = $maniaControl;
		$this->plainJsonObject = json_decode($data[0]);
	}

	/**
	 * Gets the Plain Json
	 *
	 * @api
	 */
	public function getPlainJsonObject() {
		return $this->plainJsonObject;
	}
}