<?php

namespace ManiaControl\Utils;

use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;

/**
 * Command Line Helper Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class CommandLineHelper implements UsageInformationAble {
	use UsageInformationTrait;

	/**
	 * Get the command line parameter value with the given name
	 *
	 * @param string $paramName
	 * @return string
	 */
	public static function getParameter($paramName) {
		$paramName = (string) $paramName;
		$params    = self::getAllParameters();
		foreach ($params as $param) {
			$parts = explode('=', $param, 2);
			if (count($parts) < 2) {
				continue;
			}
			if ($parts[0] !== $paramName) {
				continue;
			}
			return $parts[1];
		}
		return null;
	}

	/**
	 * Get all command line parameters
	 *
	 * @return array
	 */
	public static function getAllParameters() {
		global $argv;
		return (array) $argv;
	}
}
