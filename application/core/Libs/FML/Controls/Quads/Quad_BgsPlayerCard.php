<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad Class for 'BgsPlayerCard' Style
 *
 * @author steeffeen
 */
class Quad_BgsPlayerCard extends Quad {
	/*
	 * Constants
	 */
	const STYLE = 'BgsPlayerCard';
	const SUBSTYLE_BgActivePlayerCard = 'BgActivePlayerCard';
	const SUBSTYLE_BgActivePlayerName = 'BgActivePlayerName';
	const SUBSTYLE_BgActivePlayerScore = 'BgActivePlayerScore';
	const SUBSTYLE_BgCard = 'BgCard';
	const SUBSTYLE_BgCardSystem = 'BgCardSystem';
	const SUBSTYLE_BgMediaTracker = 'BgMediaTracker';
	const SUBSTYLE_BgPlayerCard = 'BgPlayerCard';
	const SUBSTYLE_BgPlayerCardBig = 'BgPlayerCardBig';
	const SUBSTYLE_BgPlayerCardSmall = 'BgPlayerCardSmall';
	const SUBSTYLE_BgPlayerName = 'BgPlayerName';
	const SUBSTYLE_BgPlayerScore = 'BgPlayerScore';
	const SUBSTYLE_BgRacePlayerLine = 'BgRacePlayerLine';
	const SUBSTYLE_BgRacePlayerName = 'BgRacePlayerName';
	const SUBSTYLE_ProgressBar = 'ProgressBar';

	/**
	 * Create a new Quad_BgsPlayerCard Control
	 *
	 * @param string $id (optional) Control Id
	 * @return \FML\Controls\Quads\Quad_BgsPlayerCard
	 */
	public static function create($id = null) {
		$quadBgsPlayerCard = new Quad_BgsPlayerCard($id);
		return $quadBgsPlayerCard;
	}

	/**
	 * Construct a new Quad_BgsPlayerCard Control
	 *
	 * @param string $id (optional) Control Id
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->setStyle(self::STYLE);
	}
}
