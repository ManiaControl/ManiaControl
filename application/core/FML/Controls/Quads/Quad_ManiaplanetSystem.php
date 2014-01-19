<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad Class for 'ManiaplanetSystem' Style
 *
 * @author steeffeen
 */
class Quad_ManiaplanetSystem extends Quad {
	/**
	 * Constants
	 */
	const STYLE = 'ManiaplanetSystem';
	const SUBSTYLE_BgDialog = 'BgDialog';
	const SUBSTYLE_BgDialogAnchor = 'BgDialogAnchor';
	const SUBSTYLE_BgFloat = 'BgFloat';
	const SUBSTYLE_Events = 'Events';
	const SUBSTYLE_Medals = 'Medals';
	const SUBSTYLE_Statistics = 'Statistics';

	/**
	 * Create a new Quad_ManiaplanetSystem Control
	 *
	 * @param string $id (optional) Control Id
	 * @return \FML\Controls\Quads\Quad_ManiaplanetSystem
	 */
	public static function create($id = null) {
		$quadManiaplanetSystem = new Quad_ManiaplanetSystem($id);
		return $quadManiaplanetSystem;
	}

	/**
	 * Construct a new Quad_ManiaplanetSystem Control
	 *
	 * @param string $id (optional) Control Id
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->setStyle(self::STYLE);
	}
}
