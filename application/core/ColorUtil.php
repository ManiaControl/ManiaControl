<?php

namespace ManiaControl;

/**
 * Utility Class offering Methods to convert and use ManiaPlanet Colors
 *
 * @author     ManiaControl Team <mail@maniacontrol.com>
 * @copyright  2014 ManiaControl Team
 * @license    http://www.gnu.org/licenses/ GNU General Public License, Version 3
 * @deprecated Use \ManiaControl\Utils\ColorUtil
 */
abstract class ColorUtil {

	/**
	 * Convert the given float value to a color code from red to green
	 *
	 * @param float $value
	 * @return string
	 * @deprecated Use \ManiaControl\Utils\ColorUtil
	 */
	public static function floatToStatusColor($value) {
		$value = floatval($value);
		$red   = 1.;
		$green = 1.;
		if ($value < 0.5) {
			$green = $value * 2.;
		}
		if ($value > 0.5) {
			$red = 2. * (1. - $value);
		}
		$red   = ColorUtil::floatToCode($red);
		$green = ColorUtil::floatToCode($green);
		return $red . $green . '0';
	}

	/**
	 * Get hex color representation of the float
	 *
	 * @param float $value
	 * @return string
	 * @deprecated Use \ManiaControl\Utils\ColorUtil
	 */
	public static function floatToCode($value) {
		$value = floatval($value);
		if ($value < 0.) {
			$value = 0.;
		}
		if ($value > 1.) {
			$value = 1.;
		}
		$value *= 15.;
		$value = (int)round($value);
		if ($value < 10) {
			return (string)$value;
		}
		$codes = array(10 => 'a', 11 => 'b', 12 => 'c', 13 => 'd', 14 => 'e', 15 => 'f');
		return $codes[$value];
	}
}
