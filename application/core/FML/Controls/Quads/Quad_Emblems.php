<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad Class for 'Emblems' Style
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
	 *
	 * @see \FML\Controls\Quad
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->setStyle(self::STYLE);
	}
}
