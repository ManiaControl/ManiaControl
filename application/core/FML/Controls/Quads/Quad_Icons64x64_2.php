<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad Class for 'Icons64x64_2' Style
 *
 * @author steeffeen
 */
class Quad_Icons64x64_2 extends Quad {
	/**
	 * Constants
	 */
	const STYLE = 'Icons64x64_2';
	const SUBSTYLE_ArrowElimination = 'ArrowElimination';
	const SUBSTYLE_ArrowHit = 'ArrowHit';
	const SUBSTYLE_Disconnected = 'Disconnected';
	const SUBSTYLE_DisconnectedLight = 'DisconnectedLight';
	const SUBSTYLE_LaserElimination = 'LaserElimination';
	const SUBSTYLE_LaserHit = 'LaserHit';
	const SUBSTYLE_NucleusElimination = 'NucleusElimination';
	const SUBSTYLE_NucleusHit = 'NucleusHit';
	const SUBSTYLE_RocketElimination = 'RocketElimination';
	const SUBSTYLE_RocketHit = 'RocketHit';
	const SUBSTYLE_ServerNotice = 'ServerNotice';
	const SUBSTYLE_UnknownElimination = 'UnknownElimination';
	const SUBSTYLE_UnknownHit = 'UnknownHit';

	/**
	 * Create a new Quad_Icons64x64_2 Control
	 *
	 * @param string $id (optional) Control Id
	 * @return \FML\Controls\Quads\Quad_Icons64x64_2
	 */
	public static function create($id = null) {
		$quadIcons64x64_2 = new Quad_Icons64x64_2($id);
		return $quadIcons64x64_2;
	}

	/**
	 * Construct a new Quad_Icons64x64_2 Control
	 *
	 * @param string $id (optional) Control Id
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->setStyle(self::STYLE);
	}
}
