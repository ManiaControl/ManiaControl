<?php

namespace FML\Script;

/**
 * ManiaScript Builder class
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
abstract class Builder {
	/*
	 * Constants
	 */
	const EMPTY_STRING = '""';

	/**
	 * Build a label implementation block
	 *
	 * @param string $labelName          Name of the label
	 * @param string $implementationCode Label implementation coding (without declaration)
	 * @param bool   $isolate            Whether the code should be isolated in an own block
	 * @return string
	 */
	public static function getLabelImplementationBlock($labelName, $implementationCode, $isolate = true) {
		if ($isolate) {
			$implementationCode = 'if(True){' . $implementationCode . '}';
		}
		$labelText = PHP_EOL . "***{$labelName}***" . PHP_EOL . "***{$implementationCode}***" . PHP_EOL;
		return $labelText;
	}

	/**
	 * Escape dangerous characters in the given text
	 *
	 * @param string $text           Text to escape
	 * @param bool   $addApostrophes (optional) Whether to add apostrophes before and after the text
	 * @return string
	 */
	public static function escapeText($text, $addApostrophes = false) {
		$dangers      = array('\\', '"', "\n");
		$replacements = array('\\\\', '\\"', '\\n');
		$escapedText  = str_ireplace($dangers, $replacements, $text);
		if ($addApostrophes) {
			$escapedText = '"' . $escapedText . '"';
		}
		return $escapedText;
	}

	/**
	 * Get the 'Real' string representation of the given value
	 *
	 * @param float $value Float value to convert to a ManiaScript 'Real'
	 * @return string
	 */
	public static function getReal($value) {
		$value     = (float)$value;
		$stringVal = (string)$value;
		if (!fmod($value, 1)) {
			$stringVal .= '.';
		}
		return $stringVal;
	}

	/**
	 * Get the 'Boolean' string representation of the given value
	 *
	 * @param bool $value Value to convert to a ManiaScript 'Boolean'
	 * @return string
	 */
	public static function getBoolean($value) {
		$bool = (bool)$value;
		if ($bool) {
			return 'True';
		}
		return 'False';
	}

	/**
	 * Get the string representation of the given array
	 *
	 * @param array $array       Array to convert to a ManiaScript array
	 * @param bool  $associative (optional) Whether the array should be associative
	 * @return string
	 */
	public static function getArray(array $array, $associative = false) {
		$arrayText = '[';
		$index     = 0;
		$count     = count($array);
		foreach ($array as $key => $value) {
			if ($associative) {
				if (is_string($key)) {
					$arrayText .= '"' . static::escapeText($key) . '"';
				} else {
					$arrayText .= $key;
				}
				$arrayText .= ' => ';
			}
			if (is_string($value)) {
				$arrayText .= '"' . static::escapeText($value) . '"';
			} else {
				$arrayText .= $value;
			}
			if ($index < $count - 1) {
				$arrayText .= ', ';
				$index++;
			}
		}
		$arrayText .= ']';
		return $arrayText;
	}

	/**
	 * Get the include command for the given file and namespace
	 *
	 * @param string $file      Include file
	 * @param string $namespace (optional) Include namespace
	 * @return string
	 */
	public static function getInclude($file, $namespace = null) {
		if (!$namespace && stripos($file, '.') === false) {
			$namespace = $file;
		}
		$file        = static::escapeText($file, true);
		$includeText = "#Include	{$file}";
		if ($namespace) {
			$includeText .= "	as {$namespace}";
		}
		$includeText .= PHP_EOL;
		return $includeText;
	}

	/**
	 * Get the constant command for the given name and value
	 *
	 * @param string $name  Constant name
	 * @param string $value Constant value
	 * @return string
	 */
	public static function getConstant($name, $value) {
		if (is_string($value)) {
			$value = static::escapeText($value, true);
		} else if (is_bool($value)) {
			$value = static::getBoolean($value);
		}
		$constantText = "#Const	{$name}	{$value}" . PHP_EOL;
		return $constantText;
	}
}
