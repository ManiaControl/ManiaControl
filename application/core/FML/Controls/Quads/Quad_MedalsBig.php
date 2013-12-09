<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad class for style 'MedalsBig'
 *
 * @author steeffeen
 */
class Quad_MedalsBig extends Quad {
	/**
	 * Constants
	 */
	const STYLE = 'MedalsBig';
    const SUBSTYLE_MedalBronze = 'MedalBronze';
    const SUBSTYLE_MedalGold = 'MedalGold';
    const SUBSTYLE_MedalGoldPerspective = 'MedalGoldPerspective';
    const SUBSTYLE_MedalNadeo = 'MedalNadeo';
    const SUBSTYLE_MedalNadeoPerspective = 'MedalNadeoPerspective';
    const SUBSTYLE_MedalSilver = 'MedalSilver';
    const SUBSTYLE_MedalSlot = 'MedalSlot';

	/**
	 * Construct MedalsBig quad
	 */
	public function __construct() {
		parent::__construct();
		$this->setStyle(self::STYLE);
	}
}
