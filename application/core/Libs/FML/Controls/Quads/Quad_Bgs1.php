<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad Class for 'Bgs1' Style
 *
 * @author steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Quad_Bgs1 extends Quad {
	/*
	 * Constants
	 */
	const STYLE = 'Bgs1';
	const SUBSTYLE_ArrowDown = 'ArrowDown';
	const SUBSTYLE_ArrowLeft = 'ArrowLeft';
	const SUBSTYLE_ArrowRight = 'ArrowRight';
	const SUBSTYLE_ArrowUp = 'ArrowUp';
	const SUBSTYLE_BgButton = 'BgButton';
	const SUBSTYLE_BgButtonBig = 'BgButtonBig';
	const SUBSTYLE_BgButtonGlow = 'BgButtonGlow';
	const SUBSTYLE_BgButtonGrayed = 'BgButtonGrayed';
	const SUBSTYLE_BgButtonOff = 'BgButtonOff';
	const SUBSTYLE_BgButtonShadow = 'BgButtonShadow';
	const SUBSTYLE_BgButtonSmall = 'BgButtonSmall';
	const SUBSTYLE_BgCard = 'BgCard';
	const SUBSTYLE_BgCard1 = 'BgCard1';
	const SUBSTYLE_BgCard2 = 'BgCard2';
	const SUBSTYLE_BgCard3 = 'BgCard3';
	const SUBSTYLE_BgCardBuddy = 'BgCardBuddy';
	const SUBSTYLE_BgCardChallenge = 'BgCardChallenge';
	const SUBSTYLE_BgCardFolder = 'BgCardFolder';
	const SUBSTYLE_BgCardInventoryItem = 'BgCardInventoryItem';
	const SUBSTYLE_BgCardList = 'BgCardList';
	const SUBSTYLE_BgCardOnline = 'BgCardOnline';
	const SUBSTYLE_BgCardPlayer = 'BgCardPlayer';
	const SUBSTYLE_BgCardProperty = 'BgCardProperty';
	const SUBSTYLE_BgCardSystem = 'BgCardSystem';
	const SUBSTYLE_BgCardZone = 'BgCardZone';
	const SUBSTYLE_BgColorContour = 'BgColorContour';
	const SUBSTYLE_BgDialogBlur = 'BgDialogBlur';
	const SUBSTYLE_BgEmpty = 'BgEmpty';
	const SUBSTYLE_BgGradBottom = 'BgGradBottom';
	const SUBSTYLE_BgGradLeft = 'BgGradLeft';
	const SUBSTYLE_BgGradRight = 'BgGradRight';
	const SUBSTYLE_BgGradTop = 'BgGradTop';
	const SUBSTYLE_BgGradV = 'BgGradV';
	const SUBSTYLE_BgHealthBar = 'BgHealthBar';
	const SUBSTYLE_BgIconBorder = 'BgIconBorder';
	const SUBSTYLE_BgList = 'BgList';
	const SUBSTYLE_BgListLine = 'BgListLine';
	const SUBSTYLE_BgPager = 'BgPager';
	const SUBSTYLE_BgProgressBar = 'BgProgressBar';
	const SUBSTYLE_BgShadow = 'BgShadow';
	const SUBSTYLE_BgSlider = 'BgSlider';
	const SUBSTYLE_BgSystemBar = 'BgSystemBar';
	const SUBSTYLE_BgTitle2 = 'BgTitle2';
	const SUBSTYLE_BgTitle3 = 'BgTitle3';
	const SUBSTYLE_BgTitle3_1 = 'BgTitle3_1';
	const SUBSTYLE_BgTitle3_2 = 'BgTitle3_2';
	const SUBSTYLE_BgTitle3_3 = 'BgTitle3_3';
	const SUBSTYLE_BgTitle3_4 = 'BgTitle3_4';
	const SUBSTYLE_BgTitle3_5 = 'BgTitle3_5';
	const SUBSTYLE_BgTitleGlow = 'BgTitleGlow';
	const SUBSTYLE_BgTitlePage = 'BgTitlePage';
	const SUBSTYLE_BgTitleShadow = 'BgTitleShadow';
	const SUBSTYLE_BgWindow1 = 'BgWindow1';
	const SUBSTYLE_BgWindow2 = 'BgWindow2';
	const SUBSTYLE_BgWindow3 = 'BgWindow3';
	const SUBSTYLE_BgWindow4 = 'BgWindow4';
	const SUBSTYLE_EnergyBar = 'EnergyBar';
	const SUBSTYLE_EnergyTeam2 = 'EnergyTeam2';
	const SUBSTYLE_Glow = 'Glow';
	const SUBSTYLE_HealthBar = 'HealthBar';
	const SUBSTYLE_NavButton = 'NavButton';
	const SUBSTYLE_NavButtonBlink = 'NavButtonBlink';
	const SUBSTYLE_NavButtonQuit = 'NavButtonQuit';
	const SUBSTYLE_ProgressBar = 'ProgressBar';
	const SUBSTYLE_ProgressBarSmall = 'ProgressBarSmall';
	const SUBSTYLE_Shadow = 'Shadow';

	/**
	 * Create a new Quad_Bgs1 Control
	 *
	 * @param string $id (optional) Control Id
	 * @return \FML\Controls\Quads\Quad_Bgs1
	 */
	public static function create($id = null) {
		$quadBgs1 = new Quad_Bgs1($id);
		return $quadBgs1;
	}

	/**
	 * Construct a new Quad_Bgs1 Control
	 *
	 * @param string $id (optional) Control Id
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->setStyle(self::STYLE);
	}
}
