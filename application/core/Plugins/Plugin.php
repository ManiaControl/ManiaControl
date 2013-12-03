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
	 * Create a new plugin
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl        	
	 */
	public function __construct(ManiaControl $maniaControl);

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
