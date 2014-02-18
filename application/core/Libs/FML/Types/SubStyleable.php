<?php

namespace FML\Types;

/**
 * Interface for Elements with SubStyle Attribute
 *
 * @author steeffeen
 */
interface SubStyleable {

	/**
	 * Set SubStyle
	 *
	 * @param string $subStyle SubStyle Name
	 * @return \FML\Types\SubStyleable
	 */
	public function setSubStyle($subStyle);

	/**
	 * Set Style and SubStyle
	 *
	 * @param string $style Style Name
	 * @param string $subStyle SubStyle Name
	 * @return \FML\Types\SubStyleable
	 */
	public function setStyles($style, $subStyle);
}
