<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad class for style 'BgsChallengeMedals'
 *
 * @author steeffeen
 */
class Quad_BgsChallengeMedals extends Quad {
	/**
	 * Constants
	 */
	const STYLE = 'BgsChallengeMedals';
    const SUBSTYLE_BgBronze = 'BgBronze';
    const SUBSTYLE_BgGold = 'BgGold';
    const SUBSTYLE_BgNadeo = 'BgNadeo';
    const SUBSTYLE_BgNotPlayed = 'BgNotPlayed';
    const SUBSTYLE_BgPlayed = 'BgPlayed';
    const SUBSTYLE_BgSilver = 'BgSilver';

	/**
	 * Construct BgsChallengeMedals quad
	 */
	public function __construct() {
		parent::__construct();
		$this->setStyle(self::STYLE);
	}
}
