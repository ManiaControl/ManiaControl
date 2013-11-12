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
	 * Private properties
	 */
	protected $maniaControl = null;
	protected $name = 'undefined';
	protected $version = 'undefined';
	protected $author = 'undefined';
	protected $description = 'undefined';

	/**
	 * Create a new plugin
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl        	
	 */
	public abstract function __construct(ManiaControl $maniaControl);

	/**
	 * Get plugin author
	 *
	 * @return string
	 */
	public function getAuthor() {
		return $this->author;
	}

	/**
	 * Get plugin version
	 *
	 * @return float
	 */
	public function getVersion() {
		return $this->version;
	}

	/**
	 * Get plugin name
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Get plugin description
	 *
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}
}

?>
