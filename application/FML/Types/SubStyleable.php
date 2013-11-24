<?php

namespace FML\Types;

/**
 * Interface for elements with substyle attribute
 *
 * @author steeffeen
 */
interface SubStyleable {

	/**
	 * Set substyle
	 *
	 * @param string $subStyle        	
	 */
	public function setSubStyle($subStyle);

	/**
	 * Set style and substyle
	 * 
	 * @param string $style        	
	 * @param string $subStyle        	
	 */
	public function setStyles($style, $subStyle);
}

?>
