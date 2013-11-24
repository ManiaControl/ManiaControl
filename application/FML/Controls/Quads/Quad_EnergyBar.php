<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad class for style 'EnergyBar'
 *
 * @author steeffeen
 */
class Quad_EnergyBar extends Quad {
	/**
	 * Constants
	 */
	const STYLE = 'EnergyBar';

	/**
	 * Construct EnergyBar quad
	 */
	public function __construct() {
		parent::__construct();
		$this->setStyle(self::STYLE);
		array("BgText", "EnergyBar", "EnergyBar_0.25", "EnergyBar_Thin", "HeaderGaugeLeft", "HeaderGaugeRight");
	}
}

?>
