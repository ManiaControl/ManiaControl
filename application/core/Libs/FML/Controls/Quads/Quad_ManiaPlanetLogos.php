<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad Class for 'ManiaPlanetLogos' Style
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Quad_ManiaPlanetLogos extends Quad {
	/*
	 * Constants
	 */
	const STYLE                              = 'ManiaPlanetLogos';
	const SUBSTYLE_IconPlanets               = 'IconPlanets';
	const SUBSTYLE_IconPlanetsPerspective    = 'IconPlanetsPerspective';
	const SUBSTYLE_IconPlanetsSmall          = 'IconPlanetsSmall';
	const SUBSTYLE_ManiaPlanetLogoBlack      = 'ManiaPlanetLogoBlack';
	const SUBSTYLE_ManiaPlanetLogoBlackSmall = 'ManiaPlanetLogoBlackSmall';
	const SUBSTYLE_ManiaPlanetLogoWhite      = 'ManiaPlanetLogoWhite';
	const SUBSTYLE_ManiaPlanetLogoWhiteSmall = 'ManiaPlanetLogoWhiteSmall';

	/**
	 * Create a new Quad_ManiaPlanetLogos Control
	 *
	 * @param string $id (optional) Control Id
	 * @return \FML\Controls\Quads\Quad_ManiaPlanetLogos
	 */
	public static function create($id = null) {
		$quadManiaPlanetLogos = new Quad_ManiaPlanetLogos($id);
		return $quadManiaPlanetLogos;
	}

	/**
	 * Construct a new Quad_ManiaPlanetLogos Control
	 *
	 * @param string $id (optional) Control Id
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->setStyle(self::STYLE);
	}
}
