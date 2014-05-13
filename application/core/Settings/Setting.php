<?php

namespace ManiaControl\Settings;

use ManiaControl\Utils\ClassUtil;

/**
 * Model Class for a Setting
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Setting {
	/*
	 * Constants
	 */
	const CLASS_NAME      = __CLASS__;
	const TYPE_STRING     = 'string';
	const TYPE_INT        = 'int';
	const TYPE_REAL       = 'real';
	const TYPE_BOOL       = 'bool';
	const TYPE_ARRAY      = 'array';
	const ARRAY_DELIMITER = ';;';

	/*
	 * Public Properties
	 */
	public $index = null;
	public $class = null;
	public $setting = null;
	public $type = null;
	public $value = null;
	public $default = null;
	public $fetchTime = null;

	/**
	 * Construct a new Setting
	 *
	 * @param bool $fetched
	 */
	public function __construct($fetched = false) {
		if ($fetched) {
			$this->value     = self::castValue($this->value, $this->type);
			$this->default   = self::castValue($this->default, $this->type);
			$this->fetchTime = time();
		}
	}

	/**
	 * Cast a Setting to the given Type
	 *
	 * @param string $type
	 * @param mixed  $value
	 * @return mixed
	 */
	public static function castValue($value, $type) {
		if ($type === self::TYPE_INT) {
			return (int)$value;
		}
		if ($type === self::TYPE_REAL) {
			return (float)$value;
		}
		if ($type === self::TYPE_BOOL) {
			return (bool)$value;
		}
		if ($type === self::TYPE_STRING) {
			return (string)$value;
		}
		if ($type === self::TYPE_ARRAY) {
			return explode(self::ARRAY_DELIMITER, $value);
		}
		trigger_error("Unsupported Setting Value Type: '" . print_r($type, true) . "'!");
		return $value;
	}

	/**
	 * Get the Set String for the available Types
	 *
	 * @return string
	 */
	public static function getTypeSet() {
		$typeSet = "'" . self::TYPE_STRING . "','" . self::TYPE_INT . "','" . self::TYPE_REAL . "','" . self::TYPE_BOOL . "','" . self::TYPE_ARRAY . "'";
		return $typeSet;
	}

	/**
	 * Get the Formatted Value of the Setting
	 *
	 * @return mixed
	 */
	public function getFormattedValue() {
		$formattedValue = self::formatValue($this->value, $this->type);
		return $formattedValue;
	}

	/**
	 * Format a Value for saving it to the Database
	 *
	 * @param mixed  $value
	 * @param string $type
	 * @return mixed
	 */
	public static function formatValue($value, $type = null) {
		if ($type === null) {
			$type = self::getValueType($value);
		}
		if ($type === self::TYPE_ARRAY) {
			return implode(self::ARRAY_DELIMITER, $value);
		}
		if ($type === self::TYPE_BOOL) {
			return ($value ? 1 : 0);
		}
		return $value;
	}

	/**
	 * Get Type of a Value Parameter
	 *
	 * @param mixed $value
	 * @return string
	 */
	public static function getValueType($value) {
		if (is_int($value)) {
			return self::TYPE_INT;
		}
		if (is_real($value)) {
			return self::TYPE_REAL;
		}
		if (is_bool($value)) {
			return self::TYPE_BOOL;
		}
		if (is_string($value)) {
			return self::TYPE_STRING;
		}
		if (is_array($value)) {
			return self::TYPE_ARRAY;
		}
		trigger_error("Unsupported Setting Value Type: '" . print_r($value, true) . "'!");
		return null;
	}

	/**
	 * Check if the Settings belongs to the given Class
	 *
	 * @param mixed $object
	 * @return bool
	 */
	public function belongsToClass($object) {
		$className = ClassUtil::getClass($object);
		return ($className === $this->class);
	}
}
