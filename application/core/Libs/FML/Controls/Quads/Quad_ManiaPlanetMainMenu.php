<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad Class for 'ManiaPlanetMainMenu' Style
 *
 * @author steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Quad_ManiaPlanetMainMenu extends Quad {
	/*
	 * Constants
	 */
	const STYLE = 'ManiaPlanetMainMenu';
	const SUBSTYLE_BottomBar = 'BottomBar';
	const SUBSTYLE_Highlight = 'Highlight';
	const SUBSTYLE_IconAdd = 'IconAdd';
	const SUBSTYLE_IconHome = 'IconHome';
	const SUBSTYLE_IconPlay = 'IconPlay';
	const SUBSTYLE_IconQuit = 'IconQuit';
	const SUBSTYLE_IconSettings = 'IconSettings';
	const SUBSTYLE_IconStore = 'IconStore';
	const SUBSTYLE_MainBg = 'MainBg';
	const SUBSTYLE_TitleBg = 'TitleBg';
	const SUBSTYLE_TopBar = 'TopBar';

	/**
	 * Create a new Quad_ManiaPlanetMainMenu Control
	 *
	 * @param string $id (optional) Control Id
	 * @return \FML\Controls\Quads\Quad_ManiaPlanetMainMenu
	 */
	public static function create($id = null) {
		$quadManiaPlanetMainMenu = new Quad_ManiaPlanetMainMenu($id);
		return $quadManiaPlanetMainMenu;
	}

	/**
	 * Construct a new Quad_ManiaPlanetMainMenu Control
	 *
	 * @param string $id (optional) Control Id
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->setStyle(self::STYLE);
	}
}
