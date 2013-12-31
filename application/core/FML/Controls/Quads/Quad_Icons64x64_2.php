<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad class for style 'Icons64x64_2'
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
	 *
	 * @see \FML\Controls\Quad
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->setStyle(self::STYLE);
	}
}
