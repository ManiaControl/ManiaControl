<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad class for 'TitleLogos' styles
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Quad_TitleLogos extends Quad {
	/*
	 * Constants
	 */
	const STYLE               = 'TitleLogos';
	const SUBSTYLE_Author     = 'Author';
	const SUBSTYLE_Collection = 'Collection';
	const SUBSTYLE_Icon       = 'Icon';
	const SUBSTYLE_Title      = 'Title';

	/*
	 * Protected properties
	 */
	protected $style = self::STYLE;
}
