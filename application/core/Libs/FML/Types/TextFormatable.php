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
	 * @return \FML\Types\TextFormatable
	 */
	public function setTextSize($textSize);

	/**
	 * Set Text Color
	 *
	 * @param string $textColor Text Color
	 * @return \FML\Types\TextFormatable
	 */
	public function setTextColor($textColor);

	/**
	 * Set Area Color
	 *
	 * @param string $areaColor Area Color
	 * @return \FML\Types\TextFormatable
	 */
	public function setAreaColor($areaColor);

	/**
	 * Set Area Focus Color
	 *
	 * @param string $areaFocusColor Area Focus Color
	 * @return \FML\Types\TextFormatable
	 */
	public function setAreaFocusColor($areaFocusColor);
}
