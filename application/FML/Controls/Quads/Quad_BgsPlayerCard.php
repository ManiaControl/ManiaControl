<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad class for style 'BgsPlayerCard'
 *
 * @author steeffeen
 */
class Quad_BgsPlayerCard extends Quad {
	/**
	 * Constants
	 */
	const STYLE = 'BgsPlayerCard';

	/**
	 * Construct BgsPlayerCard quad
	 */
	public function __construct() {
		parent::__construct();
		$this->setStyle(self::STYLE);
		array("BgActivePlayerCard", "BgActivePlayerName", "BgActivePlayerScore", "BgCard", "BgCardSystem", "BgMediaTracker", 
			"BgPlayerCard", "BgPlayerCardBig", "BgPlayerCardSmall", "BgPlayerName", "BgPlayerScore", "BgRacePlayerLine", 
			"BgRacePlayerName", "ProgressBar");
	}
}

?>
