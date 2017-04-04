<?php

namespace ManiaControl\General;

/**
 * Class DumpTrait Trait for Implementing the Methods for the JsonSerializeable Interface
 *
 * @package ManiaControl\General
 */
trait JsonSerializeTrait {
	public function toJson(){
		return json_encode(get_object_vars($this));
	}
}