<?php

namespace FML\Types;

/**
 * Interface for elements with Formatable text
 *
 * @author steeffeen
 */
interface TextFormatable {

	/**
	 * Set Text Size
	 *
	 * @param int $textSize
	 *        	Text Size
	 */
	public function setTextSize($textSize);

	/**
	 * Set Text Color
	 *
	 * @param string $textColor
	 *        	Text Color
	 */
	public function setTextColor($textColor);

	/**
	 * Set Area Color
	 *
	 * @param string $areaColor
	 *        	Area Text Color
	 */
	public function setAreaColor($areaColor);

	/**
	 * Set Area Focus Color
	 *
	 * @param string $areaFocusColor
	 *        	Focus Area Color
	 */
	public function setAreaFocusColor($areaFocusColor);
}
