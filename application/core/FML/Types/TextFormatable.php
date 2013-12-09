<?php

namespace FML\Types;

/**
 * Interface for elements with Formatable text
 *
 * @author steeffeen
 */
interface TextFormatable {

	/**
	 * Set text size
	 *
	 * @param int $textSize        	
	 */
	public function setTextSize($textSize);

	/**
	 * Set text color
	 *
	 * @param string $textColor        	
	 */
	public function setTextColor($textColor);

	/**
	 * Set area color
	 *
	 * @param string $areaColor        	
	 */
	public function setAreaColor($areaColor);

	/**
	 * Set area focus color
	 *
	 * @param string $areaFocusColor        	
	 */
	public function setAreaFocusColor($areaFocusColor);
}
