<?php
/**
 * Created by PhpStorm.
 * User: Lukas
 * Date: 24. Mär. 2017
 * Time: 21:59
 */

namespace ManiaControl\General;


trait DumpTrait {
	/**
	 * Var_Dump Public Properties of the Object
	 */
	public function dump() {
		var_dump(json_decode(json_encode($this)));
	}
}