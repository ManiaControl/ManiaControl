<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad Class for 'BgRaceScore2' Style
 *
 * @author steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Quad_BgRaceScore2 extends Quad {
	/*
	 * Constants
	 */
	const STYLE = 'BgRaceScore2';
	const SUBSTYLE_BgCardPlayer = 'BgCardPlayer';
	const SUBSTYLE_BgCardServer = 'BgCardServer';
	const SUBSTYLE_BgScores = 'BgScores';
	const SUBSTYLE_Cartouche = 'Cartouche';
	const SUBSTYLE_CartoucheLine = 'CartoucheLine';
	const SUBSTYLE_CupFinisher = 'CupFinisher';
	const SUBSTYLE_CupPotentialFinisher = 'CupPotentialFinisher';
	const SUBSTYLE_Fame = 'Fame';
	const SUBSTYLE_Handle = 'Handle';
	const SUBSTYLE_HandleBlue = 'HandleBlue';
	const SUBSTYLE_HandleRed = 'HandleRed';
	const SUBSTYLE_HandleSelectable = 'HandleSelectable';
	const SUBSTYLE_IsLadderDisabled = 'IsLadderDisabled';
	const SUBSTYLE_IsLocalPlayer = 'IsLocalPlayer';
	const SUBSTYLE_LadderPoints = 'LadderPoints';
	const SUBSTYLE_LadderRank = 'LadderRank';
	const SUBSTYLE_Laps = 'Laps';
	const SUBSTYLE_Podium = 'Podium';
	const SUBSTYLE_Points = 'Points';
	const SUBSTYLE_SandTimer = 'SandTimer';
	const SUBSTYLE_ScoreLink = 'ScoreLink';
	const SUBSTYLE_ScoreReplay = 'ScoreReplay';
	const SUBSTYLE_SendScore = 'SendScore';
	const SUBSTYLE_Speaking = 'Speaking';
	const SUBSTYLE_Spectator = 'Spectator';
	const SUBSTYLE_Tv = 'Tv';
	const SUBSTYLE_Warmup = 'Warmup';

	/**
	 * Create a new Quad_BgRaceScore2 Control
	 *
	 * @param string $id (optional) Control Id
	 * @return \FML\Controls\Quads\Quad_BgRaceScore2
	 */
	public static function create($id = null) {
		$quadBgRaceScore2 = new Quad_BgRaceScore2($id);
		return $quadBgRaceScore2;
	}

	/**
	 * Construct a new Quad_BgRaceScore2 Control
	 *
	 * @param string $id (optional) Control Id
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->setStyle(self::STYLE);
	}
}
