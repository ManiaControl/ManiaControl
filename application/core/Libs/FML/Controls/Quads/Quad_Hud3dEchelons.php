<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad Class for 'Hud3dEchelons' Style
 *
 * @author steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Quad_Hud3dEchelons extends Quad {
	/*
	 * Constants
	 */
	const STYLE = 'Hud3dEchelons';
	const SUBSTYLE_EchelonBronze1 = 'EchelonBronze1';
	const SUBSTYLE_EchelonBronze2 = 'EchelonBronze2';
	const SUBSTYLE_EchelonBronze3 = 'EchelonBronze3';
	const SUBSTYLE_EchelonGold1 = 'EchelonGold1';
	const SUBSTYLE_EchelonGold2 = 'EchelonGold2';
	const SUBSTYLE_EchelonGold3 = 'EchelonGold3';
	const SUBSTYLE_EchelonSilver1 = 'EchelonSilver1';
	const SUBSTYLE_EchelonSilver2 = 'EchelonSilver2';
	const SUBSTYLE_EchelonSilver3 = 'EchelonSilver3';

	/**
	 * Create a new Quad_Hud3dEchelons Control
	 *
	 * @param string $id (optional) Control Id
	 * @return \FML\Controls\Quads\Quad_Hud3dEchelons
	 */
	public static function create($id = null) {
		$quadHud3dEchelons = new Quad_Hud3dEchelons($id);
		return $quadHud3dEchelons;
	}

	/**
	 * Construct a new Quad_Hud3dEchelons Control
	 *
	 * @param string $id (optional) Control Id
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->setStyle(self::STYLE);
	}
}
