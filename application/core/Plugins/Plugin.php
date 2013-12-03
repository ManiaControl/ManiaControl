<?php

namespace ManiaControl\Plugins;

use ManiaControl\ManiaControl;

/**
 * Plugin parent class
 *
 * @author steeffeen & kremsy
 */
abstract class Plugin {
	/**
	 * Plugin Metadata
	 */
	public static $name = 'undefined';
	public static $version = 'undefined';
	public static $author = 'undefined';
	public static $description = 'undefined';
	
	/**
	 * Protected properties
	 */
	protected $maniaControl = null;

	/**
	 * Create a new plugin
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl        	
	 */
	public abstract function __construct(ManiaControl $maniaControl);

	/**
	 * Get class name as string
	 *
	 * @return string
	 */
	public static final function getClass() {
		return __CLASS__;
	}
}

?>
