<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad Class for 'EnergyBar' Style
 *
 * @author steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Quad_EnergyBar extends Quad {
	/*
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
	 * Create a new Quad_EnergyBar Control
	 *
	 * @param string $id (optional) Control Id
	 * @return \FML\Controls\Quads\Quad_EnergyBar
	 */
	public static function create($id = null) {
		$quadEnergybar = new Quad_EnergyBar($id);
		return $quadEnergybar;
	}

	/**
	 * Construct a new Quad_EnergyBar Control
	 *
	 * @param string $id (optional) Control Id
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->setStyle(self::STYLE);
	}
}
