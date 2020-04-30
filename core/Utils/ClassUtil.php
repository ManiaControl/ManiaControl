<?php

namespace ManiaControl\Utils;

/**
 * Utility Class offering Methods related to Classes
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
abstract class ClassUtil {

	/**
	 * Get the Class Name of the given Object
	 *
	 * @param mixed $object
	 * @return string
	 */
	public static function getClass($object) {
		if (is_object($object)) {
			return get_class($object);
		}
		if (is_string($object)) {
			return $object;
		}
		trigger_error("Invalid class param: '" . print_r($object, true) . "'!");
		return (string)$object;
	}
}
