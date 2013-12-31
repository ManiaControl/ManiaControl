<?php

namespace FML\Types;

/**
 * Interface for elements with SubStyle attribute
 *
 * @author steeffeen
 */
interface SubStyleable {

	/**
	 * Set SubStyle
	 *
	 * @param string $subStyle
	 *        	Sub-Style
	 */
	public function setSubStyle($subStyle);

	/**
	 * Set Style and SubStyle
	 *
	 * @param string $style
	 *        	Style
	 * @param string $subStyle
	 *        	Sub-Style
	 */
	public function setStyles($style, $subStyle);
}
