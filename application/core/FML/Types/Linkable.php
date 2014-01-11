<?php

namespace FML\Types;

/**
 * Interface for Elements with Url Attributes
 *
 * @author steeffeen
 */
interface Linkable {

	/**
	 * Set Url
	 *
	 * @param string $url Link Url
	 */
	public function setUrl($url);

	/**
	 * Set Manialink
	 *
	 * @param string $manialink Manialink Name
	 */
	public function setManialink($manialink);
}
