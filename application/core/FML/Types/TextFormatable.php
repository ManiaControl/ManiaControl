<?php

namespace FML\Types;

/**
 * Interface for Elements with Formatable Text
 *
 * @author steeffeen
 */
interface TextFormatable {

	/**
	 * Set Text Size
	 *
	 * @param int $textSize Text Size
	 */
	public function setTextSize($textSize);

	/**
	 * Set Text Color
	 *
	 * @param string $textColor Text Color
	 */
	public function setTextColor($textColor);

	/**
	 * Set Area Color
	 *
	 * @param string $areaColor Area Color
	 */
	public function setAreaColor($areaColor);

	/**
	 * Set Area Focus Color
	 *
	 * @param string $areaFocusColor Area Focus Color
	 */
	public function setAreaFocusColor($areaFocusColor);
}
