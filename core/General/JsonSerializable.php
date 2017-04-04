<?php

namespace ManiaControl\General;

/**
 * Object implementing this Interface has a toJson() Method
 *
 * @package ManiaControl\General
 */
interface JsonSerializable {
	public function toJson();
}