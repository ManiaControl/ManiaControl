<?php

namespace FML\Script;

/**
 * Builder Class offering Methods to build ManiaScript
 *
 * @author steeffeen
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
}
