<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad class for style '321Go'
 *
 * @author steeffeen
 */
class Quad_321Go extends Quad {
	/**
	 * Constants
	 */
	const STYLE = '321Go';
	const SUBSTYLE_3 = '3';
	const SUBSTYLE_2 = '2';
	const SUBSTYLE_1 = '1';
	const SUBSTYLE_Go = 'Go!';

	/**
	 * Construct 321Go quad
	 */
	public function __construct() {
		parent::__construct();
		$this->setStyle(self::STYLE);
	}
}
