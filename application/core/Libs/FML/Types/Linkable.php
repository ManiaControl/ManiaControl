<?php

namespace FML\Types;

/**
 * Interface for Elements with url attributes
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface Linkable {

	/**
	 * Set url
	 *
	 * @param string $url Link url
	 * @return \FML\Types\Linkable|static
	 */
	public function setUrl($url);

	/**
	 * Set url id to use from Dico
	 *
	 * @param string $urlId Url id
	 * @return \FML\Types\Linkable|static
	 */
	public function setUrlId($urlId);

	/**
	 * Set manialink
	 *
	 * @param string $manialink Manialink name
	 * @return \FML\Types\Linkable|static
	 */
	public function setManialink($manialink);

	/**
	 * Set manialink id to use from Dico
	 *
	 * @param string $manialinkId Manialink id
	 * @return \FML\Types\Linkable|static
	 */
	public function setManialinkId($manialinkId);
}
