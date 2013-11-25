<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad class for style 'BgsChallengeMedals'
 *
 * @author steeffeen
 */
class Quad_BgsChallengeMedals extends Quad {
	/**
	 * Constants
	 */
	const STYLE = 'BgsChallengeMedals';

	/**
	 * Construct BgsChallengeMedals quad
	 */
	public function __construct() {
		parent::__construct();
		$this->setStyle(self::STYLE);
		array("BgBronze", "BgGold", "BgNadeo", "BgNotPlayed", "BgPlayed", "BgSilver");
	}
}

?>
