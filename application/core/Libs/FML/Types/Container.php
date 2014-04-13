<?php

namespace FML\Types;

use FML\Controls\Control;
use FML\Elements\Format;

/**
 * Interface for Element being able to contain other Controls
 *
 * @author steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface Container {

	/**
	 * Add a new Child Control
	 *
	 * @param Control $child The Child Control to add
	 * @return \FML\Types\Container
	 */
	public function add(Control $child);

	/**
	 * Remove all Children
	 *
	 * @return \FML\Types\Container
	 */
	public function removeChildren();

	/**
	 * Set the Format Object of the Container
	 *
	 * @param Format $format New Format Object
	 * @return \FML\Types\Container
	 */
	public function setFormat(Format $format);

	/**
	 * Get the Format Object of the Container
	 * 
	 * @param bool $createIfEmpty (optional) Whether the Format Object should be created if it's not set yet
	 * @return \FML\Elements\Format
	 */
	public function getFormat($createIfEmpty = true);
}
