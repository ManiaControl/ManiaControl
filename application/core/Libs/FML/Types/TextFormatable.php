<?php

namespace FML\Types;

/**
 * Interface for Elements with formatable text
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface TextFormatable {

	/**
	 * Set text size
	 *
	 * @param int $textSize Text size
	 * @return \FML\Types\TextFormatable|static
	 */
	public function setTextSize($textSize);

	/**
	 * Set text color
	 *
	 * @param string $textColor Text color
	 * @return \FML\Types\TextFormatable|static
	 */
	public function setTextColor($textColor);

	/**
	 * Set area color
	 *
	 * @param string $areaColor Area color
	 * @return \FML\Types\TextFormatable|static
	 */
	public function setAreaColor($areaColor);

	/**
	 * Set area focus color
	 *
	 * @param string $areaFocusColor Area focus color
	 * @return \FML\Types\TextFormatable|static
	 */
	public function setAreaFocusColor($areaFocusColor);
}
