<?php

namespace FML\Types;

/**
 * Interface for Elements with Url Attributes
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface Linkable {

	/**
	 * Set Url
	 *
	 * @param string $url Link Url
	 * @return \FML\Types\Linkable
	 */
	public function setUrl($url);

	/**
	 * Set Url Id to use from the Dico
	 *
	 * @param string $urlId
	 * @return \FML\Types\Linkable
	 */
	public function setUrlId($urlId);

	/**
	 * Set Manialink
	 *
	 * @param string $manialink Manialink Name
	 * @return \FML\Types\Linkable
	 */
	public function setManialink($manialink);

	/**
	 * Set Manialink Id to use from the Dico
	 *
	 * @param string $manialinkId Manialink Id
	 * @return \FML\Types\Linkable
	 */
	public function setManialinkId($manialinkId);
}
