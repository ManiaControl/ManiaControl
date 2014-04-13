<?php

namespace FML\Types;

/**
 * Interface for Elements that support the Action Attribute
 *
 * @author steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface Actionable {
	/*
	 * Constants
	 */
	const ACTION_0 = '0';
	const ACTION_BACK = 'back';
	const ACTION_ENTER = 'enter';
	const ACTION_HOME = 'home';
	const ACTION_MENU_SOLO = 'menu_solo';
	const ACTION_MENU_COMPETITIONS = 'menu_competitions';
	const ACTION_MENU_LOCAL = 'menu_local';
	const ACTION_MENU_INTERNET = 'menu_internet';
	const ACTION_MENU_EDITORS = 'menu_editors';
	const ACTION_MENU_PROFILE = 'menu_profile';
	const ACTION_QUIT = 'quit';
	const ACTION_QUITSERVER = 'maniaplanet:quitserver';
	const ACTION_SAVEREPLAY = 'maniaplanet:savereplay';
	const ACTION_TOGGLESPEC = 'maniaplanet:togglespec';
	const ACTIONKEY_F5 = 1;
	const ACTIONKEY_F6 = 2;
	const ACTIONKEY_F7 = 3;
	const ACTIONKEY_F8 = 4;

	/**
	 * Set Action
	 *
	 * @param string $action Action Name
	 * @return \FML\Types\Actionable
	 */
	public function setAction($action);

	/**
	 * Get the assigned Action
	 *
	 * @return string 
	 */
	public function getAction();

	/**
	 * Set Action Key
	 *
	 * @param int $actionKey Action Key Number
	 * @return \FML\Types\Actionable
	 */
	public function setActionKey($actionKey);
}
