<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad Class for 'MedalsBig' Style
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
	 * Create a new Quad_MedalsBig Control
	 *
	 * @param string $id (optional) Control Id
	 * @return \FML\Controls\Quads\Quad_MedalsBig
	 */
	public static function create($id = null) {
		$quadMedalsBig = new Quad_MedalsBig($id);
		return $quadMedalsBig;
	}

	/**
	 * Construct a new Quad_MedalsBig Control
	 *
	 * @param string $id (optional) Control Id
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->setStyle(self::STYLE);
	}
}
