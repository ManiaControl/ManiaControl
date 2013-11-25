<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad class for style 'Emblems'
 *
 * @author steeffeen
 */
class Quad_Emblems extends Quad {
	/**
	 * Constants
	 */
	const STYLE = 'Emblems';
	const SUBSTYLE_0 = '#0';
	const SUBSTYLE_1 = '#1';
	const SUBSTYLE_2 = '#2';

	/**
	 * Construct Emblems quad
	 */
	public function __construct() {
		parent::__construct();
		$this->setStyle(self::STYLE);
	}
}

?>
