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
	public abstract function getAuthor();

	/**
	 * Get plugin version
	 *
	 * @return float
	 */
	public abstract function getVersion();

	/**
	 * Get plugin name
	 *
	 * @return string
	 */
	public abstract function getName();

	/**
	 * Get plugin description
	 *
	 * @return string
	 */
	public abstract function getDescription();
}

?>
