<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad class for style 'Hud3dEchelons'
 *
 * @author steeffeen
 */
class Quad_Hud3dEchelons extends Quad {
	/**
	 * Constants
	 */
	const STYLE = 'Hud3dEchelons';

	/**
	 * Construct Hud3dEchelons quad
	 */
	public function __construct() {
		parent::__construct();
		$this->setStyle(self::STYLE);
		array("EchelonBronze1", "EchelonBronze2", "EchelonBronze3", "EchelonGold1", "EchelonGold2", "EchelonGold3", "EchelonSilver1", 
			"EchelonSilver2", "EchelonSilver3");
	}
}

?>
