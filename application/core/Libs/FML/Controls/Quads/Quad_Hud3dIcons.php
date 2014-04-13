<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad Class for 'Hud3dIcons' Style
 *
 * @author steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Quad_Hud3dIcons extends Quad {
	/*
	 * Constants
	 */
	const STYLE = 'Hud3dIcons';
	const SUBSTYLE_Cross = 'Cross';
	const SUBSTYLE_CrossTargeted = 'CrossTargeted';
	const SUBSTYLE_Player1 = 'Player1';
	const SUBSTYLE_Player2 = 'Player2';
	const SUBSTYLE_Player3 = 'Player3';
	const SUBSTYLE_PointA = 'PointA';
	const SUBSTYLE_PointB = 'PointB';
	const SUBSTYLE_PointC = 'PointC';

	/**
	 * Create a new Quad_Hud3dIcons Control
	 *
	 * @param string $id (optional) Control Id
	 * @return \FML\Controls\Quads\Quad_Hud3dIcons
	 */
	public static function create($id = null) {
		$quadHud3dIcons = new Quad_Hud3dIcons($id);
		return $quadHud3dIcons;
	}

	/**
	 * Construct a new Quad_Hud3dIcons Control
	 *
	 * @param string $id (optional) Control Id
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->setStyle(self::STYLE);
	}
}
