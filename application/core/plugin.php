<?php

namespace ManiaControl;

/**
 * Plugin parent class
 *
 * @author Lukas Kremsmayr and steeffeen
 */
abstract class Plugin {
	
	/**
	 * Private properties
	 */
	protected $maniaControl;
	protected $name;
	protected $version;
	protected $author;
	protected $description;

	/**
	 * Create plugin instance
	 *
	 * @param ManiaControl $maniaControl        	
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
