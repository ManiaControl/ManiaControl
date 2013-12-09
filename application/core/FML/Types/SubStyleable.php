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
	 */
	public function setSubStyle($subStyle);

	/**
	 * Set Style and SubStyle
	 * 
	 * @param string $style        	
	 * @param string $subStyle        	
	 */
	public function setStyles($style, $subStyle);
}
