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
	const SUBSTYLE_BgText = 'BgText';
	const SUBSTYLE_EnergyBar = 'EnergyBar';
	const SUBSTYLE_EnergyBar_0_25 = 'EnergyBar_0.25';
	const SUBSTYLE_EnergyBar_Thin = 'EnergyBar_Thin';
	const SUBSTYLE_HeaderGaugeLeft = 'HeaderGaugeLeft';
	const SUBSTYLE_HeaderGaugeRight = 'HeaderGaugeRight';

	/**
	 * Construct EnergyBar quad
	 */
	public function __construct() {
		parent::__construct();
		$this->setStyle(self::STYLE);
	}
}
