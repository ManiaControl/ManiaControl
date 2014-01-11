<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad Class for 'ManiaPlanetLogos' Style
 *
 * @author steeffeen
 */
class Quad_ManiaPlanetLogos extends Quad {
	/**
	 * Constants
	 */
	const STYLE = 'ManiaPlanetLogos';
	const SUBSTYLE_IconPlanets = 'IconPlanets';
	const SUBSTYLE_IconPlanetsPerspective = 'IconPlanetsPerspective';
	const SUBSTYLE_IconPlanetsSmall = 'IconPlanetsSmall';
	const SUBSTYLE_ManiaPlanetLogoBlack = 'ManiaPlanetLogoBlack';
	const SUBSTYLE_ManiaPlanetLogoBlackSmall = 'ManiaPlanetLogoBlackSmall';
	const SUBSTYLE_ManiaPlanetLogoWhite = 'ManiaPlanetLogoWhite';
	const SUBSTYLE_ManiaPlanetLogoWhiteSmall = 'ManiaPlanetLogoWhiteSmall';

	/**
	 *
	 * @see \FML\Controls\Quad
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->setStyle(self::STYLE);
	}
}
