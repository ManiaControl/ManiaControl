<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad class for style 'Copilot'
 *
 * @author steeffeen
 */
class Quad_Copilot extends Quad {
	/**
	 * Constants
	 */
	const STYLE = 'Copilot';

	/**
	 * Construct Copilot quad
	 */
	public function __construct() {
		parent::__construct();
		$this->setStyle(self::STYLE);
		array("Down", "DownGood", "DownWrong", "Left", "LeftGood", "LeftWrong", "Right", "RightGood", "RightWrong", "Up", "UpGood", 
			"UpWrong");
	}
}

?>
