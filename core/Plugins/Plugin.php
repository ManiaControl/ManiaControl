<?php

namespace ManiaControl\Plugins;

use ManiaControl\ManiaControl;

/**
 * Interface for ManiaControl Plugins
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface Plugin {
	/*
	 * Constants
	 */
	const PLUGIN_INTERFACE = __CLASS__;

	/**
	 * Prepare the Plugin
	 *
	 * @param ManiaControl $maniaControl
	 */
	public static function prepare(ManiaControl $maniaControl);

	/**
	 * Get plugin id
	 *
	 * @return int
	 */
	public static function getId();

	/**
	 * Get Plugin Name
	 *
	 * @return string
	 */
	public static function getName();

	/**
	 * Get Plugin Version
	 *
	 * @return string
	 */
	public static function getVersion();

	/**
	 * Get Plugin Author
	 *
	 * @return string
	 */
	public static function getAuthor();

	/**
	 * Get Plugin Description
	 *
	 * @return string
	 */
	public static function getDescription();

	/**
	 * Load the plugin
	 *
	 * @param ManiaControl $maniaControl
	 * @return bool
	 */
	public function load(ManiaControl $maniaControl);

	/**
	 * Unload the plugin and its Resources
	 */
	public function unload();
}
