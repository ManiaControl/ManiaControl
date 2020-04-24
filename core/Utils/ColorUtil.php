<?php

namespace ManiaControl\Utils;

use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;

/**
 * Utility Class offering Methods to convert and use ManiaPlanet Colors
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
abstract class ColorUtil implements UsageInformationAble {
	use UsageInformationTrait;

	/**
	 * Convert the given float value to a color code from red to green
	 *
	 * @param float $value
	 * @return string
	 */
	public static function floatToStatusColor($value, $addBlue = true) {
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

		if ($addBlue) {
			return $red . $green . '0';
		} else {
			return $red . $green;
		}
	}

	/**
	 * Get hex color representation of the float
	 *
	 * @param float $value
	 * @return string
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
		$value = (int) round($value);
		if ($value < 10) {
			return (string) $value;
		}
		static $codes = array(10 => 'a', 11 => 'b', 12 => 'c', 13 => 'd', 14 => 'e', 15 => 'f');
		return $codes[$value];
	}
}
