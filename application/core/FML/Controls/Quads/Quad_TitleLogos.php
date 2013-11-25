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

	/**
	 * Construct TitleLogos quad
	 */
	public function __construct() {
		parent::__construct();
		$this->setStyle(self::STYLE);
		array("Author", "Collection", "Icon", "Title");
	}
}

?>
