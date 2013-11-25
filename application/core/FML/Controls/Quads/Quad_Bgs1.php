<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad class for style 'Bgs1'
 *
 * @author steeffeen
 */
class Quad_Bgs1 extends Quad {
	/**
	 * Constants
	 */
	const STYLE = 'Bgs1';

	/**
	 * Construct Bgs1 quad
	 */
	public function __construct() {
		parent::__construct();
		$this->setStyle(self::STYLE);
		array("ArrowDown", "ArrowLeft", "ArrowRight", "ArrowUp", "BgButton", "BgButtonBig", "BgButtonGlow", "BgButtonGrayed", 
			"BgButtonOff", "BgButtonShadow", "BgButtonSmall", "BgCard", "BgCard1", "BgCard2", "BgCard3", "BgCardBuddy", 
			"BgCardChallenge", "BgCardFolder", "BgCardInventoryItem", "BgCardList", "BgCardOnline", "BgCardPlayer", "BgCardSystem", 
			"BgCardZone", "BgColorContour", "BgDialogBlur", "BgEmpty", "BgGradBottom", "BgGradLeft", "BgGradRight", "BgGradTop", 
			"BgGradV", "BgHealthBar", "BgIconBorder", "BgList", "BgListLine", "BgPager", "BgProgressBar", "BgShadow", "BgSlider", 
			"BgSystemBar", "BgTitle2", "BgTitle3", "BgTitle3_1", "BgTitle3_2", "BgTitle3_3", "BgTitle3_4", "BgTitle3_5", "BgTitleGlow", 
			"BgTitlePage", "BgTitleShadow", "BgWindow1", "BgWindow2", "BgWindow3", "EnergyBar", "EnergyTeam2", "Glow", "HealthBar", 
			"NavButton", "NavButtonBlink", "NavButtonQuit", "ProgressBar", "ProgressBarSmall", "Shadow");
	}
}

?>
