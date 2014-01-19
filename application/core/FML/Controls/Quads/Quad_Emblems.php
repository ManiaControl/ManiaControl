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
	 * Create a new Quad_Emblems Control
	 *
	 * @param string $id (optional) Control Id
	 * @return \FML\Controls\Quads\Quad_Emblems
	 */
	public static function create($id = null) {
		$quadEmblems = new Quad_Emblems($id);
		return $quadEmblems;
	}

	/**
	 * Construct a new Quad_Emblems Control
	 *
	 * @param string $id (optional) Control Id
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->setStyle(self::STYLE);
	}
}
