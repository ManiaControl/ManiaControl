<?php

namespace ManiaControl\General;

/**
 * Class DumpTrait Trait for Implementing the Methods for the dumpable Interface
 *
 * @package ManiaControl\General
 */
trait DumpTrait {
	/**
	 * Var_Dump Public Properties of the Object
	 */
	public function dump() {
		var_dump(json_decode(json_encode($this)));
	}
}