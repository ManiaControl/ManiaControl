<?php

namespace FML\Types;

/**
 * Interface for elements with url attributes
 *
 * @author steeffeen
 */
interface Linkable {

	/**
	 * Set url
	 *
	 * @param string $url        	
	 */
	public function setUrl($url);

	/**
	 * Set manialink
	 *
	 * @param string $manialink        	
	 */
	public function setManialink($manialink);
}

?>
