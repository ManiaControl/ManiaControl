<?php

namespace FML\Types;

/**
 * Interface for elements with url attributes
 *
 * @author steeffeen
 */
interface Linkable {

	/**
	 * Set Url
	 *
	 * @param string $url
	 *        	Link Url
	 */
	public function setUrl($url);

	/**
	 * Set Manialink
	 *
	 * @param string $manialink
	 *        	Manialink Name
	 */
	public function setManialink($manialink);
}
