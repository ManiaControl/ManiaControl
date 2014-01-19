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
	 * Set Url Id to use from the Dico
	 *
	 * @param string $urlId
	 */
	public function setUrlId($urlId);

	/**
	 * Set Manialink
	 *
	 * @param string $manialink Manialink Name
	 */
	public function setManialink($manialink);

	/**
	 * Set Manialink Id to use from the Dico
	 * 
	 * @param string $manialinkId Manialink Id
	 */
	public function setManialinkId($manialinkId);
}
