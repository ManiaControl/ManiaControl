<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad Class for 'UIConstruction_Buttons2' Style
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Quad_UIConstruction_Buttons2 extends Quad {
	/*
	 * Constants
	 */
	const STYLE                = 'UIConstruction_Buttons2';
	const SUBSTYLE_AirMapping  = 'AirMapping';
	const SUBSTYLE_BlockEditor = 'BlockEditor';
	const SUBSTYLE_Copy        = 'Copy';
	const SUBSTYLE_Cut         = 'Cut';
	const SUBSTYLE_GhostBlocks = 'GhostBlocks';
	const SUBSTYLE_KeysAdd     = 'KeysAdd';
	const SUBSTYLE_KeysCopy    = 'KeysCopy';
	const SUBSTYLE_KeysDelete  = 'KeysDelete';
	const SUBSTYLE_KeysPaste   = 'KeysPaste';
	const SUBSTYLE_New         = 'New';
	const SUBSTYLE_Open        = 'Open';
	const SUBSTYLE_Symmetry    = 'Symmetry';

	/**
	 * Create a new Quad_UIConstruction_Buttons2 Control
	 *
	 * @param string $id (optional) Control Id
	 * @return \FML\Controls\Quads\Quad_UIConstruction_Buttons2
	 */
	public static function create($id = null) {
		$quadUIConstructionButtons2 = new Quad_UIConstruction_Buttons2($id);
		return $quadUIConstructionButtons2;
	}

	/**
	 * Construct a new Quad_UIConstruction_Buttons2 Control
	 *
	 * @param string $id (optional) Control Id
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->setStyle(self::STYLE);
	}
}
