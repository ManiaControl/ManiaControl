<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad class for style 'Icons128x32_1'
 *
 * @author steeffeen
 */
class Quad_Icons128x32_1 extends Quad {
	/**
	 * Constants
	 */
	const STYLE = 'Icons128x32_1';

	/**
	 * Construct Icons128x32_1 quad
	 */
	public function __construct() {
		parent::__construct();
		$this->setStyle(self::STYLE);
		array("Empty", "ManiaLinkHome", "ManiaLinkSwitch", "ManiaPlanet", "Music", "PainterBrush", "PainterFill", "PainterLayer", 
			"PainterMirror", "PainterSticker", "PainterTeam", "RT_Cup", "RT_Laps", "RT_Rounds", "RT_Script", "RT_Team", "RT_TimeAttack", 
			"RT_Stunts", "Settings", "SliderBar", "SliderBar2", "SliderCursor", "Sound", "UrlBg", "Windowed");
	}
}

?>
