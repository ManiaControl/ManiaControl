<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad class for style 'TitleLogos'
 *
 * @author steeffeen
 */
class Quad_TitleLogos extends Quad {
	/**
	 * Constants
	 */
	const STYLE = 'TitleLogos';
    const SUBSTYLE_Author = 'Author';
    const SUBSTYLE_Collection = 'Collection';
    const SUBSTYLE_Icon = 'Icon';
    const SUBSTYLE_Title = 'Title';

	/**
	 * Construct TitleLogos quad
	 */
	public function __construct() {
		parent::__construct();
		$this->setStyle(self::STYLE);
	}
}
