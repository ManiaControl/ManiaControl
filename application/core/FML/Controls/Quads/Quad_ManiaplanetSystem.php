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
	 *
	 * @see \FML\Controls\Quad
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->setStyle(self::STYLE);
	}
}
