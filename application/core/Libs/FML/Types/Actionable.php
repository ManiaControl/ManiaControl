<?php

namespace FML\Types;

/**
 * Interface for Elements that support the action attribute
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface Actionable {
	/*
	 * Constants
	 */
	const ACTION_0                 = '0';
	const ACTION_BACK              = 'back';
	const ACTION_ENTER             = 'enter';
	const ACTION_HOME              = 'home';
	const ACTION_MENU_SOLO         = 'menu_solo';
	const ACTION_MENU_COMPETITIONS = 'menu_competitions';
	const ACTION_MENU_LOCAL        = 'menu_local';
	const ACTION_MENU_INTERNET     = 'menu_internet';
	const ACTION_MENU_EDITORS      = 'menu_editors';
	const ACTION_MENU_PROFILE      = 'menu_profile';
	const ACTION_QUIT              = 'quit';
	const ACTION_QUITSERVER        = 'maniaplanet:quitserver';
	const ACTION_SAVEREPLAY        = 'maniaplanet:savereplay';
	const ACTION_TOGGLESPEC        = 'maniaplanet:togglespec';
	const ACTIONKEY_F5             = 1;
	const ACTIONKEY_F6             = 2;
	const ACTIONKEY_F7             = 3;
	const ACTIONKEY_F8             = 4;

	/**
	 * Set action
	 *
	 * @param string $action Action name
	 * @return \FML\Types\Actionable|static
	 */
	public function setAction($action);

	/**
	 * Get the assigned action
	 *
	 * @return string
	 */
	public function getAction();

	/**
	 * Set action key
	 *
	 * @param int $actionKey Action key
	 * @return \FML\Types\Actionable|static
	 */
	public function setActionKey($actionKey);
}
