<?php


namespace ManiaControl\Manialinks;

/**
 * Interface for the Sidebar managing
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */

interface SidebarMenuEntryRenderable {

	/**
	 *  Call here the function which updates the MenuIcon ManiaLink
	 */
	public function renderMenuEntry();
}