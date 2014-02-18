<?php

namespace FML\Types;

/**
 * Interface for Elements with Background Color Attribute
 *
 * @author steeffeen
 */
interface BgColorable {

	/**
	 * Set Background Color
	 *
	 * @param string $bgColor Background Color
	 * @return \FML\Types\BgColorable
	 */
	public function setBgColor($bgColor);
}
