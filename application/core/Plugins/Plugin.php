<?php

namespace ManiaControl\Plugins;

use ManiaControl\ManiaControl;

/**
 * Interface for ManiaControl Plugins
 *
 * @author steeffeen & kremsy
 * @copyright ManiaControl Copyright © 2014 ManiaControl Team
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface Plugin {
	/*
	 * Constants
	 */
	const PLUGIN_INTERFACE = __CLASS__;

	/**
	 * Prepares the Plugin
	 *
	 * @param ManiaControl $maniaControl
	 * @return mixed
	 */
	public static function prepare(ManiaControl $maniaControl);

	/**
	 * Load the plugin
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 * @return bool
	 */
	public function load(ManiaControl $maniaControl);

	/**
	 * Unload the plugin and its resources
	 */
	public function unload();

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
	 * @return float
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
}
