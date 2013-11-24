<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad class for style 'Icons64x64_2'
 *
 * @author steeffeen
 */
class Quad_Icons64x64_2 extends Quad {
	/**
	 * Constants
	 */
	const STYLE = 'Icons64x64_2';

	/**
	 * Construct Icons64x64_2 quad
	 */
	public function __construct() {
		parent::__construct();
		$this->setStyle(self::STYLE);
		array("ArrowElimination", "ArrowHit", "Disconnected", "DisconnectedLight", "LaserElimination", "LaserHit", 
			"NucleusElimination", "NucleusHit", "RocketElimination", "RocketHit", "ServerNotice", "UnknownElimination", "UnknownHit");
	}
}

?>
