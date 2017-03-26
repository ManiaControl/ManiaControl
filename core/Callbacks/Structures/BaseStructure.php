<?php

namespace ManiaControl\Callbacks\Structures;


use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\ManiaControl;

/**
 * Base Structure of all Callback Structures
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
abstract class BaseStructure implements UsageInformationAble {
	use UsageInformationTrait;

	/** @var ManiaControl $maniaControl */
	protected $maniaControl;
	private   $plainJsonObject;

	protected function __construct(ManiaControl $maniaControl, $data) {
		$this->maniaControl    = $maniaControl;
		$this->plainJsonObject = json_decode($data[0]);
	}

	/**
	 * Gets the Plain Json
	 */
	public function getPlainJsonObject() {
		return $this->plainJsonObject;
	}
}