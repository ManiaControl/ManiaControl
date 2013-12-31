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
	 *
	 * @see \FML\Controls\Quad
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->setStyle(self::STYLE);
	}
}
