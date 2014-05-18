<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad Class for 'UiSMSpectatorScoreBig' Style
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Quad_UiSMSpectatorScoreBig extends Quad {
	/*
	 * Constants
	 */
	const STYLE                       = 'UiSMSpectatorScoreBig';
	CONST SUBSTYLE_BotLeft            = 'BotLeft';
	CONST SUBSTYLE_BotLeftGlass       = 'BotLeftGlass';
	CONST SUBSTYLE_BotRight           = 'BotRight';
	CONST SUBSTYLE_BotRightGlass      = 'BotRightGlass';
	CONST SUBSTYLE_CenterShield       = 'CenterShield';
	CONST SUBSTYLE_CenterShieldSmall  = 'CenterShieldSmall';
	CONST SUBSTYLE_HandleLeft         = 'HandleLeft';
	CONST SUBSTYLE_HandleRight        = 'HandleRight';
	CONST SUBSTYLE_PlayerGlass        = 'PlayerGlass';
	CONST SUBSTYLE_PlayerIconBg       = 'PlayerIconBg';
	CONST SUBSTYLE_PlayerJunction     = 'PlayerJunction';
	CONST SUBSTYLE_PlayerSlot         = 'PlayerSlot';
	CONST SUBSTYLE_PlayerSlotCenter   = 'PlayerSlotCenter';
	CONST SUBSTYLE_PlayerSlotRev      = 'PlayerSlotRev';
	CONST SUBSTYLE_PlayerSlotSmall    = 'PlayerSlotSmall';
	CONST SUBSTYLE_PlayerSlotSmallRev = 'PlayerSlotSmallRev';
	CONST SUBSTYLE_TableBgHoriz       = 'TableBgHoriz';
	CONST SUBSTYLE_TableBgVert        = 'TableBgVert';
	CONST SUBSTYLE_Top                = 'Top';
	CONST SUBSTYLE_UIRange1Bg         = 'UIRange1Bg';
	CONST SUBSTYLE_UIRange2Bg         = 'UIRange2Bg';

	/**
	 * Create a new Quad_UiSMSpectatorScoreBig Control
	 *
	 * @param string $id (optional) Control Id
	 * @return \FML\Controls\Quads\Quad_UiSMSpectatorScoreBig
	 */
	public static function create($id = null) {
		$quadUiSMSpectatorScoreBig = new Quad_UiSMSpectatorScoreBig($id);
		return $quadUiSMSpectatorScoreBig;
	}

	/**
	 * Construct a new Quad_UiSMSpectatorScoreBig Control
	 *
	 * @param string $id (optional) Control Id
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->setStyle(self::STYLE);
	}
}
