<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad class for style 'ManiaplanetSystem'
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
	 * Construct ManiaplanetSystem quad
	 */
	public function __construct() {
		parent::__construct();
		$this->setStyle(self::STYLE);
	}
}

?>
