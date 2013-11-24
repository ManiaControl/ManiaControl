<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad class for style 'MedalsBig'
 *
 * @author steeffeen
 */
class Quad_MedalsBig extends Quad {
	/**
	 * Constants
	 */
	const STYLE = 'MedalsBig';

	/**
	 * Construct MedalsBig quad
	 */
	public function __construct() {
		parent::__construct();
		$this->setStyle(self::STYLE);
		array("MedalBronze", "MedalGold", "MedalGoldPerspective", "MedalNadeo", "MedalNadeoPerspective", "MedalSilver", "MedalSlot");
	}
}

?>
