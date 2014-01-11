<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad Class for 'EnergyBar' Style
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
	 *
	 * @see \FML\Controls\Quad
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->setStyle(self::STYLE);
	}
}
