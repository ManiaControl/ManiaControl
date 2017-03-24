<?php

namespace ManiaControl\Callbacks\Structures;


use ManiaControl\General\Dumpable;
use ManiaControl\ManiaControl;

/**
 * Base Structure of all Callback Structures
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
abstract class BaseStructure implements Dumpable {
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

	/**
	 * Var_Dump the Structure
	 */
	public function dump() {
		var_dump(json_decode(json_encode($this)));
		var_dump("Class Name including Namespace: " . get_class($this));
	}
}