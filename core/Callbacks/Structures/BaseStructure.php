<?php
/**
 * Created by PhpStorm.
 * User: Lukas
 * Date: 22. Mär. 2017
 * Time: 18:26
 */

namespace ManiaControl\Callbacks\Structures;


use ManiaControl\ManiaControl;

abstract class BaseStructure {
	/** @var ManiaControl $maniaControl */
	protected $maniaControl;

	/**
	 * Var_Dump the Structure
	 */
	public function dump() {
		var_dump(json_decode(json_encode($this)));
	}

}