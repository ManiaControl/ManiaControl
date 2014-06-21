<?php

namespace FML\Types;

use FML\Elements\Format;

/**
 * Interface for Element being able to contain other Controls
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface Container {

	/**
	 * Add a new child Element
	 *
	 * @param Renderable $child Child Control to add
	 * @return \FML\Types\Container|static
	 */
	public function add(Renderable $child);

	/**
	 * Remove all children
	 *
	 * @return \FML\Types\Container|static
	 */
	public function removeChildren();

	/**
	 * Set the Format object of the Container
	 *
	 * @param Format $format New Format object
	 * @return \FML\Types\Container|static
	 */
	public function setFormat(Format $format);

	/**
	 * Get the Format object of the Container
	 *
	 * @param bool $createIfEmpty (optional) Whether the Format object should be created if it's not set
	 * @return \FML\Elements\Format|static
	 */
	public function getFormat($createIfEmpty = true);
}
