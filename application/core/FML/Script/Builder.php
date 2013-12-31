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
	 * @param string $labelName
	 * @param string $implementationCode
	 * @return string
	 */
	public static function getLabelImplementationBlock($labelName, $implementationCode) {
		$labelText = PHP_EOL . "***{$labelName}***" . PHP_EOL . "***{$implementationCode}***" . PHP_EOL;
		return $labelText;
	}
}
