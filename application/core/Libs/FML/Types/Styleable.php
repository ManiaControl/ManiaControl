<?php

namespace FML\Types;

/**
 * Interface for Elements with Style Attribute
 *
 * @author steeffeen
 */
interface Styleable {

	/**
	 * Set Style
	 *
	 * @param string $style Style Name
	 * @return \FML\Types\Styleable
	 */
	public function setStyle($style);
}
