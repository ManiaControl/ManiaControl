<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad Class for 'BgsChallengeMedals' Style
 *
 * @author steeffeen
 */
class Quad_BgsChallengeMedals extends Quad {
	/*
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
	 * Create a new Quad_BgsChallengeMedals Control
	 *
	 * @param string $id (optional) Control Id
	 * @return \FML\Controls\Quads\Quad_BgsChallengeMedals
	 */
	public static function create($id = null) {
		$quadBgsChallengeMedals = new Quad_BgsChallengeMedals($id);
		return $quadBgsChallengeMedals;
	}

	/**
	 * Construct a new Quad_BgsChallengeMedals Control
	 *
	 * @param string $id (optional) Control Id
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->setStyle(self::STYLE);
	}
}
