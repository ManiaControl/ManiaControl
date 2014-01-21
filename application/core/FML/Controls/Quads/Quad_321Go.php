<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad Class for '321Go' Style
 *
 * @author steeffeen
 */
class Quad_321Go extends Quad {
	/*
	 * Constants
	 */
	const STYLE = '321Go';
	const SUBSTYLE_3 = '3';
	const SUBSTYLE_2 = '2';
	const SUBSTYLE_1 = '1';
	const SUBSTYLE_Go = 'Go!';

	/**
	 * Create a new Quad_321Go Control
	 *
	 * @param string $id (optional) Control Id
	 * @return \FML\Controls\Quads\Quad_321Go
	 */
	public static function create($id = null) {
		$quad321Go = new Quad_321Go($id);
		return $quad321Go;
	}

	/**
	 * Construct a new Quad_321Go Control
	 *
	 * @param string $id (optional) Control Id
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->setStyle(self::STYLE);
	}
}
