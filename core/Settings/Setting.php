<?php

namespace ManiaControl\Settings;

use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\Utils\ClassUtil;

/**
 * ManiaControl Setting Model Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Setting implements UsageInformationAble {
	use UsageInformationTrait;

	/*
	 * Constants
	 */
	const CLASS_NAME      = __CLASS__;
	const TYPE_STRING     = 'string';
	const TYPE_INT        = 'int';
	const TYPE_REAL       = 'real';
	const TYPE_BOOL       = 'bool';
	const TYPE_SET        = 'set';
	const VALUE_DELIMITER = ';;';

	/*
	 * Public properties
	 */
	public $index       = null;
	public $class       = null;
	public $setting     = null;
	public $type        = null;
	public $value       = null;
	public $default     = null;
	public $set         = null;
	public $fetchTime   = null;
	public $description = null;

	/**
	 * Construct a new setting instance
	 *
	 * @param mixed       $object
	 * @param string      $settingName
	 * @param mixed       $defaultValue
	 * @param string|null $description
	 */
	public function __construct($object, $settingName, $defaultValue, $description = null) {
		if ($object === false) {
			// Fetched from Database
			$this->value   = $this->castValue($this->value);
			$this->default = $this->castValue($this->default);
			if ($this->set) {
				$this->set = $this->castValue($this->set, $this->type);
			}
			$this->fetchTime = time();
		} else {
			// Created by Values
			$this->class   = ClassUtil::getClass($object);
			$this->setting = (string) $settingName;
			$this->type    = self::getValueType($defaultValue);
			if ($this->type === self::TYPE_SET) {
				// Save Set and use first Value as Default
				$this->set   = $defaultValue;
				$this->value = reset($defaultValue);
			} else {
				$this->value = $defaultValue;
			}
			$this->default = $this->value;
			$this->description = $description;
		}
	}

	/**
	 * Cast the Value based on the Setting Type
	 *
	 * @param mixed  $value
	 * @param string $type
	 * @return mixed
	 */
	private static function castValue($value, $type = null) {
		if ($type === null) {
			$type = self::getValueType($value);
		}
		if ($type === self::TYPE_INT) {
			return (int) $value;
		}
		if ($type === self::TYPE_REAL) {
			return (float) $value;
		}
		if ($type === self::TYPE_BOOL) {
			return (bool) $value;
		}
		if ($type === self::TYPE_STRING) {
			return (string) $value;
		}
		if ($type === self::TYPE_SET) {
			return explode(self::VALUE_DELIMITER, $value);
		}
		trigger_error("Unsupported Setting Value Type: '" . print_r($type, true) . "'!");
		return $value;
	}

	/**
	 * Get Type of a Value Parameter
	 *
	 * @param mixed $value
	 * @return string
	 */
	private static function getValueType($value) {
		if (is_int($value)) {
			return self::TYPE_INT;
		}
		if (is_float($value)) {
			return self::TYPE_REAL;
		}
		if (is_bool($value)) {
			return self::TYPE_BOOL;
		}
		if (is_string($value)) {
			return self::TYPE_STRING;
		}
		if (is_array($value)) {
			return self::TYPE_SET;
		}
		trigger_error("Unsupported Setting Value Type: '" . print_r($value, true) . "'!");
		return null;
	}

	/**
	 * Get whether the Setting has been persisted at some point
	 *
	 * @return bool
	 */
	public function isPersisted() {
		return ($this->index > 0);
	}

	/**
	 * Get the Formatted Value of the Setting
	 *
	 * @return string
	 */
	public function getFormattedValue() {
		return self::formatValue($this->value);
	}

	/**
	 * Format the given Value based on the Type
	 *
	 * @param mixed  $value
	 * @param string $type
	 * @return string
	 */
	private static function formatValue($value, $type = null) {
		if ($type === null) {
			$type = self::getValueType($value);
		}
		if ($type === self::TYPE_BOOL) {
			return ($value ? 1 : 0);
		}
		if ($type === self::TYPE_SET) {
			return implode(self::VALUE_DELIMITER, $value);
		}
		return $value;
	}

	/**
	 * Get the Formatted Default of the Setting
	 *
	 * @return string
	 */
	public function getFormattedDefault() {
		return self::formatValue($this->default);
	}

	/**
	 * Get the Formatted Set of the Setting
	 *
	 * @return string
	 */
	public function getFormattedSet() {
		if ($this->type === self::TYPE_SET) {
			return self::formatValue($this->set);
		}
		return '';
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
