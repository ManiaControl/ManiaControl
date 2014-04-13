<?php

namespace FML\Script;

/**
 * Builder Class offering Methods to build ManiaScript
 *
 * @author steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
abstract class Builder {

	/**
	 * Build a Label Implementation Block
	 *
	 * @param string $labelName Name of the Label
	 * @param string $implementationCode Label Implementation Coding (without declaration)
	 * @return string
	 */
	public static function getLabelImplementationBlock($labelName, $implementationCode) {
		$labelText = PHP_EOL . "***{$labelName}***" . PHP_EOL . "***{$implementationCode}***" . PHP_EOL;
		return $labelText;
	}

	/**
	 * Escape dangerous Characters in the given Text
	 *
	 * @param string $text Text to escape
	 * @return string
	 */
	public static function escapeText($text) {
		$escapedText = $text;
		$dangers = array('\\', '"', "\n");
		$replacements = array('\\\\', '\\"', '\n');
		$escapedText = str_ireplace($dangers, $replacements, $escapedText);
		return $escapedText;
	}

	/**
	 * Get the Real String-Representation of the given Value
	 *
	 * @param float $value The Float Value to convert to a ManiaScript Real
	 * @return string
	 */
	public static function getReal($value) {
		$value = (float) $value;
		$stringVal = (string) $value;
		if (!fmod($value, 1)) $stringVal .= '.';
		return $stringVal;
	}
	
	/**
	 * Get the Boolean String-Representation of the given Value
	 * 
	 * @param bool $value The Value to convert to a ManiaScript Boolean
	 * @return string
	 */
	public static function getBoolean($value) {
		$bool = (bool) $value;
		if ($bool) {
			return "True";
		}
		return "False";
	}
}
