<?php

namespace ManiaControl\Plugins;

use ManiaControl\ManiaControl;

/**
 * Interface for ManiaControl Plugins
 *
 * @author steeffeen & kremsy
 */
interface Plugin {
	/**
	 * Constants
	 */
	const PLUGIN_INTERFACE = __CLASS__;

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

?>
